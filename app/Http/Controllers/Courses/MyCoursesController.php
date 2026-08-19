<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Services\Courses\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyCoursesController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollments) {}

    public function __invoke(Request $request): View
    {
        $enrollments = $this->enrollments->activeFor($request->user())
            ->filter(fn ($enrollment) => $enrollment->product?->isCourse());

        return view('courses.my-courses', compact('enrollments'));
    }
}
