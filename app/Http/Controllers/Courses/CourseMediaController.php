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
        $path = $this->safeAssetPath($course, $content->video_url, 'video/course.mp4');

        return response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function slides(Request $request, Product $course): BinaryFileResponse
    {
        $content = $this->authorizedContent($request, $course);
        $path = $this->safeAssetPath($course, $content->slide_url, 'slides/course-slides.pptx');
        $filename = Str::of($course->name)->ascii()->replaceMatches('/[^A-Za-z0-9]+/', '-')->trim('-').'-Course-Slides.pptx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
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

    private function safeAssetPath(Product $course, ?string $relativePath, string $expectedSuffix): string
    {
        $expected = 'courses/'.$course->slug.'/'.$expectedSuffix;
        abort_unless($relativePath === $expected && ! str_contains($relativePath, '..'), 404);
        abort_unless(Storage::disk('local')->exists($relativePath), 404);

        return Storage::disk('local')->path($relativePath);
    }
}
