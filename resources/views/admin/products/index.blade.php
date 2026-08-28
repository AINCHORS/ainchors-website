@extends('layouts.admin')

@section('title', 'Products | AINCHORS Admin')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-ainchors-green">Catalogue management</p>
            <h1 class="mt-2 font-heading text-3xl font-bold text-ainchors-navy sm:text-4xl">Products</h1>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ainchors-grey-dark">Find a product, check whether it is ready, then open one management page for the relevant actions.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Add product</a>
    </div>

    <form method="GET" action="{{ route('admin.products.index') }}" class="mb-6 grid gap-3 rounded-ainchors-card border border-ainchors-navy/10 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_11rem_13rem_10rem_11rem_auto] xl:items-end">
        <div>
            <label for="products-search" class="block text-sm font-semibold text-ainchors-navy">Search</label>
            <input id="products-search" name="q" type="search" value="{{ request('q') }}" placeholder="Name, SKU or slug" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        </div>
        <div>
            <label for="products-course-category" class="block text-sm font-semibold text-ainchors-navy">Course category</label>
            <select id="products-course-category" name="course_category" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="">All categories</option>
                @foreach (\App\Models\Product::COURSE_CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected(request('course_category') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="products-type" class="block text-sm font-semibold text-ainchors-navy">Type</label>
            <select id="products-type" name="type" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="">All types</option>
                <option value="course" @selected(request('type') === 'course')>Course</option>
                <option value="course_package" @selected(request('type') === 'course_package')>Course package</option>
                <option value="consulting" @selected(request('type') === 'consulting')>Consulting</option>
                <option value="service" @selected(request('type') === 'service')>Service</option>
            </select>
        </div>
        <div>
            <label for="products-status" class="block text-sm font-semibold text-ainchors-navy">Status</label>
            <select id="products-status" name="status" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="">All statuses</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <div>
            <label for="products-readiness" class="block text-sm font-semibold text-ainchors-navy">Readiness</label>
            <select id="products-readiness" name="readiness" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="">All readiness</option>
                <option value="ready" @selected(request('readiness') === 'ready')>Ready</option>
                <option value="incomplete" @selected(request('readiness') === 'incomplete')>Incomplete</option>
            </select>
        </div>
        <div class="flex gap-2 md:col-span-2 xl:col-span-1">
            <button type="submit" class="rounded-ainchors-button bg-ainchors-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green focus:ring-offset-2">Filter</button>
            <a href="{{ route('admin.products.index') }}" class="rounded-ainchors-button border border-ainchors-navy/15 px-4 py-2.5 text-sm font-semibold text-ainchors-grey-dark transition hover:border-ainchors-green hover:text-ainchors-green focus:outline-none focus:ring-2 focus:ring-ainchors-green">Reset</a>
        </div>
    </form>

    <section aria-labelledby="products-heading">
        <h2 id="products-heading" class="sr-only">Product catalogue</h2>

        <div class="space-y-4 lg:hidden">
            @forelse ($products as $product)
                @php($readiness = $readinessByProduct->get($product->id))
                <article class="rounded-ainchors-card border border-ainchors-navy/10 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-heading text-lg font-bold leading-snug text-ainchors-navy">{{ $product->name }}</h3>
                            <p class="mt-1 text-xs font-semibold tracking-wide text-ainchors-grey-dark">{{ $product->sku }} · {{ str($product->type)->replace('_', ' ')->headline() }}</p>
                            @if ($product->type === 'course')<p class="mt-1 text-xs text-ainchors-green">{{ $product->courseCategoryLabel() ?? 'Category not assigned' }}</p>@endif
                        </div>
                        @include('admin.partials.status-badge', ['status' => $product->status])
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-ainchors-button bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-ainchors-grey-dark">Price</p>
                            <p class="mt-2 font-semibold text-ainchors-navy">{{ \App\Services\Products\ProductBillingRules::priceLabel($product->price !== null ? (float) $product->price : null, $product->currency, $product->billing_type) }}</p>
                        </div>
                        <div class="rounded-ainchors-button bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3"><p class="text-xs font-bold uppercase tracking-wide text-ainchors-grey-dark">Readiness</p><span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $readiness['ready'] ? 'bg-ainchors-green/10 text-ainchors-green' : 'bg-amber-100 text-amber-800' }}">{{ $readiness['label'] }}</span></div>
                            <p class="mt-2 text-xs leading-relaxed text-ainchors-grey-dark">{{ $readiness['summary'] }}</p>
                        </div>
                    </div>
                    <div class="mt-5 flex items-center justify-between gap-4 border-t border-ainchors-navy/10 pt-4">
                        <p class="text-xs text-ainchors-grey-dark">Updated {{ $product->updated_at?->format('j M Y') ?? '—' }}</p>
                        <a href="{{ route('admin.products.show', $product) }}" class="inline-flex rounded-ainchors-button bg-ainchors-green px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green">Manage</a>
                    </div>
                </article>
            @empty
                <div class="rounded-ainchors-card border border-ainchors-navy/10 bg-white px-5 py-12 text-center shadow-sm"><p class="font-semibold text-ainchors-navy">No products match these filters.</p><p class="mt-1 text-sm text-ainchors-grey-dark">Adjust the filters or create a product.</p></div>
            @endforelse
        </div>

        <div class="hidden overflow-hidden rounded-ainchors-card border border-ainchors-navy/10 bg-white shadow-sm lg:block">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[64rem] table-fixed text-left text-sm">
                    <caption class="sr-only">AINCHORS products, readiness and catalogue status</caption>
                    <thead class="border-b border-ainchors-navy/10 bg-slate-50 text-xs uppercase tracking-wide text-ainchors-grey-dark">
                        <tr><th class="w-[27%] px-5 py-3.5 font-bold">Product</th><th class="w-[13%] px-5 py-3.5 font-bold">Type</th><th class="w-[13%] px-5 py-3.5 font-bold">Price</th><th class="w-[25%] px-5 py-3.5 font-bold">Readiness</th><th class="w-[10%] px-5 py-3.5 font-bold">Status</th><th class="w-[12%] px-5 py-3.5 text-right font-bold">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ainchors-navy/10">
                        @forelse ($products as $product)
                            @php($readiness = $readinessByProduct->get($product->id))
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-5 py-5"><p class="font-semibold leading-snug text-ainchors-navy">{{ $product->name }}</p><p class="mt-1 text-xs text-ainchors-grey-dark">{{ $product->sku }}</p></td>
                                <td class="px-5 py-5 text-ainchors-grey-dark">{{ str($product->type)->replace('_', ' ')->headline() }}@if ($product->type === 'course')<p class="mt-1 text-xs leading-snug text-ainchors-green">{{ $product->courseCategoryLabel() ?? 'Category not assigned' }}</p>@endif</td>
                                <td class="px-5 py-5 font-semibold text-ainchors-navy">{{ \App\Services\Products\ProductBillingRules::priceLabel($product->price !== null ? (float) $product->price : null, $product->currency, $product->billing_type) }}</td>
                                <td class="px-5 py-5"><span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $readiness['ready'] ? 'bg-ainchors-green/10 text-ainchors-green' : 'bg-amber-100 text-amber-800' }}">{{ $readiness['label'] }}</span><p class="mt-2 text-xs leading-relaxed text-ainchors-grey-dark">{{ $readiness['summary'] }}</p></td>
                                <td class="px-5 py-5">@include('admin.partials.status-badge', ['status' => $product->status])</td>
                                <td class="px-5 py-5 text-right"><a href="{{ route('admin.products.show', $product) }}" class="inline-flex rounded-ainchors-button border border-ainchors-green px-3.5 py-2 text-sm font-semibold text-ainchors-green transition hover:bg-ainchors-green hover:text-white focus:outline-none focus:ring-2 focus:ring-ainchors-green">Manage</a><p class="mt-2 text-xs text-ainchors-grey-dark">{{ $product->updated_at?->format('j M Y') ?? '—' }}</p></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center"><p class="font-semibold text-ainchors-navy">No products match these filters.</p><p class="mt-1 text-sm text-ainchors-grey-dark">Adjust the filters or create a product.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @if (method_exists($products, 'links'))<div class="mt-6">{{ $products->onEachSide(1)->links() }}</div>@endif
@endsection
