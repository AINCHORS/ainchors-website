@extends('layouts.app')

@section('title', 'My Courses | AINCHORS')

@section('content')
<section class="catalogue-hero compact-hero my-courses-hero"><div class="site-shell"><span class="eyebrow">Your account</span><h1>My Learning</h1><p>Access the AINCHORS courses currently enrolled in your account.</p></div></section>
<section class="section catalogue-section my-courses-page">
    <div class="site-shell">
        <form method="GET" action="{{ route('my-courses') }}" class="my-courses-filter" aria-label="Filter enrolled courses">
            <div>
                <label for="my-courses-category">Course category</label>
                <select id="my-courses-category" name="course_category">
                    <option value="">All categories</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="primary-button" type="submit">Filter</button>
            @if ($category !== '')
                <a class="secondary-button" href="{{ route('my-courses') }}">Clear</a>
            @endif
        </form>

        @if ($enrollments->isEmpty())
            <div class="empty-state"><h2>{{ $category !== '' ? 'No courses in this category' : 'No courses yet' }}</h2><p>{{ $category !== '' ? 'Choose another category to view your enrolled courses.' : 'Your purchased courses will appear here.' }}</p>@if ($category === '')<a class="primary-button" href="{{ route('courses.index') }}">Browse Courses</a>@endif</div>
        @else
            <div class="my-courses-grid">
                @foreach ($enrollments as $enrollment)
                    <article class="course-card compact-card">
                        <img src="{{ asset($enrollment->product->image) }}" alt="{{ $enrollment->product->name }}">
                        <div><span class="course-label">Enrolled</span><h2>{{ $enrollment->product->name }}</h2><a class="primary-button" href="{{ route('learn.show', $enrollment->product) }}">Access Course</a></div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
