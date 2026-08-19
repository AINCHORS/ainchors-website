@extends('layouts.app')

@section('title', 'Self-Learning Courses | AINCHORS')

@section('content')
<section class="catalogue-hero">
    <div class="site-shell narrow-shell">
        <span class="eyebrow">Learn at your pace</span>
        <h1>AINCHORS Self-Learning Courses</h1>
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

        <div class="course-grid">
            @foreach ($courses as $course)
                @php($owned = in_array($course->id, $ownedCourseIds, true))
                <x-course-card
                    :image="asset($course->image)"
                    :title="$course->name"
                    :description="$course->short_description"
                    :price-original="number_format($course->listPrice(), 0)"
                    :price-current="number_format((float) $course->price, 0)"
                    :href="$owned ? route('learn.show', $course) : route('courses.show', $course)"
                    :button-label="$owned ? 'ACCESS COURSE' : 'VIEW COURSE'"
                />
            @endforeach
        </div>
    </div>
</section>
@endsection
