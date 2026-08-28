@php
    $editing = isset($product) && $product;
    $selectedType = old('type', $editing ? $product->type : 'course');
    $selectedBilling = old('billing_type', $editing ? $product->billing_type : 'one_time');
    $selectedBilling = \App\Services\Products\ProductBillingRules::allows($selectedType, $selectedBilling)
        ? $selectedBilling
        : 'one_time';
    $selectedCurrency = old('currency', $editing ? $product->currency : 'USD');
    $selectedPrice = old('price', $editing && $product->price !== null ? number_format((float) $product->price, 2, '.', '') : '');
    $selectedImage = old('image', $editing ? $product->image : '');
    $selectedCategory = old('course_category', $editing ? $product->course_category : '');
    $imagePreviewUrl = \App\Services\Products\ProductImagePath::previewUrl($selectedImage);
@endphp

<div
    class="contents"
    x-data="{
        productType: @js($selectedType),
        billingType: @js($selectedBilling),
        currency: @js($selectedCurrency),
        price: @js($selectedPrice),
        fixedBillingTypes: ['course', 'course_package'],
        recurringTypes: ['consulting', 'service'],
        monthlyPreview() {
            const amount = Number(this.price);
            if (!Number.isFinite(amount) || this.price === '') return `${this.currency} — / month`;
            return `${this.currency} ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} / month`;
        },
    }"
    x-init="$watch('productType', value => { if (fixedBillingTypes.includes(value)) billingType = 'one_time' })"
>

