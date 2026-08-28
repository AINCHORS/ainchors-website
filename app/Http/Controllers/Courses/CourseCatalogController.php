<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Commerce\CheckoutService;
use App\Services\Courses\CourseAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseCatalogController extends Controller
{
    public function __construct(
        private readonly CourseAccessService $access,
        private readonly CheckoutService $checkout,
    ) {}

    public function index(Request $request): View
    {
        $courses = Product::query()->where('type', 'course')->where('status', 'active')->orderBy('id')->get();
        $package = Product::query()->where('type', 'course_package')->where('status', 'active')->with('bundleProducts')->first();
        $ownedCourseIds = $request->user()
            ? $request->user()->enrollments()->where('status', 'active')->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->pluck('product_id')->all()
            : [];
        $packageOwned = $request->user() && $package
            ? $this->checkout->isFullyOwned($request->user(), $package)
            : false;

        return view('courses.index', compact('courses', 'package', 'ownedCourseIds', 'packageOwned'));
    }

    public function show(Request $request, Product $course): View
    {
        abort_unless($course->isCourse() && $course->status === 'active', 404);
        $owned = $request->user() ? $this->access->canAccess($request->user(), $course) : false;

        return view('courses.show', ['product' => $course, 'owned' => $owned]);
    }

    public function package(Request $request, Product $package): View
    {
        abort_unless($package->isPackage() && $package->status === 'active', 404);
        $package->load('bundleProducts');
        $owned = $request->user() ? $this->checkout->isFullyOwned($request->user(), $package) : false;

        return view('courses.show', ['product' => $package, 'owned' => $owned]);
    }
}
