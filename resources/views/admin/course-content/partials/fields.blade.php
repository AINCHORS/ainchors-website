@php($editing = isset($courseContent) && $courseContent)
@php($lessonContent = old('lesson_content', $editing ? ($courseContent->lesson_content ?? []) : []))

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="course-video-title" class="block text-sm font-semibold text-ainchors-navy">Video title</label>
        <input id="course-video-title" name="video_title" type="text" value="{{ old('video_title', $editing ? $courseContent->video_title : '') }}" maxlength="255" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('video_title')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2 overflow-hidden rounded-ainchors-card border border-ainchors-navy/10 bg-slate-50 p-5">
        <label for="course-video-file" class="block text-sm font-semibold text-ainchors-navy">{{ $editing ? 'Replace course video' : 'Upload course video' }} <span class="text-red-700">{{ $editing ? '(optional)' : '*' }}</span></label>
        <p class="mt-1 text-sm text-ainchors-grey-dark">MP4 only, maximum 64 MB. Video duration is detected automatically after you choose a file.</p>
        @if ($editing && ($media['video']['available'] ?? false))
            <div class="mt-4 rounded-ainchors-button border border-ainchors-navy/10 bg-white p-4">
                <p class="text-sm font-semibold text-ainchors-navy">Current private video</p>
                <p class="mt-1 text-xs text-ainchors-grey-dark">{{ $media['video']['name'] }} · {{ $formatSize($media['video']['size']) }} · MP4</p>
                <video class="mt-3 aspect-video w-full rounded-ainchors-button bg-ainchors-navy" controls preload="metadata"><source src="{{ route('admin.course-content.video-preview', $courseContent) }}" type="video/mp4">Your browser does not support video preview.</video>
                @if ($courseContent->video_duration_seconds)
                    <p class="mt-2 text-xs text-ainchors-grey-dark">{{ $formatDuration($courseContent->video_duration_seconds) }}</p>
                @endif
            </div>
        @elseif ($editing)
            <p class="mt-3 text-sm text-red-700">No private video file is available. Select a replacement below to restore it.</p>
        @endif
        <input id="course-video-duration" name="video_duration_seconds" type="hidden" value="{{ old('video_duration_seconds', $editing ? $courseContent->video_duration_seconds : '') }}">
        <input id="course-video-file" name="video_file" type="file" accept="video/mp4,.mp4" {{ $editing ? '' : 'required' }} class="mt-3 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy file:mr-4 file:rounded-full file:border-0 file:bg-ainchors-green file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-ainchors-navy">
        <p id="course-video-duration-status" class="mt-2 text-xs text-ainchors-grey-dark"></p>
        @error('video_file')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        @error('video_duration_seconds')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="course-slide-name" class="block text-sm font-semibold text-ainchors-navy">Slide deck name <span class="font-normal text-ainchors-grey-dark">(optional)</span></label>
        <input id="course-slide-name" name="slide_name" type="text" value="{{ old('slide_name', $editing ? $courseContent->slide_name : '') }}" maxlength="255" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('slide_name')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2 overflow-hidden rounded-ainchors-card border border-ainchors-navy/10 bg-slate-50 p-5">
        <label for="course-slide-file" class="block text-sm font-semibold text-ainchors-navy">{{ $editing ? 'Replace course slides' : 'Upload course slides' }} <span class="font-normal text-ainchors-grey-dark">(optional)</span></label>
        <p class="mt-1 text-sm text-ainchors-grey-dark">PDF, PPT or PPTX, maximum 50 MB. Re-uploading replaces the existing private deck.</p>
        @if ($editing && ($media['slides']['available'] ?? false))
            <div class="mt-4 rounded-ainchors-button border border-ainchors-navy/10 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-ainchors-navy">Current private slides</p>
                        <p class="mt-1 text-xs text-ainchors-grey-dark">{{ $media['slides']['name'] }} · {{ $formatSize($media['slides']['size']) }} · {{ strtoupper($media['slides']['extension']) }}</p>
                    </div>
                    <a href="{{ route('admin.course-content.slides-preview', $courseContent) }}" class="rounded-ainchors-button border border-ainchors-green px-3.5 py-2 text-sm font-semibold text-ainchors-green">{{ $media['slides']['pdf'] ? 'Open current PDF' : 'Download current slides' }}</a>
                </div>
                @if ($media['slides']['pdf'])
                    <iframe class="mt-3 h-80 w-full rounded-ainchors-button border border-ainchors-navy/10 bg-white" src="{{ route('admin.course-content.slides-preview', $courseContent) }}" title="Current course slide preview"></iframe>
                @else
                    <p class="mt-3 text-xs leading-relaxed text-ainchors-grey-dark">PowerPoint files stay private and are supplied through the protected download above. PDF files also receive an in-page preview.</p>
                @endif
            </div>
        @elseif ($editing)
            <p class="mt-3 text-sm text-red-700">No private slide file is available.</p>
        @endif
        <input id="course-slide-file" name="slide_file" type="file" accept="application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,.pdf,.ppt,.pptx" class="mt-3 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy file:mr-4 file:rounded-full file:border-0 file:bg-ainchors-green file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-ainchors-navy">
        @error('slide_file')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>

