@extends('layouts.app')

@section('title', 'My Courses | AINCHORS')

@section('content')
<section class="catalogue-hero compact-hero"><div class="site-shell"><span class="eyebrow">Your account</span><h1>My Learning</h1><p>Access the AINCHORS courses currently enrolled in your account.</p></div></section>
<section class="section catalogue-section">
    <div class="site-shell">
        @if ($enrollments->isEmpty())
            <div class="empty-state"><h2>No courses yet</h2><p>Your purchased courses will appear here.</p><a class="primary-button" href="{{ route('courses.index') }}">Browse Courses</a></div>
        @else
            <div class="course-grid">
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
