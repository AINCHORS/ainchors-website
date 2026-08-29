<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseContent;
use App\Models\Product;
use App\Services\Admin\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourseContentController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $category = (string) $request->input('course_category', '');
        $category = array_key_exists($category, Product::COURSE_CATEGORIES) ? $category : '';

        $courses = Product::query()
            ->select(['id', 'name', 'slug', 'sku', 'course_category', 'status', 'created_at', 'updated_at'])
            ->where('type', 'course')
            ->with($this->safeCourseContentRelation())
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', fn ($query) => $query->where('course_category', $category))
            ->orderBy('sku')
            ->paginate(20)
            ->withQueryString();

        $mediaByCourse = $courses->getCollection()->mapWithKeys(function (Product $course): array {
            $content = $course->courseContent;

            if (! $content instanceof CourseContent) {
                return [$course->id => [
                    'video' => ['available' => false, 'configured' => false, 'name' => null, 'size' => null, 'extension' => null],
                    'slides' => ['available' => false, 'configured' => false, 'name' => null, 'size' => null, 'extension' => null],
                ]];
            }

            $video = $this->mediaDetails($content, 'video');
            $slides = $this->mediaDetails($content, 'slide');
            $video['configured'] = filled($content->video_url);
            $slides['configured'] = filled($content->slide_url);

            return [$course->id => compact('video', 'slides')];
        });

        $categories = Product::COURSE_CATEGORIES;

        return view('admin.course-content.index', compact('courses', 'mediaByCourse', 'categories', 'search', 'category'));
    }

    public function create(Request $request): View
    {
        $courses = Product::query()
            ->select(['id', 'name', 'slug', 'sku', 'status'])
            ->where('type', 'course')
            ->whereDoesntHave('courseContent')
            ->orderBy('name')
            ->get();
        $selectedProduct = $request->filled('product_id')
            ? $courses->firstWhere('id', (int) $request->input('product_id'))
            : null;

        return view('admin.course-content.create', compact('courses', 'selectedProduct'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['product_id' => ['required', 'integer', 'exists:products,id']]);
        $product = Product::query()->findOrFail((int) $request->input('product_id'));
        $this->ensureCourseWithoutContent($product);
        $data = $this->validatedMetadata($request, true);

        /** @var \App\Models\User $admin */
        $admin = $request->user();

        $storedFiles = $this->storeUploadedFiles($request, $product);

        try {
            DB::transaction(function () use ($admin, $product, $data, $storedFiles): void {
            $content = CourseContent::query()->create([
                'product_id' => $product->id,
                ...$data,
                // Private storage locations are server-generated, never supplied
                // by a form or exposed to the administration UI.
                ...$storedFiles,
            ]);

            $this->audit->record($admin, 'COURSE_CONTENT_CREATED', $content, [], $this->auditData($content));
            });
        } catch (\Throwable $exception) {
            $this->deleteStoredFiles($storedFiles);

            throw $exception;
        }

        return redirect()->route('admin.course-content.index')
            ->with('success', 'Course content and private media uploaded. Activate the course separately when it is ready for the catalogue.');
    }

    public function edit(CourseContent $courseContent): View
    {
        $courseContent = $this->safeContentQuery()
            ->with('product:id,name,slug,sku,status,type')
            ->findOrFail($courseContent->getKey());

        $media = [
            'video' => $this->mediaDetails($courseContent, 'video'),
            'slides' => $this->mediaDetails($courseContent, 'slide'),
        ];

        return view('admin.course-content.edit', compact('courseContent', 'media'));
    }

    public function update(Request $request, CourseContent $courseContent): RedirectResponse
    {
        $data = $this->validatedMetadata($request);
        /** @var \App\Models\User $admin */
        $admin = $request->user();
        $storedFiles = $this->storeUploadedFiles($request, $courseContent->product()->firstOrFail());
        $oldFiles = [];

        try {
            DB::transaction(function () use ($admin, $courseContent, $data, $storedFiles, &$oldFiles): void {
            $before = $this->auditData($courseContent);
            $oldFiles = array_intersect_key($courseContent->only(['video_url', 'slide_url']), $storedFiles);
            $courseContent->fill([...$data, ...$storedFiles]);

            if (! $courseContent->isDirty()) {
                return;
            }

            $courseContent->save();
            $this->audit->record($admin, 'COURSE_CONTENT_UPDATED', $courseContent, $before, $this->auditData($courseContent));
            });
        } catch (\Throwable $exception) {
            $this->deleteStoredFiles($storedFiles);

            throw $exception;
        }

        foreach ($oldFiles as $oldFile) {
            if (is_string($oldFile) && ! in_array($oldFile, array_intersect_key($storedFiles, array_flip(['video_url', 'slide_url'])), true)) {
                Storage::disk('local')->delete($oldFile);
            }
        }

        return redirect()->route('admin.course-content.edit', $courseContent)
            ->with('success', 'Course content updated. Any uploaded media has replaced the previous private file.');
    }

    public function videoPreview(CourseContent $courseContent): BinaryFileResponse
    {
        $courseContent = $this->contentForPreview($courseContent);
        $path = $this->privateMediaPath($courseContent, 'video');

        return response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function slidesPreview(CourseContent $courseContent): BinaryFileResponse
    {
        $courseContent = $this->contentForPreview($courseContent);
        $path = $this->privateMediaPath($courseContent, 'slide');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentType = match ($extension) {
            'pdf' => 'application/pdf',
            'ppt' => 'application/vnd.ms-powerpoint',
            default => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        };

        if ($extension === 'pdf') {
            return response()->file($path, ['Content-Type' => $contentType, 'Cache-Control' => 'private, no-store']);
        }

        return response()->download($path, $courseContent->slide_original_name ?: 'Course-Slides.'.$extension, ['Content-Type' => $contentType, 'Cache-Control' => 'private, no-store']);
    }

    /** @return array{available: bool, name: string|null, size: int|null, extension: string|null, pdf: bool} */
    private function mediaDetails(CourseContent $courseContent, string $type): array
    {
        $pathColumn = $type === 'video' ? 'video_url' : 'slide_url';
        $nameColumn = $type === 'video' ? 'video_original_name' : 'slide_original_name';
        $sizeColumn = $type === 'video' ? 'video_file_size' : 'slide_file_size';
        $relativePath = $courseContent->getAttribute($pathColumn);

        if (! is_string($relativePath) || ! Storage::disk('local')->exists($relativePath)) {
            return ['available' => false, 'name' => null, 'size' => null, 'extension' => null, 'pdf' => false];
        }

        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        return [
            'available' => true,
            'name' => $courseContent->getAttribute($nameColumn) ?: basename($relativePath),
            'size' => $courseContent->getAttribute($sizeColumn) ?: Storage::disk('local')->size($relativePath),
            'extension' => $extension,
            'pdf' => $extension === 'pdf',
        ];
    }

    private function contentForPreview(CourseContent $courseContent): CourseContent
    {
        return $this->safeContentQuery()->with('product:id,name,slug')->findOrFail($courseContent->getKey());
    }

    private function privateMediaPath(CourseContent $courseContent, string $type): string
    {
        $pathColumn = $type === 'video' ? 'video_url' : 'slide_url';
        $directory = $type === 'video' ? 'video' : 'slides';
        $extensions = $type === 'video' ? ['mp4'] : ['pdf', 'ppt', 'pptx'];
        $relativePath = $courseContent->getAttribute($pathColumn);
        $prefix = 'courses/'.$courseContent->product->slug.'/'.$directory.'/';
        $extension = is_string($relativePath) ? strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) : null;

        abort_unless(is_string($relativePath) && str_starts_with($relativePath, $prefix) && ! str_contains($relativePath, '..') && in_array($extension, $extensions, true), 404);
        abort_unless(Storage::disk('local')->exists($relativePath), 404, 'The private media file is unavailable. Upload a replacement file to restore it.');

        return Storage::disk('local')->path($relativePath);
    }

    private function ensureCourseWithoutContent(Product $product): void
    {
        if (! $product->isCourse()) {
            abort(404);
        }

        if ($product->courseContent()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'This course already has managed course metadata.',
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function validatedMetadata(Request $request, bool $creating = false): array
    {
        $data = Arr::except($request->validate([
            'video_title' => ['required', 'string', 'max:255'],
            'video_duration_seconds' => ['nullable', 'integer', 'min:0'],
            'slide_name' => ['nullable', 'string', 'max:255'],
            'lesson_content' => ['nullable', 'array'],
            'lesson_content.start' => ['nullable', 'array'],
            'lesson_content.start.title' => ['nullable', 'string', 'max:255'],
            'lesson_content.start.body' => ['nullable', 'string', 'max:5000'],
            'lesson_content.start.objectives' => ['nullable', 'array'],
            'lesson_content.start.objectives.*' => ['nullable', 'string', 'max:1000'],
            'lesson_content.full' => ['nullable', 'array'],
            'lesson_content.full.title' => ['nullable', 'string', 'max:255'],
            'lesson_content.full.body' => ['nullable', 'string', 'max:5000'],
            'lesson_content.full.topics' => ['nullable', 'array'],
            'lesson_content.full.topics.*' => ['nullable', 'string', 'max:1000'],
            'lesson_content.recap' => ['nullable', 'array'],
            'lesson_content.recap.title' => ['nullable', 'string', 'max:255'],
            'lesson_content.recap.body' => ['nullable', 'string', 'max:5000'],
            'lesson_content.recap.takeaways' => ['nullable', 'array'],
            'lesson_content.recap.takeaways.*' => ['nullable', 'string', 'max:1000'],
            'lesson_content.recap.next_steps' => ['nullable', 'array'],
            'lesson_content.recap.next_steps.*' => ['nullable', 'string', 'max:1000'],
            'video_file' => [$creating ? 'required' : 'nullable', 'file', 'mimetypes:video/mp4', 'max:65536'],
            'slide_file' => [
                'nullable',
                'file',
                'extensions:pdf,ppt,pptx',
                'mimetypes:application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/octet-stream',
                'max:51200',
            ],
        ], [
            'video_file.uploaded' => 'The video could not be uploaded. Use an MP4 file no larger than 64 MB.',
            'video_file.max' => 'The video must not be larger than 64 MB.',
            'video_file.mimetypes' => 'The video must be an MP4 file.',
            'slide_file.uploaded' => 'The slide deck could not be uploaded. Use a PDF, PPT or PPTX file no larger than 50 MB.',
            'slide_file.extensions' => 'The slide deck must use a PDF, PPT or PPTX file extension.',
            'slide_file.mimetypes' => 'The slide deck must be a valid PDF, PPT or PPTX file.',
            'slide_file.max' => 'The slide deck must not be larger than 50 MB.',
        ]), ['video_file', 'slide_file']);

        if (array_key_exists('lesson_content', $data)) {
            $data['lesson_content'] = $this->normalizeLessonContent($data['lesson_content'] ?? []);
        }

        return $data;
    }

    /** @param array<string, mixed> $lessonContent */
    private function normalizeLessonContent(array $lessonContent): array
    {
        $normalized = [];

        foreach ([
            'start' => ['objectives'],
            'full' => ['topics'],
            'recap' => ['takeaways', 'next_steps'],
        ] as $section => $lists) {
            $input = is_array($lessonContent[$section] ?? null) ? $lessonContent[$section] : [];
            $normalized[$section] = [
                'title' => trim((string) ($input['title'] ?? '')),
                'body' => trim((string) ($input['body'] ?? '')),
            ];

            foreach ($lists as $list) {
                $items = is_array($input[$list] ?? null) ? $input[$list] : [];
                $normalized[$section][$list] = array_values(array_filter(
                    array_map(static fn (mixed $item): string => trim((string) $item), $items),
                    static fn (string $item): bool => $item !== '',
                ));
            }
        }

        return $normalized;
    }

    /** @return array<string, int|string> */
    private function storeUploadedFiles(Request $request, Product $product): array
    {
        $storedFiles = [];

        if ($request->hasFile('video_file')) {
            $file = $request->file('video_file');
            $storedFiles = [
                ...$storedFiles,
                'video_url' => $this->storeFile($file, $product, 'video'),
                'video_original_name' => $file->getClientOriginalName(),
                'video_file_size' => $file->getSize(),
            ];
        }

        if ($request->hasFile('slide_file')) {
            $file = $request->file('slide_file');
            $storedFiles = [
                ...$storedFiles,
                'slide_url' => $this->storeFile($file, $product, 'slides'),
                'slide_original_name' => $file->getClientOriginalName(),
                'slide_file_size' => $file->getSize(),
            ];
        }

        return $storedFiles;
    }

    private function storeFile(?UploadedFile $file, Product $product, string $type): string
    {
        abort_unless($file instanceof UploadedFile, 422);

        return $file->storeAs(
            'courses/'.$product->slug.'/'.$type,
            Str::uuid().'.'.strtolower($file->getClientOriginalExtension()),
            'local',
        );
    }

    /** @param array<string, int|string> $storedFiles */
    private function deleteStoredFiles(array $storedFiles): void
    {
        Storage::disk('local')->delete(array_values(array_intersect_key($storedFiles, array_flip(['video_url', 'slide_url']))));
    }

    /** @return array<string, int|string|bool|null> */
    private function auditData(CourseContent $courseContent): array
    {
        $videoConfigured = array_key_exists('video_url', $courseContent->getAttributes())
            ? filled($courseContent->getAttribute('video_url'))
            : CourseContent::query()
                ->whereKey($courseContent->getKey())
                ->whereNotNull('video_url')
                ->where('video_url', '!=', '')
                ->exists();

        return [
            'id' => $courseContent->id,
            'product_id' => $courseContent->product_id,
            'video_title' => $courseContent->video_title,
            'video_provider' => $courseContent->video_provider,
            'video_duration_seconds' => $courseContent->video_duration_seconds,
            'slide_name' => $courseContent->slide_name,
            // The private storage path is never placed in audit data or passed
            // to a view; retain only an accurate availability flag.
            'content_configured' => $videoConfigured,
            'slides_configured' => array_key_exists('slide_url', $courseContent->getAttributes())
                ? filled($courseContent->getAttribute('slide_url'))
                : CourseContent::query()->whereKey($courseContent->getKey())->whereNotNull('slide_url')->where('slide_url', '!=', '')->exists(),
            'lesson_content' => $this->lessonContentAuditSummary($courseContent->lesson_content),
        ];
    }

    /** @param array<string, mixed>|null $lessonContent */
    private function lessonContentAuditSummary(?array $lessonContent): array
    {
        return [
            'start' => [
                'title' => data_get($lessonContent, 'start.title'),
                'body_present' => filled(data_get($lessonContent, 'start.body')),
                'objectives_count' => count((array) data_get($lessonContent, 'start.objectives', [])),
            ],
            'full' => [
                'title' => data_get($lessonContent, 'full.title'),
                'body_present' => filled(data_get($lessonContent, 'full.body')),
                'topics_count' => count((array) data_get($lessonContent, 'full.topics', [])),
            ],
            'recap' => [
                'title' => data_get($lessonContent, 'recap.title'),
                'body_present' => filled(data_get($lessonContent, 'recap.body')),
                'takeaways_count' => count((array) data_get($lessonContent, 'recap.takeaways', [])),
                'next_steps_count' => count((array) data_get($lessonContent, 'recap.next_steps', [])),
            ],
        ];
    }

    private function safeContentQuery()
    {
        return CourseContent::query()->select([
            'id', 'product_id', 'video_title', 'video_provider',
            'video_url', 'video_original_name', 'video_file_size', 'video_duration_seconds',
            'slide_name', 'slide_url', 'slide_original_name', 'slide_file_size', 'lesson_content',
            'created_at', 'updated_at',
        ]);
    }

    /** @return array<string, \Closure> */
    private function safeCourseContentRelation(): array
    {
        return [
            'courseContent' => fn ($query) => $query->select([
                'id', 'product_id', 'video_title', 'video_provider',
                'video_url', 'video_original_name', 'video_file_size', 'video_duration_seconds',
                'slide_name', 'slide_url', 'slide_original_name', 'slide_file_size', 'lesson_content',
                'created_at', 'updated_at',
            ]),
        ];
    }
}
