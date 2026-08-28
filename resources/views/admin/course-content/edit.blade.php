@extends('layouts.admin')

@section('title', 'Edit Course Content | AINCHORS Admin')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('admin.course-content.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy">← Course content</a>
        <div class="mt-5 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Learning management</p>
            <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy">Edit course content</h1>
            <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">{{ $courseContent->product?->name ?? 'Selected course' }}</p>

            @php
                $formatSize = static fn (?int $bytes): string => $bytes === null ? 'Size unavailable' : match (true) {
                    $bytes >= 1073741824 => number_format($bytes / 1073741824, 1).' GB',
                    $bytes >= 1048576 => number_format($bytes / 1048576, 1).' MB',
                    $bytes >= 1024 => number_format($bytes / 1024, 1).' KB',
                    default => $bytes.' bytes',
                };
                $formatDuration = static fn (?int $seconds): string => $seconds === null ? 'Duration unavailable' : sprintf('%d h %02d min %02d sec', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
            @endphp

            <form method="POST" action="{{ route('admin.course-content.update', $courseContent) }}" enctype="multipart/form-data" class="mt-7 space-y-7">
                @csrf
                @method('PUT')
                @include('admin.course-content.partials.fields', ['courseContent' => $courseContent, 'media' => $media, 'formatSize' => $formatSize, 'formatDuration' => $formatDuration])
                <div class="flex flex-wrap justify-end gap-3 border-t border-ainchors-navy/10 pt-6">
                    <a href="{{ route('admin.course-content.index') }}" class="rounded-ainchors-button border border-ainchors-navy/15 px-4 py-2.5 text-sm font-semibold text-ainchors-grey-dark">Cancel</a>
                    <button type="submit" class="rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy">Save course content</button>
                </div>
            </form>
        </div>
    </div>
@endsection
