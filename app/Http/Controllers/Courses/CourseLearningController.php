<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Courses\CourseAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourseLearningController extends Controller
{
    public function __construct(private readonly CourseAccessService $access) {}

    public function __invoke(Request $request, Product $course): View|RedirectResponse
    {
        abort_unless($course->isCourse(), 404);

        if (! $this->access->canAccess($request->user(), $course)) {
            return redirect()->route('courses.show', $course)->with('error', 'Purchase this course to access the learning materials.');
        }

        $course->load('courseContent');
        $content = $course->courseContent;
        $videoAvailable = $content && Storage::disk('local')->exists($content->video_url);
        $slidesAvailable = $content && $content->slide_url && Storage::disk('local')->exists($content->slide_url);

        return view('courses.learn', compact('course', 'content', 'videoAvailable', 'slidesAvailable'));
    }
}
