@extends('layouts.admin')

@section('title', 'Package Courses — '.$product->name.' | AINCHORS Admin')

@section('content')
    @php($bundleRelations = $product->childRelations->where('relation_type', 'bundle_item')->sortBy('sort_order')->values())

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('admin.products.show', $product) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>Product management</a>
            <p class="mt-4 text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Package management</p>
            <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Package courses</h1>
            <p class="mt-2 text-sm text-ainchors-grey-dark">{{ $product->name }} · {{ $product->sku }}</p>
        </div>
        <span class="inline-flex self-start rounded-full bg-ainchors-green-hero px-3 py-1.5 text-xs font-bold text-ainchors-navy sm:self-auto">{{ $bundleRelations->count() }} course{{ $bundleRelations->count() === 1 ? '' : 's' }}</span>
    </div>

    @error('package')<p class="mb-5 rounded-ainchors-button bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror
    @error('course_id')<p class="mb-5 rounded-ainchors-button bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror
    @error('positions')<p class="mb-5 rounded-ainchors-button bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror
    @if ($errors->has('positions.*'))<p class="mb-5 rounded-ainchors-button bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('positions.*') }}</p>@endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section aria-labelledby="included-courses-heading" class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
            <h2 id="included-courses-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Included courses</h2>
            <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Set one unique position per course, then save the complete order once.</p>

            @if ($bundleRelations->isNotEmpty())
                <form method="POST" action="{{ route('admin.package-members.reorder', $product) }}" class="mt-6">
                    @csrf
                    @method('PATCH')
                    <div class="space-y-3">
                        @foreach ($bundleRelations as $relation)
                            @php($course = $relation->childProduct)
                            @php($contentReady = filled($course?->courseContent?->video_url))
                            <div class="grid gap-4 rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 p-4 sm:grid-cols-[5rem_minmax(0,1fr)_auto] sm:items-center">
                                <div><label for="package-position-{{ $relation->child_product_id }}" class="block text-xs font-bold uppercase tracking-wide text-ainchors-grey-dark">Position</label><input id="package-position-{{ $relation->child_product_id }}" name="positions[{{ $relation->child_product_id }}]" type="number" min="1" max="9999" value="{{ old('positions.'.$relation->child_product_id, $relation->sort_order) }}" required class="mt-1.5 w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-2.5 py-2 text-sm focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"></div>
                                <div><h3 class="font-semibold text-ainchors-navy">{{ $course?->name ?? 'Course unavailable' }}</h3><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $course?->sku ?? '—' }}</p><div class="mt-2 flex flex-wrap gap-2">@if ($course) @include('admin.partials.status-badge', ['status' => $course->status]) @endif<span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $contentReady ? 'bg-ainchors-green/10 text-ainchors-green' : 'bg-amber-100 text-amber-800' }}">{{ $contentReady ? 'Content ready' : 'Content incomplete' }}</span></div></div>
                                <div class="sm:text-right">@if ($course)<button type="submit" form="remove-package-course-{{ $course->id }}" class="text-sm font-semibold text-red-700 transition hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500">Remove</button>@endif</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 flex justify-end"><button type="submit" class="rounded-ainchors-button bg-ainchors-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Save order</button></div>
                </form>
                @foreach ($bundleRelations as $relation)
                    @if ($relation->childProduct)<form id="remove-package-course-{{ $relation->childProduct->id }}" method="POST" action="{{ route('admin.package-members.destroy', [$product, $relation->childProduct]) }}" class="hidden">@csrf @method('DELETE')</form>@endif
                @endforeach
            @else
                <div class="mt-6 rounded-ainchors-button border border-dashed border-ainchors-grey-light/50 bg-slate-50 px-5 py-10 text-center"><p class="font-semibold text-ainchors-navy">No courses are currently included.</p><p class="mt-1 text-sm text-ainchors-grey-dark">Use Add course to begin configuring this package.</p></div>
            @endif
        </section>

        <aside class="self-start rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm">
            <h2 class="font-heading text-2xl font-bold text-ainchors-navy">Add course</h2>
            <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">A course can be included only once. Active packages accept active courses with protected content.</p>
            <form method="POST" action="{{ route('admin.package-members.store', $product) }}" class="mt-5 space-y-4">
                @csrf
                <div><label for="package-course" class="block text-sm font-semibold text-ainchors-navy">Course</label><select id="package-course" name="course_id" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"><option value="">Select a course</option>@foreach ($availableCourses as $course)<option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->name }} — {{ str($course->status)->headline() }}{{ filled($course->courseContent?->video_url) ? '' : ' — content incomplete' }}</option>@endforeach</select></div>
                <button type="submit" @disabled($availableCourses->isEmpty()) class="w-full rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition enabled:hover:bg-ainchors-navy disabled:cursor-not-allowed disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Add course</button>
            </form>
            @if ($availableCourses->isEmpty())<p class="mt-4 text-xs leading-relaxed text-ainchors-grey-dark">Every available course is already included.</p>@endif
        </aside>
    </div>
@endsection
