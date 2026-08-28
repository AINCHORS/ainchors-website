@extends('layouts.admin')

@section('title', 'Course Content | AINCHORS Admin')

@section('content')
    @php
        $formatSize = static fn (?int $bytes): string => $bytes === null ? 'Size unavailable' : match (true) {
            $bytes >= 1073741824 => number_format($bytes / 1073741824, 1).' GB',
            $bytes >= 1048576 => number_format($bytes / 1048576, 1).' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 1).' KB',
            default => $bytes.' bytes',
        };
        $statusLabel = static fn (array $item): string => $item['available'] ? 'Available' : ($item['configured'] ? 'File missing' : 'Not configured');
        $statusClass = static fn (array $item): string => $item['available']
            ? 'border-ainchors-green/25 bg-ainchors-green/10 text-ainchors-green'
            : ($item['configured'] ? 'border-red-200 bg-red-50 text-red-700' : 'border-ainchors-grey-light/30 bg-slate-100 text-ainchors-grey-dark');
    @endphp

    <div class="mb-8">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Learning management</p>
        <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Course content</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ainchors-grey-dark">Review each course's private video and slide readiness. Protected storage locations remain hidden.</p>
    </div>

    <section aria-labelledby="course-content-heading">
        <h2 id="course-content-heading" class="sr-only">Course media readiness</h2>

        <div class="space-y-4 lg:hidden">
            @forelse ($courses as $course)
                @php($courseContent = $course->courseContent)
                @php($media = $mediaByCourse->get($course->id))
                <article class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-heading text-lg font-bold text-ainchors-navy">{{ $course->name }}</h3>
                            <p class="mt-1 text-xs font-semibold tracking-wide text-ainchors-grey-dark">{{ $course->sku }}</p>
                        </div>
                        @if ($courseContent)
                            <a href="{{ route('admin.course-content.edit', $courseContent) }}" class="shrink-0 rounded-ainchors-button bg-ainchors-green px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Edit</a>
                        @else
                            <a href="{{ route('admin.course-content.create', ['product_id' => $course->id]) }}" class="shrink-0 rounded-ainchors-button bg-ainchors-green px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Add</a>
                        @endif
                    </div>

                    <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach (['video' => 'Video', 'slides' => 'Slides'] as $key => $label)
                            @php($item = $media[$key])
                            <div class="rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 p-4">
                                <dt class="flex items-center justify-between gap-3 text-sm font-semibold text-ainchors-navy">
                                    <span>{{ $label }}</span>
                                    <span class="rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $statusClass($item) }}">{{ $statusLabel($item) }}</span>
                                </dt>
                                <dd class="mt-2 text-xs leading-relaxed text-ainchors-grey-dark">
                                    @if ($item['available'])
                                        {{ $item['name'] }}<br>{{ strtoupper($item['extension']) }} · {{ $formatSize($item['size']) }}
                                    @else
                                        {{ $item['configured'] ? 'The database record exists, but the private file cannot be found.' : 'No managed file has been added.' }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($courseContent?->updated_at)
                        <p class="mt-4 text-xs text-ainchors-grey-dark">Updated {{ $courseContent->updated_at->format('d M Y, H:i') }}</p>
                    @endif
                </article>
            @empty
                <div class="rounded-ainchors-card border border-ainchors-navy/10 bg-white px-5 py-12 text-center shadow-sm">
                    <p class="font-semibold text-ainchors-navy">No course products are available.</p>
                    <p class="mt-1 text-sm text-ainchors-grey-dark">Create a course product before adding course content.</p>
                </div>
            @endforelse
        </div>

        <div class="hidden overflow-hidden rounded-ainchors-card border border-ainchors-navy/10 bg-white shadow-sm lg:block">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[64rem] table-fixed text-left text-sm">
                    <caption class="sr-only">Courses and the availability of their private video and slide files</caption>
                    <thead class="border-b border-ainchors-navy/10 bg-slate-50 text-xs uppercase tracking-wide text-ainchors-grey-dark">
                        <tr>
                            <th scope="col" class="w-[28%] px-5 py-3.5 font-bold">Course</th>
                            <th scope="col" class="w-[25%] px-5 py-3.5 font-bold">Video</th>
                            <th scope="col" class="w-[25%] px-5 py-3.5 font-bold">Slides</th>
                            <th scope="col" class="w-[13%] px-5 py-3.5 font-bold">Updated</th>
                            <th scope="col" class="w-[9%] px-5 py-3.5 text-right font-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ainchors-navy/10">
                        @forelse ($courses as $course)
                            @php($courseContent = $course->courseContent)
                            @php($media = $mediaByCourse->get($course->id))
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-5 py-5">
                                    <p class="font-semibold leading-snug text-ainchors-navy">{{ $course->name }}</p>
                                    <p class="mt-1 text-xs font-semibold tracking-wide text-ainchors-grey-dark">{{ $course->sku }}</p>
                                </td>
                                @foreach (['video' => 'Video', 'slides' => 'Slides'] as $key => $label)
                                    @php($item = $media[$key])
                                    <td class="px-5 py-5">
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $statusClass($item) }}">{{ $statusLabel($item) }}</span>
                                        @if ($item['available'])
                                            <p class="mt-2 break-words text-xs font-medium text-ainchors-navy">{{ $item['name'] }}</p>
                                            <p class="mt-1 text-xs text-ainchors-grey-dark">{{ strtoupper($item['extension']) }} · {{ $formatSize($item['size']) }}</p>
                                        @elseif ($item['configured'])
                                            <p class="mt-2 text-xs leading-relaxed text-red-700">Database record exists; private file not found.</p>
                                        @else
                                            <p class="mt-2 text-xs text-ainchors-grey-dark">No managed file.</p>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-5 py-5 text-xs leading-relaxed text-ainchors-grey-dark">{{ $courseContent?->updated_at?->format('d M Y') ?? '—' }}</td>
                                <td class="px-5 py-5 text-right">
                                    @if ($courseContent)
                                        <a href="{{ route('admin.course-content.edit', $courseContent) }}" class="inline-flex rounded-ainchors-button border border-ainchors-green px-3.5 py-2 text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-white focus:outline-none focus:ring-2 focus:ring-ainchors-green">Edit</a>
                                    @else
                                        <a href="{{ route('admin.course-content.create', ['product_id' => $course->id]) }}" class="inline-flex rounded-ainchors-button border border-ainchors-green px-3.5 py-2 text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-white focus:outline-none focus:ring-2 focus:ring-ainchors-green">Add</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center"><p class="font-semibold text-ainchors-navy">No course products are available.</p><p class="mt-1 text-sm text-ainchors-grey-dark">Create a course product before adding course content.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @if (method_exists($courses, 'links'))
        <div class="mt-6">{{ $courses->onEachSide(1)->links() }}</div>
    @endif
@endsection
