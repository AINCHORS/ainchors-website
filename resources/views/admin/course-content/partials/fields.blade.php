@php
    $editing = isset($courseContent) && $courseContent;
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="course-video-title" class="block text-sm font-semibold text-ainchors-navy">Video title</label>
        <input id="course-video-title" name="video_title" type="text" value="{{ old('video_title', $editing ? $courseContent->video_title : '') }}" maxlength="255" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('video_title')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="course-video-provider" class="block text-sm font-semibold text-ainchors-navy">Video provider</label>
        <input id="course-video-provider" name="video_provider" type="text" value="{{ old('video_provider', $editing ? $courseContent->video_provider : '') }}" maxlength="100" placeholder="For example: Vimeo" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('video_provider')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="course-video-duration" class="block text-sm font-semibold text-ainchors-navy">Video duration (seconds)</label>
        <input id="course-video-duration" name="video_duration_seconds" type="number" min="0" step="1" value="{{ old('video_duration_seconds', $editing ? $courseContent->video_duration_seconds : '') }}" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('video_duration_seconds')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="course-slide-name" class="block text-sm font-semibold text-ainchors-navy">Slide deck name</label>
        <input id="course-slide-name" name="slide_name" type="text" value="{{ old('slide_name', $editing ? $courseContent->slide_name : '') }}" maxlength="255" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('slide_name')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>

<aside class="rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 px-4 py-3 text-sm leading-relaxed text-ainchors-grey-dark"><strong class="text-ainchors-navy">Protected media stays protected.</strong> This screen manages display metadata only; video and download locations are intentionally not displayed or edited here.</aside>
