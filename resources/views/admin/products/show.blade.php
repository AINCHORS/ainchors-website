@extends('layouts.admin')

@section('title', $product->name.' | AINCHORS Admin')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>All products</a>
            <div class="mt-4 flex flex-wrap items-center gap-3"><h1 class="font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">{{ $product->name }}</h1>@include('admin.partials.status-badge', ['status' => $product->status])</div>
            <p class="mt-2 text-sm text-ainchors-grey-dark">{{ $product->sku }} · {{ str($product->type)->replace('_', ' ')->headline() }}</p>
        </div>
        <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex items-center justify-center rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Edit product</a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)]">
        <section aria-labelledby="product-description-heading" class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm">
            <h2 id="product-description-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Catalogue copy</h2>
            <p class="mt-5 whitespace-pre-line text-sm leading-relaxed text-ainchors-grey-dark">{{ $product->description ?: ($product->short_description ?: 'No catalogue description has been added.') }}</p>
        </section>
        <section aria-labelledby="product-details-heading" class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm">
            <h2 id="product-details-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Product details</h2>
            <dl class="mt-5 divide-y divide-ainchors-navy/8 text-sm">
                <div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Price</dt><dd class="text-right font-semibold text-ainchors-navy">{{ $product->price === null ? 'Custom' : $product->currency.' '.number_format((float) $product->price, 2) }}</dd></div>
                <div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Billing</dt><dd class="text-right text-ainchors-navy">{{ str($product->billing_type)->replace('_', ' ')->headline() }}</dd></div>
                <div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Slug</dt><dd class="max-w-[13rem] break-words text-right text-ainchors-navy">{{ $product->slug }}</dd></div>
                <div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Last updated</dt><dd class="text-right text-ainchors-navy">{{ $product->updated_at?->format('j M Y, H:i') ?? '—' }}</dd></div>
            </dl>
        </section>
    </div>

    @if ($product->type === 'course')
        <section aria-labelledby="course-content-heading" class="mt-6 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 id="course-content-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Course content metadata</h2><p class="mt-1 text-sm leading-relaxed text-ainchors-grey-dark">Manage descriptive metadata without exposing protected media locations.</p></div>
                @if ($product->courseContent)
                    <a href="{{ route('admin.course-content.edit', $product->courseContent) }}" class="inline-flex justify-center rounded-ainchors-button border border-ainchors-green px-4 py-2.5 text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-white focus:outline-none focus:ring-2 focus:ring-ainchors-green">Edit course content</a>
                @else
                    <a href="{{ route('admin.course-content.create', ['product_id' => $product->id]) }}" class="inline-flex justify-center rounded-ainchors-button border border-ainchors-green px-4 py-2.5 text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-white focus:outline-none focus:ring-2 focus:ring-ainchors-green">Add course content</a>
                @endif
            </div>
        </section>
    @endif

    @if ($product->type === 'course_package')
        @php
            $bundleRelations = $product->childRelations->where('relation_type', 'bundle_item')->sortBy('sort_order')->values();
        @endphp
        <section aria-labelledby="package-membership-heading" class="mt-6 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 id="package-membership-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Package courses</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-relaxed text-ainchors-grey-dark">Add, remove and reorder the courses included in this package. An active package cannot be left empty.</p>
                </div>
                <span class="rounded-full bg-ainchors-green-hero px-3 py-1 text-xs font-bold text-ainchors-navy">{{ $bundleRelations->count() }} course{{ $bundleRelations->count() === 1 ? '' : 's' }}</span>
            </div>

            @error('package')<p class="mt-4 rounded-ainchors-button bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror
            @error('course_id')<p class="mt-4 rounded-ainchors-button bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror
            @error('positions')<p class="mt-4 rounded-ainchors-button bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror

            <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div>
                    <div class="overflow-x-auto rounded-ainchors-button border border-ainchors-navy/10">
                        <table class="w-full min-w-[42rem] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-ainchors-grey-dark"><tr><th class="px-4 py-3 font-bold">Order</th><th class="px-4 py-3 font-bold">Course</th><th class="px-4 py-3 font-bold">Status</th><th class="px-4 py-3 text-right font-bold">Action</th></tr></thead>
                            <tbody class="divide-y divide-ainchors-navy/8">
                                @forelse ($bundleRelations as $relation)
                                    <tr>
                                        <td class="px-4 py-3 text-ainchors-grey-dark">{{ $relation->sort_order }}</td>
                                        <td class="px-4 py-3"><p class="font-semibold text-ainchors-navy">{{ $relation->childProduct?->name ?? 'Course unavailable' }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $relation->childProduct?->sku ?? '—' }}</p></td>
                                        <td class="px-4 py-3">@if ($relation->childProduct) @include('admin.partials.status-badge', ['status' => $relation->childProduct->status]) @else — @endif</td>
                                        <td class="px-4 py-3 text-right">
                                            @if ($relation->childProduct)
                                                <form method="POST" action="{{ route('admin.package-members.destroy', [$product, $relation->childProduct]) }}" class="inline">@csrf @method('DELETE')<button type="submit" class="font-semibold text-red-700 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500">Remove</button></form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-ainchors-grey-dark">No courses are currently included.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($bundleRelations->isNotEmpty())
                        <form method="POST" action="{{ route('admin.package-members.reorder', $product) }}" class="mt-5 rounded-ainchors-button bg-slate-50 p-4">
                            @csrf
                            @method('PATCH')
                            <p class="text-sm font-semibold text-ainchors-navy">Reorder package</p>
                            <p class="mt-1 text-xs leading-relaxed text-ainchors-grey-dark">Enter positions; ties are normalized deterministically.</p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($bundleRelations as $relation)
                                    <label class="flex items-center justify-between gap-3 rounded-ainchors-button bg-white px-3 py-2 text-sm"><span class="min-w-0 truncate font-semibold text-ainchors-navy">{{ $relation->childProduct?->name ?? 'Course '.$relation->child_product_id }}</span><input name="positions[{{ $relation->child_product_id }}]" type="number" min="1" max="9999" value="{{ old('positions.'.$relation->child_product_id, $relation->sort_order) }}" required class="w-20 rounded-ainchors-button border border-ainchors-grey-light/45 px-2.5 py-1.5 text-sm focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"></label>
                                @endforeach
                            </div>
                            <button type="submit" class="mt-4 rounded-ainchors-button bg-ainchors-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Save order</button>
                        </form>
                    @endif
                </div>

                <aside class="rounded-ainchors-button bg-slate-50 p-5">
                    <h3 class="font-heading text-xl font-bold text-ainchors-navy">Add course</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Active packages accept only active courses with protected course content configured.</p>
                    <form method="POST" action="{{ route('admin.package-members.store', $product) }}" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label for="package-course" class="block text-sm font-semibold text-ainchors-navy">Course</label>
                            <select id="package-course" name="course_id" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                                <option value="">Select a course</option>
                                @foreach ($availableCourses as $course)
                                    <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->name }} — {{ str($course->status)->headline() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" @disabled($availableCourses->isEmpty()) class="w-full rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition enabled:hover:bg-ainchors-navy disabled:cursor-not-allowed disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Add to package</button>
                    </form>
                </aside>
            </div>
        </section>
    @endif
@endsection
