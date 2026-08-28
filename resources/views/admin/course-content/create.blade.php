@extends('layouts.admin')

@section('title', 'Add Course Content | AINCHORS Admin')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('admin.course-content.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy">← Course content</a>
        <div class="mt-5 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Learning management</p>
            <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy">Add course content</h1>
            <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Choose a course, upload its private MP4 video and optional slide deck, then record the learning details.</p>
            <form method="POST" action="{{ route('admin.course-content.store') }}" enctype="multipart/form-data" class="mt-7 space-y-7">
                @csrf
                <div>
                    <label for="course-content-product" class="block text-sm font-semibold text-ainchors-navy">Course</label>
                    <select id="course-content-product" name="product_id" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                        <option value="">Select a course</option>
                        @foreach ($courses as $course)<option value="{{ $course->id }}" @selected((string) old('product_id', request('product_id')) === (string) $course->id)>{{ $course->name }}</option>@endforeach
                    </select>
                    @error('product_id')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                @include('admin.course-content.partials.fields', ['courseContent' => null])
                <div class="flex flex-wrap justify-end gap-3 border-t border-ainchors-navy/10 pt-6">
                    <a href="{{ route('admin.course-content.index') }}" class="rounded-ainchors-button border border-ainchors-navy/15 px-4 py-2.5 text-sm font-semibold text-ainchors-grey-dark">Cancel</a>
                    <button type="submit" class="rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy">Add course content</button>
                </div>
            </form>
        </div>
    </div>
@endsection
