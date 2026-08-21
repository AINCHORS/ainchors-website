@php
    $editing = isset($product) && $product;
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="product-type" class="block text-sm font-semibold text-ainchors-navy">Product type</label>
        <select id="product-type" name="type" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
            <option value="course" @selected(old('type', $editing ? $product->type : 'course') === 'course')>Course</option>
            <option value="course_package" @selected(old('type', $editing ? $product->type : '') === 'course_package')>Course package</option>
            <option value="consulting" @selected(old('type', $editing ? $product->type : '') === 'consulting')>Consulting</option>
            <option value="service" @selected(old('type', $editing ? $product->type : '') === 'service')>Service</option>
        </select>
        @error('type')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="product-sku" class="block text-sm font-semibold text-ainchors-navy">SKU</label>
        <input id="product-sku" name="sku" type="text" value="{{ old('sku', $editing ? $product->sku : '') }}" required maxlength="100" @error('sku') aria-invalid="true" aria-describedby="product-sku-error" @enderror class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('sku')<p id="product-sku-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="product-name" class="block text-sm font-semibold text-ainchors-navy">Product name</label>
        <input id="product-name" name="name" type="text" value="{{ old('name', $editing ? $product->name : '') }}" required @error('name') aria-invalid="true" aria-describedby="product-name-error" @enderror class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('name')<p id="product-name-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="product-slug" class="block text-sm font-semibold text-ainchors-navy">URL slug</label>
        <input id="product-slug" name="slug" type="text" value="{{ old('slug', $editing ? $product->slug : '') }}" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*" aria-describedby="product-slug-hint" @error('slug') aria-invalid="true" aria-describedby="product-slug-hint product-slug-error" @enderror class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        <p id="product-slug-hint" class="mt-1.5 text-xs text-ainchors-grey-dark">Use lowercase letters, numbers and hyphens only.</p>
        @error('slug')<p id="product-slug-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="product-short-description" class="block text-sm font-semibold text-ainchors-navy">Short description</label>
        <textarea id="product-short-description" name="short_description" rows="3" class="mt-2 block w-full resize-y rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm leading-relaxed text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">{{ old('short_description', $editing ? $product->short_description : '') }}</textarea>
        @error('short_description')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="product-description" class="block text-sm font-semibold text-ainchors-navy">Full description</label>
        <textarea id="product-description" name="description" rows="8" class="mt-2 block w-full resize-y rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm leading-relaxed text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">{{ old('description', $editing ? $product->description : '') }}</textarea>
        @error('description')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="product-price" class="block text-sm font-semibold text-ainchors-navy">Price</label>
        <input id="product-price" name="price" type="number" min="0" step="0.01" value="{{ old('price', $editing && $product->price !== null ? number_format((float) $product->price, 2, '.', '') : '') }}" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('price')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="product-currency" class="block text-sm font-semibold text-ainchors-navy">Currency</label>
        <input id="product-currency" name="currency" type="text" value="{{ old('currency', $editing ? $product->currency : 'USD') }}" maxlength="3" pattern="[A-Za-z]{3}" class="mt-2 block w-full uppercase rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
        @error('currency')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="product-billing-type" class="block text-sm font-semibold text-ainchors-navy">Billing type</label>
        <select id="product-billing-type" name="billing_type" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
            <option value="one_time" @selected(old('billing_type', $editing ? $product->billing_type : 'one_time') === 'one_time')>One-time</option><option value="monthly" @selected(old('billing_type', $editing ? $product->billing_type : '') === 'monthly')>Monthly</option><option value="custom" @selected(old('billing_type', $editing ? $product->billing_type : '') === 'custom')>Custom</option>
        </select>
        @error('billing_type')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    @if (! $editing)
        <div>
            <label for="product-status" class="block text-sm font-semibold text-ainchors-navy">Initial status</label>
            <select id="product-status" name="status" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"><option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option><option value="active" @selected(old('status') === 'active')>Active</option><option value="inactive" @selected(old('status') === 'inactive')>Inactive</option></select>
            @error('status')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    @else
        <input type="hidden" name="status" value="{{ $product->status }}">
        @error('status')<p class="sm:col-span-2 text-sm text-red-700">{{ $message }}</p>@enderror
    @endif
</div>

<p class="rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 px-4 py-3 text-sm leading-relaxed text-ainchors-grey-dark">Public media is managed through the approved asset workflow. This form deliberately does not expose protected media locations.</p>
