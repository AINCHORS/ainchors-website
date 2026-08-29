@extends('layouts.app')

@section('title', 'Self-Learning Courses | AINCHORS')

@section('content')
<section class="catalogue-hero">
    <div class="site-shell narrow-shell">
        <span class="eyebrow">Learn at your pace</span>
        <h1>Training Course</h1>
        <p>Explore the ten AINCHORS video courses and access your learning from one secure account.</p>
    </div>
</section>

<section class="section catalogue-section">
    <div class="site-shell">
        @if ($package)
            <article class="package-banner">
                <div>
                    <span class="eyebrow">All 10 courses</span>
                    <h2>{{ $package->name }}</h2>
                    <p>{{ $package->description }}</p>
                    <div class="price-line"><del>USD {{ number_format($package->listPrice(), 0) }}</del><strong>USD {{ number_format((float) $package->price, 0) }}</strong></div>
                </div>
                <a class="primary-button" href="{{ $packageOwned ? route('my-courses') : route('packages.show', $package) }}">
                    {{ $packageOwned ? 'ACCESS MY COURSES' : 'VIEW PACKAGE' }}
                </a>
            </article>
        @endif

        @php
            $courseCategories = \App\Models\Product::COURSE_CATEGORIES;
            $uncategorisedCourses = $courses->whereNull('course_category');
            if ($uncategorisedCourses->isNotEmpty()) $courseCategories[''] = 'More Courses';
        @endphp

        @foreach ($courseCategories as $categoryValue => $categoryName)
            @php($categoryCourses = $categoryValue === '' ? $uncategorisedCourses : $courses->where('course_category', $categoryValue))
            @if ($categoryCourses->isNotEmpty())
                <section class="course-category" aria-labelledby="course-category-{{ $loop->index }}">
                    <h2 id="course-category-{{ $loop->index }}" class="course-category-heading">{{ $categoryName }}</h2>
                    <div class="course-carousel" data-course-carousel aria-label="{{ $categoryName }} carousel">
                        <div class="course-carousel-viewport" data-carousel-viewport tabindex="0">
                            <div class="course-carousel-track" data-carousel-track>
                        @foreach ($categoryCourses as $course)
                            @php($owned = in_array($course->id, $ownedCourseIds, true))
                                    <div class="course-carousel-item" data-carousel-card>
                                        <x-course-card
                                            :image="$course->image ? asset($course->image) : null"
                                            :title="$course->name"
                                            :description="$course->short_description"
                                            :price-original="number_format($course->listPrice(), 0)"
                                            :price-current="number_format((float) $course->price, 0)"
                                            :href="$owned ? route('learn.show', $course) : route('courses.show', $course)"
                                            :button-label="$owned ? 'Access Course' : 'View Course'"
                                        />
                                    </div>
                        @endforeach
                            </div>
                        </div>

                        <div class="course-carousel-navigation" data-carousel-navigation hidden>
                            <button type="button" class="course-carousel-arrow course-carousel-previous" data-carousel-previous aria-label="Previous courses">
                                <span aria-hidden="true">←</span>
                            </button>
                            <div class="course-carousel-pagination" data-carousel-pagination aria-label="Course carousel pagination"></div>
                            <button type="button" class="course-carousel-arrow course-carousel-next" data-carousel-next aria-label="Next courses">
                                <span aria-hidden="true">→</span>
                            </button>
                        </div>
                        <p class="sr-only" data-carousel-status aria-live="polite"></p>
                    </div>
                </section>
            @endif
        @endforeach
    </div>
</section>
@endsection
