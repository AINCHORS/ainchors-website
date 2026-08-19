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
                <article class="course-card">
                    <img src="{{ asset($course->image) }}" alt="{{ $course->name }}">
                    <div>
                        <span class="course-label">Self-learning course</span>
                        <h2>{{ $course->name }}</h2>
                        <p>{{ $course->short_description }}</p>
                        <div class="price-line"><del>USD {{ number_format($course->listPrice(), 0) }}</del><strong>USD {{ number_format((float) $course->price, 0) }}</strong></div>
                        <a class="primary-button" href="{{ $owned ? route('learn.show', $course) : route('courses.show', $course) }}">
                            {{ $owned ? 'ACCESS COURSE' : 'VIEW COURSE' }}
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endsection