<section class="space-y-5 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 sm:p-6" aria-labelledby="learning-page-content-heading">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Learning page</p>
        <h2 id="learning-page-content-heading" class="mt-2 font-heading text-2xl font-bold text-ainchors-navy">Learning Page Content</h2>
        <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Edit the plain-text sections shown to enrolled learners. Video and slides remain managed above.</p>
    </div>

    @foreach ([
        ['key' => 'start', 'number' => '01', 'heading' => 'Start Here', 'list' => 'objectives', 'list_label' => 'Learning Objectives', 'item_label' => 'Objective'],
        ['key' => 'full', 'number' => '02', 'heading' => 'Full Course', 'list' => 'topics', 'list_label' => 'Course Roadmap', 'item_label' => 'Topic'],
        ['key' => 'recap', 'number' => '03', 'heading' => 'Course Recap & Next Steps', 'list' => 'takeaways', 'list_label' => 'Key Takeaways', 'item_label' => 'Takeaway'],
    ] as $section)
        <fieldset class="rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 p-4 sm:p-5">
            <legend class="px-2 font-heading text-lg font-bold text-ainchors-navy">{{ $section['number'] }} {{ $section['heading'] }}</legend>
            <div class="mt-2 space-y-5">
                <div>
                    <label for="lesson-{{ $section['key'] }}-title" class="block text-sm font-semibold text-ainchors-navy">Section Title</label>
                    <input id="lesson-{{ $section['key'] }}-title" name="lesson_content[{{ $section['key'] }}][title]" type="text" maxlength="255" value="{{ data_get($lessonContent, $section['key'].'.title', $section['number'].' '.$section['heading']) }}" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                    @error('lesson_content.'.$section['key'].'.title')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="lesson-{{ $section['key'] }}-body" class="block text-sm font-semibold text-ainchors-navy">Introduction / Body</label>
                    <textarea id="lesson-{{ $section['key'] }}-body" name="lesson_content[{{ $section['key'] }}][body]" rows="4" maxlength="5000" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm leading-relaxed text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">{{ data_get($lessonContent, $section['key'].'.body') }}</textarea>
                    @error('lesson_content.'.$section['key'].'.body')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                @include('admin.course-content.partials.repeatable-list', [
                    'sectionKey' => $section['key'],
                    'listKey' => $section['list'],
                    'listLabel' => $section['list_label'],
                    'itemLabel' => $section['item_label'],
                    'items' => data_get($lessonContent, $section['key'].'.'.$section['list'], []),
                ])

                @if ($section['key'] === 'recap')
                    @include('admin.course-content.partials.repeatable-list', [
                        'sectionKey' => 'recap',
                        'listKey' => 'next_steps',
                        'listLabel' => 'Next Steps',
                        'itemLabel' => 'Step',
                        'items' => data_get($lessonContent, 'recap.next_steps', []),
                    ])
                @endif
            </div>
        </fieldset>
    @endforeach
</section>

<aside class="rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 px-4 py-3 text-sm leading-relaxed text-ainchors-grey-dark"><strong class="text-ainchors-navy">Protected media stays protected.</strong> Uploaded files are saved outside the public website folder. Their locations are never shown in Admin or shared as public URLs.</aside>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('course-video-file');
            const durationInput = document.getElementById('course-video-duration');
            const status = document.getElementById('course-video-duration-status');
            if (!input || !durationInput || !status) return;
            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) return;
                const video = document.createElement('video');
                video.preload = 'metadata';
                video.onloadedmetadata = () => {
                    durationInput.value = Math.round(video.duration).toString();
                    status.textContent = `Duration detected: ${Math.floor(video.duration / 60)} min ${Math.round(video.duration % 60)} sec`;
                    URL.revokeObjectURL(video.src);
                };
                video.onerror = () => { status.textContent = 'Duration will be available after the video is uploaded.'; };
                video.src = URL.createObjectURL(file);
            });

            document.querySelectorAll('[data-repeatable-list]').forEach((list) => {
                const rows = list.querySelector('[data-repeatable-rows]');
                const template = list.querySelector('template');
                const add = list.querySelector('[data-repeatable-add]');
                if (!rows || !template || !add) return;

                add.addEventListener('click', () => {
                    const index = Number(list.dataset.nextIndex || rows.children.length);
                    rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index.toString()));
                    list.dataset.nextIndex = (index + 1).toString();
                    rows.lastElementChild?.querySelector('input')?.focus();
                });

                list.addEventListener('click', (event) => {
                    const remove = event.target.closest('[data-repeatable-remove]');
                    if (remove) remove.closest('[data-repeatable-row]')?.remove();
                });
            });
        });
    </script>
@endonce
