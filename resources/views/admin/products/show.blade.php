@extends('layouts.admin')

@section('title', $product->name.' | AINCHORS Admin')

@section('content')
    <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>All products</a>
            <div class="mt-4 flex flex-wrap items-center gap-3"><h1 class="font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">{{ $product->name }}</h1>@include('admin.partials.status-badge', ['status' => $product->status])</div>
            <p class="mt-2 text-sm text-ainchors-grey-dark">{{ $product->sku }} · {{ str($product->type)->replace('_', ' ')->headline() }}</p>
        </div>
        <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex items-center justify-center rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Edit product</a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(20rem,0.75fr)]">
        <div class="space-y-6">
            <section aria-labelledby="product-readiness-heading" class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Activation readiness</p><h2 id="product-readiness-heading" class="mt-2 font-heading text-2xl font-bold text-ainchors-navy">{{ $readiness['label'] }}</h2><p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">{{ $readiness['summary'] }}</p></div>
                    <span class="inline-flex self-start rounded-full px-3 py-1.5 text-xs font-bold {{ $readiness['ready'] ? 'bg-ainchors-green/10 text-ainchors-green' : 'bg-amber-100 text-amber-800' }}">{{ $readiness['ready'] ? 'Ready to activate' : 'Action required' }}</span>
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ($readiness['checks'] as $check)
                        <div class="rounded-ainchors-button border p-4 {{ $check['complete'] ? 'border-ainchors-green/20 bg-ainchors-green/5' : 'border-amber-200 bg-amber-50' }}">
                            <div class="flex items-start gap-3"><span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-xs font-bold {{ $check['complete'] ? 'bg-ainchors-green text-white' : 'bg-amber-200 text-amber-900' }}">{{ $check['complete'] ? '✓' : '!' }}</span><div><h3 class="text-sm font-semibold text-ainchors-navy">{{ $check['label'] }}</h3><p class="mt-1 text-xs leading-relaxed text-ainchors-grey-dark">{{ $check['detail'] }}</p></div></div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="product-management-heading" class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
                <h2 id="product-management-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Management</h2>
                <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Choose the area you want to manage. Each page has one clear responsibility.</p>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @if ($product->isCourse())
                        <a href="{{ $product->courseContent ? route('admin.course-content.edit', $product->courseContent) : route('admin.course-content.create', ['product_id' => $product->id]) }}" class="group rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 p-5 transition hover:border-ainchors-green hover:bg-ainchors-green-hero"><p class="font-heading text-lg font-bold text-ainchors-navy group-hover:text-ainchors-green">Course content</p><p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Preview or replace the private video and slide deck.</p></a>
                    @endif
                    @if ($product->isPackage())
                        <a href="{{ route('admin.package-members.index', $product) }}" class="group rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 p-5 transition hover:border-ainchors-green hover:bg-ainchors-green-hero"><p class="font-heading text-lg font-bold text-ainchors-navy group-hover:text-ainchors-green">Package courses</p><p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Add, remove and reorder {{ $readiness['course_count'] }} included course{{ $readiness['course_count'] === 1 ? '' : 's' }}.</p></a>
                    @endif
                    @if ($product->isCourse())
                        <a href="{{ route('admin.enrollments.index', ['q' => $product->name]) }}" class="group rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 p-5 transition hover:border-ainchors-green hover:bg-ainchors-green-hero"><p class="font-heading text-lg font-bold text-ainchors-navy group-hover:text-ainchors-green">Enrollments</p><p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Review {{ $product->enrollments_count }} course-access record{{ $product->enrollments_count === 1 ? '' : 's' }} linked to this course.</p></a>
                    @endif
                    <a href="{{ route('admin.orders.index') }}" class="group rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 p-5 transition hover:border-ainchors-green hover:bg-ainchors-green-hero"><p class="font-heading text-lg font-bold text-ainchors-navy group-hover:text-ainchors-green">Orders</p><p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Open commerce records. This product appears in {{ $product->order_items_count }} order item{{ $product->order_items_count === 1 ? '' : 's' }}.</p></a>
                    <a href="{{ route('admin.audit-log.index', ['q' => $product->id]) }}" class="group rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 p-5 transition hover:border-ainchors-green hover:bg-ainchors-green-hero"><p class="font-heading text-lg font-bold text-ainchors-navy group-hover:text-ainchors-green">Audit history</p><p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Review recorded administrator actions for entity ID {{ $product->id }}.</p></a>
                </div>
            </section>

            <section aria-labelledby="product-description-heading" class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm sm:p-8">
                <h2 id="product-description-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Catalogue copy</h2>
                @if ($product->short_description)<p class="mt-5 font-semibold leading-relaxed text-ainchors-navy">{{ $product->short_description }}</p>@endif
                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-ainchors-grey-dark">{{ $product->description ?: 'No full catalogue description has been added.' }}</p>
            </section>
        </div>

        <div class="space-y-6">
            <section aria-labelledby="product-details-heading" class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm">
                <h2 id="product-details-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Overview</h2>
                <dl class="mt-5 divide-y divide-ainchors-navy/10 text-sm">
                    <div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Price</dt><dd class="text-right font-semibold text-ainchors-navy">{{ \App\Services\Products\ProductBillingRules::priceLabel($product->price !== null ? (float) $product->price : null, $product->currency, $product->billing_type) }}</dd></div>
                    <div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Billing</dt><dd class="text-right text-ainchors-navy">{{ \App\Services\Products\ProductBillingRules::label($product->billing_type) }}</dd></div>
                    <div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Type</dt><dd class="text-right text-ainchors-navy">{{ str($product->type)->replace('_', ' ')->headline() }}</dd></div>
                    @if ($product->isCourse())<div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Course category</dt><dd class="text-right text-ainchors-navy">{{ $product->courseCategoryLabel() ?? 'Not assigned' }}</dd></div>@endif
                    <div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Slug</dt><dd class="max-w-[13rem] break-words text-right text-ainchors-navy">{{ $product->slug }}</dd></div>
                    <div class="flex justify-between gap-5 py-3"><dt class="font-semibold text-ainchors-grey-dark">Last updated</dt><dd class="text-right text-ainchors-navy">{{ $product->updated_at?->format('j M Y, H:i') ?? '—' }}</dd></div>
                </dl>
            </section>

            <section aria-labelledby="product-status-heading" class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-6 shadow-sm">
                <h2 id="product-status-heading" class="font-heading text-2xl font-bold text-ainchors-navy">Catalogue status</h2>
                <p class="mt-2 text-sm leading-relaxed text-ainchors-grey-dark">Use status changes instead of deletion so historical orders remain intact.</p>
                @error('status')<p class="mt-4 rounded-ainchors-button bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror
                <form method="POST" action="{{ route('admin.products.status', $product) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div><label for="product-update-status" class="block text-sm font-semibold text-ainchors-navy">Status</label><select id="product-update-status" name="status" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"><option value="draft" @selected($product->status === 'draft')>Draft</option><option value="active" @selected($product->status === 'active') @disabled(! $readiness['ready'] && $product->status !== 'active')>Active{{ ! $readiness['ready'] && $product->status !== 'active' ? ' — complete required setup first' : '' }}</option><option value="inactive" @selected($product->status === 'inactive')>Inactive</option></select></div>
                    <button type="submit" class="w-full rounded-ainchors-button border border-ainchors-navy/15 bg-white px-4 py-2.5 text-sm font-semibold text-ainchors-navy transition hover:border-ainchors-green hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">Update status</button>
                </form>
            </section>
        </div>
    </div>
@endsection