<section aria-labelledby="product-identity-heading">
    <div><h2 id="product-identity-heading" class="font-heading text-xl font-bold text-ainchors-navy">Product identity</h2><p class="mt-1 text-sm leading-relaxed text-ainchors-grey-dark">Core identifiers used throughout catalogue, orders and learning access.</p></div>
    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <div>
            <label for="product-type" class="block text-sm font-semibold text-ainchors-navy">Product type</label>
            @if ($editing && ! ($typeChange['editable'] ?? false))
                <input type="hidden" name="type" value="{{ $product->type }}">
                <div id="product-type" class="mt-2 rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-ainchors-navy">{{ str($product->type)->replace('_', ' ')->headline() }}</div>
                <p class="mt-1.5 text-xs leading-relaxed text-ainchors-grey-dark">{{ $typeChange['message'] }}</p>
            @else
                <select id="product-type" name="type" x-model="productType" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                    <option value="course" @selected($selectedType === 'course')>Course</option>
                    <option value="course_package" @selected($selectedType === 'course_package')>Course package</option>
                    <option value="consulting" @selected($selectedType === 'consulting')>Consulting</option>
                    <option value="service" @selected($selectedType === 'service')>Service</option>
                </select>
                @if ($editing)
                    <p class="mt-1.5 text-xs leading-relaxed text-ainchors-grey-dark">{{ $typeChange['message'] }} Changing it will be recorded in the Admin audit log.</p>
                @endif
            @endif
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
        <div class="sm:col-span-2" x-show="productType === 'course'">
            <label for="product-course-category" class="block text-sm font-semibold text-ainchors-navy">Course category</label>
            <select id="product-course-category" name="course_category" :disabled="productType !== 'course'" @disabled($selectedType !== 'course') class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                <option value="">Select a course category</option>
                @foreach (\App\Models\Product::COURSE_CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-ainchors-grey-dark">Controls the section where this course appears in the public catalogue.</p>
            @error('course_category')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label for="product-slug" class="block text-sm font-semibold text-ainchors-navy">URL slug</label>
            <input id="product-slug" name="slug" type="text" value="{{ old('slug', $editing ? $product->slug : '') }}" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*" aria-describedby="product-slug-hint" @error('slug') aria-invalid="true" aria-describedby="product-slug-hint product-slug-error" @enderror class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
            <p id="product-slug-hint" class="mt-1.5 text-xs text-ainchors-grey-dark">Lowercase letters, numbers and hyphens only. A course slug is locked after private content is configured.</p>
            @error('slug')<p id="product-slug-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section aria-labelledby="product-copy-heading" class="border-t border-ainchors-navy/10 pt-7">
    <div><h2 id="product-copy-heading" class="font-heading text-xl font-bold text-ainchors-navy">Catalogue copy</h2><p class="mt-1 text-sm leading-relaxed text-ainchors-grey-dark">Maintain the approved product wording without mixing it with course-file management.</p></div>
    <div class="mt-5 grid gap-5">
        <div><label for="product-short-description" class="block text-sm font-semibold text-ainchors-navy">Short description</label><textarea id="product-short-description" name="short_description" rows="3" class="mt-2 block w-full resize-y rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm leading-relaxed text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">{{ old('short_description', $editing ? $product->short_description : '') }}</textarea>@error('short_description')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
        <div><label for="product-description" class="block text-sm font-semibold text-ainchors-navy">Full description</label><textarea id="product-description" name="description" rows="8" class="mt-2 block w-full resize-y rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm leading-relaxed text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">{{ old('description', $editing ? $product->description : '') }}</textarea>@error('description')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
        <div>
            <label for="product-image" class="block text-sm font-semibold text-ainchors-navy">Catalogue image path <span class="font-normal text-ainchors-grey-dark">(optional)</span></label>
            @if ($selectedImage)
                <div class="mt-2 overflow-hidden rounded-ainchors-button border border-ainchors-navy/10 bg-[#f2f7f5] p-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-ainchors-grey-dark">Current image preview</p>
                    @if ($imagePreviewUrl)
                        <img src="{{ $imagePreviewUrl }}" alt="Current catalogue image for {{ old('name', $editing ? $product->name : 'this product') }}" class="mt-3 h-40 w-full rounded-lg bg-white object-contain">
                    @else
                        <p class="mt-2 text-sm text-amber-800">The saved path is not available as a safe local preview. Verify the asset before saving.</p>
                    @endif
                    <p class="mt-2 break-all font-mono text-xs text-ainchors-grey-dark">{{ $selectedImage }}</p>
                </div>
            @endif
            <input id="product-image" name="image" type="text" maxlength="500" value="{{ $selectedImage }}" placeholder="assets/site/example.webp" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
            <p class="mt-1.5 text-xs text-ainchors-grey-dark">Use an approved existing path inside <code>public/assets</code>. External URLs and filesystem paths are rejected. Private course media is managed separately.</p>
            @error('image')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section aria-labelledby="product-pricing-heading" class="border-t border-ainchors-navy/10 pt-7">
    <div><h2 id="product-pricing-heading" class="font-heading text-xl font-bold text-ainchors-navy">Pricing</h2><p class="mt-1 text-sm leading-relaxed text-ainchors-grey-dark">These values apply to new catalogue transactions; historical order lines remain unchanged.</p></div>
    <div class="mt-5 grid gap-5 sm:grid-cols-3">
        <div><label for="product-price" class="block text-sm font-semibold text-ainchors-navy">Price</label><input id="product-price" name="price" x-model="price" type="number" min="0" step="0.01" value="{{ $selectedPrice }}" class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">@error('price')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror</div>
        <div>
            <label for="product-currency" class="block text-sm font-semibold text-ainchors-navy">Currency</label>
            <select id="product-currency" name="currency" x-model="currency" required class="mt-2 block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                @foreach (config('commerce.supported_currencies', []) as $code => $label)
                    <option value="{{ $code }}" @selected($selectedCurrency === $code)>{{ $label }}</option>
                @endforeach
            </select>
            @error('currency')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="product-billing-type" class="block text-sm font-semibold text-ainchors-navy">Billing type</label>
            <div x-show="fixedBillingTypes.includes(productType)" class="mt-2">
                <input type="hidden" name="billing_type" value="one_time" :disabled="!fixedBillingTypes.includes(productType)" @disabled(! \App\Services\Products\ProductBillingRules::isFixedOneTime($selectedType))>
                <div id="product-billing-type" class="rounded-ainchors-button border border-ainchors-navy/10 bg-[#f2f7f5] px-3.5 py-2.5 text-sm font-semibold text-ainchors-navy">One-time</div>
                <p class="mt-1.5 text-xs leading-relaxed text-ainchors-grey-dark">Courses and course packages always use one-time billing.</p>
            </div>
            <div x-cloak x-show="recurringTypes.includes(productType)" class="mt-2">
                <select id="product-billing-type-service" name="billing_type" x-model="billingType" :disabled="!recurringTypes.includes(productType)" @disabled(! in_array($selectedType, ['consulting', 'service'], true)) class="block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-white px-3.5 py-2.5 text-sm text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25">
                    <option value="one_time">One-time</option>
                    <option value="monthly">Monthly</option>
                </select>
                <p class="mt-1.5 text-xs leading-relaxed text-ainchors-grey-dark">Monthly is catalogue metadata only; it does not create a recurring subscription.</p>
            </div>
            @error('billing_type')<p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
    </div>
    <div x-cloak x-show="recurringTypes.includes(productType) && billingType === 'monthly'" class="mt-4 rounded-ainchors-button border border-ainchors-green/25 bg-ainchors-green-hero px-4 py-3">
        <p class="text-xs font-bold uppercase tracking-wide text-ainchors-green">Catalogue billing preview</p>
        <p class="mt-1 font-heading text-lg font-bold text-ainchors-navy" x-text="monthlyPreview()"></p>
        <p class="mt-1 text-xs leading-relaxed text-ainchors-grey-dark">Display metadata only. Payment subscriptions are outside this Admin product task.</p>
    </div>
</section>

@if ($editing && ! \App\Services\Products\ProductBillingRules::allows($product->type, $product->billing_type))
    <div class="rounded-ainchors-button border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-relaxed text-amber-900"><strong>Legacy billing value detected.</strong> Choose a supported billing option before saving. The database record is not changed automatically.</div>
@endif

@if ($editing)
    <input type="hidden" name="status" value="{{ $product->status }}">
@else
    <input type="hidden" name="status" value="draft">
    <div class="rounded-ainchors-button border border-ainchors-green/20 bg-ainchors-green-hero px-4 py-3 text-sm leading-relaxed text-ainchors-grey-dark"><strong class="text-ainchors-navy">Initial status: Draft.</strong> Complete the product setup from its management page before activation.</div>
@endif
@error('status')<p class="text-sm text-red-700">{{ $message }}</p>@enderror

<p class="rounded-ainchors-button border border-ainchors-navy/10 bg-slate-50 px-4 py-3 text-sm leading-relaxed text-ainchors-grey-dark">Private videos and slides are managed through Course content. Their protected storage locations are never exposed here.</p>
</div>
