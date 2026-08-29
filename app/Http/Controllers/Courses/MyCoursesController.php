<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Courses\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyCoursesController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollments) {}

    public function __invoke(Request $request): View
    {
        $category = (string) $request->input('course_category', '');
        $category = array_key_exists($category, Product::COURSE_CATEGORIES) ? $category : '';
        $enrollments = $this->enrollments->activeFor($request->user())
            ->filter(fn ($enrollment) => $enrollment->product?->isCourse())
            ->when($category !== '', fn ($items) => $items->filter(
                fn ($enrollment) => $enrollment->product?->course_category === $category,
            ));
        $categories = Product::COURSE_CATEGORIES;

        return view('courses.my-courses', compact('enrollments', 'categories', 'category'));
    }
}
