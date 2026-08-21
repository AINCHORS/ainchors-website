<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseContent;
use App\Models\Product;
use App\Services\Admin\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseContentController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $courses = Product::query()
            ->select(['id', 'name', 'slug', 'sku', 'status', 'created_at', 'updated_at'])
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
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.course-content.index', compact('courses'));
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
        $data = $this->validatedMetadata($request);

        /** @var \App\Models\User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $product, $data): void {
            $content = CourseContent::query()->create([
                'product_id' => $product->id,
                ...$data,
                // Private storage locations are server-generated, never supplied
                // by a form or exposed to the administration UI.
                'video_url' => $this->videoPath($product),
                'slide_url' => $this->slidePath($product),
            ]);

            $this->audit->record($admin, 'COURSE_CONTENT_CREATED', $content, [], $this->auditData($content));
        });

        return redirect()->route('admin.course-content.index')
            ->with('success', 'Course metadata configured. Activate the course separately when it is ready for the catalogue.');
    }

    public function edit(CourseContent $courseContent): View
    {
        $courseContent = $this->safeContentQuery()
            ->with('product:id,name,slug,sku,status,type')
            ->findOrFail($courseContent->getKey());

        return view('admin.course-content.edit', compact('courseContent'));
    }

    public function update(Request $request, CourseContent $courseContent): RedirectResponse
    {
        $data = $this->validatedMetadata($request);
        /** @var \App\Models\User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $courseContent, $data): void {
            $before = $this->auditData($courseContent);
            $courseContent->fill($data);

            if (! $courseContent->isDirty()) {
                return;
            }

            $courseContent->save();
            $this->audit->record($admin, 'COURSE_CONTENT_UPDATED', $courseContent, $before, $this->auditData($courseContent));
        });

        return redirect()->route('admin.course-content.edit', $courseContent)
            ->with('success', 'Course metadata updated.');
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
    private function validatedMetadata(Request $request): array
    {
        return $request->validate([
            'video_title' => ['required', 'string', 'max:255'],
            'video_provider' => ['nullable', 'string', 'max:100'],
            'video_duration_seconds' => ['nullable', 'integer', 'min:0'],
            'slide_name' => ['nullable', 'string', 'max:255'],
            'lesson_content' => ['nullable', 'array'],
        ]);
    }

    private function videoPath(Product $product): string
    {
        return 'courses/'.$product->slug.'/video/course.mp4';
    }

    private function slidePath(Product $product): string
    {
        return 'courses/'.$product->slug.'/slides/course-slides.pptx';
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
        ];
    }

    private function safeContentQuery()
    {
        return CourseContent::query()->select([
            'id', 'product_id', 'video_title', 'video_provider',
            'video_duration_seconds', 'slide_name', 'lesson_content',
            'created_at', 'updated_at',
        ]);
    }

    /** @return array<string, \Closure> */
    private function safeCourseContentRelation(): array
    {
        return [
            'courseContent' => fn ($query) => $query->select([
                'id', 'product_id', 'video_title', 'video_provider',
                'video_duration_seconds', 'slide_name', 'lesson_content',
                'created_at', 'updated_at',
            ]),
        ];
    }
}
