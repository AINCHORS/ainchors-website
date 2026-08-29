<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Courses\CourseAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourseMediaController extends Controller
{
    public function __construct(private readonly CourseAccessService $access) {}

    public function video(Request $request, Product $course): BinaryFileResponse
    {
        $content = $this->authorizedContent($request, $course);
        $path = $this->safeAssetPath($course, $content->video_url, 'video', ['mp4']);

        return response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function slides(Request $request, Product $course): BinaryFileResponse
    {
        $content = $this->authorizedContent($request, $course);
        $path = $this->safeAssetPath($course, $content->slide_url, 'slides', ['pdf', 'ppt', 'pptx']);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $downloadName = Str::of($content->slide_name ?: $course->name.' Course Slides')
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/', '-')
            ->trim('-');
        $filename = ($downloadName->isNotEmpty() ? $downloadName : Str::of($course->slug.'-Course-Slides')).'.'.$extension;
        $contentType = match ($extension) {
            'pdf' => 'application/pdf',
            'ppt' => 'application/vnd.ms-powerpoint',
            default => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        };

        return response()->download($path, $filename, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function authorizedContent(Request $request, Product $course): mixed
    {
        abort_unless($course->isCourse(), 404);
        abort_unless($this->access->canAccess($request->user(), $course), 403, 'An active enrollment is required.');
        $content = $course->courseContent()->firstOrFail();

        return $content;
    }

    /** @param array<int, string> $extensions */
    private function safeAssetPath(Product $course, ?string $relativePath, string $directory, array $extensions): string
    {
        abort_unless(is_string($relativePath) && ! str_contains($relativePath, '..'), 404);
        $prefix = 'courses/'.$course->slug.'/'.$directory.'/';
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        abort_unless(str_starts_with($relativePath, $prefix) && in_array($extension, $extensions, true), 404);
        abort_unless(Storage::disk('local')->exists($relativePath), 404);

        return Storage::disk('local')->path($relativePath);
    }
}
