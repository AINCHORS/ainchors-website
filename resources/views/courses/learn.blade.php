@extends('layouts.app')

@section('title', $course->name.' | My Learning')

@section('content')
<section class="learning-hero"><div class="site-shell"><a href="{{ route('my-courses') }}">← My Courses</a><span class="eyebrow">AINCHORS Learning</span><h1>{{ $course->name }}</h1></div></section>
<section class="learning-section">
    <div class="site-shell learning-layout">
        @php($lesson = $content?->lesson_content ?? [])
        <nav class="lesson-nav" aria-label="Course sections">
            <a href="#start">01 Start Here</a><a href="#full-course">02 Full Course</a><a href="#recap">03 Course Recap &amp; Next Steps</a>
        </nav>
        <div class="lesson-content">
            <article id="start" class="lesson-panel">
                <span>01</span><h2>{{ data_get($lesson, 'start.title', '01 Start Here') }}</h2><p>{{ data_get($lesson, 'start.body') }}</p>
                <h3>Learning objectives</h3><ul>@foreach (data_get($lesson, 'start.objectives', []) as $item)<li>{{ $item }}</li>@endforeach</ul>
            </article>
            <article id="full-course" class="lesson-panel">
                <span>02</span><h2>{{ data_get($lesson, 'full.title', '02 Full Course') }}</h2><p>{{ data_get($lesson, 'full.body') }}</p>
                <h3>Course roadmap</h3><ul>@foreach (data_get($lesson, 'full.topics', []) as $item)<li>{{ $item }}</li>@endforeach</ul>
                @if ($videoAvailable)
                    <video class="course-video" controls preload="metadata"><source src="{{ route('course-media.video', $course) }}" type="video/mp4">Your browser does not support HTML5 video.</video>
                @else
                    <div class="asset-unavailable">Course video coming soon</div>
                @endif
                @if ($slidesAvailable)<a class="secondary-button" href="{{ route('course-media.slides', $course) }}">Download Course Slides</a>@endif
            </article>
            <article id="recap" class="lesson-panel">
                <span>03</span><h2>{{ data_get($lesson, 'recap.title', '03 Course Recap & Next Steps') }}</h2><p>{{ data_get($lesson, 'recap.body') }}</p>
                <h3>Key takeaways</h3><ul>@foreach (data_get($lesson, 'recap.takeaways', []) as $item)<li>{{ $item }}</li>@endforeach</ul>
                <h3>Next steps</h3><ol>@foreach (data_get($lesson, 'recap.next_steps', []) as $item)<li>{{ $item }}</li>@endforeach</ol>
            </article>
        </div>
    </div>
</section>
@endsection
