@php
    $profileUser = $user ?? null;
    $fieldClass = 'block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-ainchors-white px-4 py-3 font-sans text-ainchors-body text-ainchors-navy shadow-sm outline-none transition focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25';
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div class="space-y-2">
        <label for="first-name" class="block font-sans text-sm font-semibold text-ainchors-navy">First Name</label>
        <input id="first-name" name="first_name" type="text" value="{{ old('first_name', $profileUser?->first_name) }}" autocomplete="given-name" required class="{{ $fieldClass }}">
        @error('first_name')<p class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div class="space-y-2">
        <label for="last-name" class="block font-sans text-sm font-semibold text-ainchors-navy">Last Name</label>
        <input id="last-name" name="last_name" type="text" value="{{ old('last_name', $profileUser?->last_name) }}" autocomplete="family-name" required class="{{ $fieldClass }}">
        @error('last_name')<p class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div class="space-y-2">
        <label for="date-of-birth" class="block font-sans text-sm font-semibold text-ainchors-navy">Date of Birth</label>
        <input id="date-of-birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $profileUser?->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" autocomplete="bday" required class="{{ $fieldClass }}">
        @error('date_of_birth')<p class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div class="space-y-2">
        <label for="phone" class="block font-sans text-sm font-semibold text-ainchors-navy">Phone Number</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone', $profileUser?->phone) }}" autocomplete="tel" inputmode="tel" pattern="[0-9+() .-]+" oninput="this.value = this.value.replace(/[^0-9+() .-]/g, '')" placeholder="e.g. +60 12 345 6789" required class="{{ $fieldClass }}">
        @error('phone')<p class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>

<div class="space-y-2">
    <label for="country" class="block font-sans text-sm font-semibold text-ainchors-navy">Country</label>
    <select id="country" name="country" autocomplete="country-name" required class="{{ $fieldClass }}">
        <option value="">Select your country</option>
        @foreach ($countries as $country)
            <option value="{{ $country }}" @selected(old('country', $profileUser?->country) === $country)>{{ $country }}</option>
        @endforeach
    </select>
    @error('country')<p class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
</div>

<div class="space-y-2">
    <label for="address-line-1" class="block font-sans text-sm font-semibold text-ainchors-navy">Home Address</label>
    <input id="address-line-1" name="address_line_1" type="text" value="{{ old('address_line_1', $profileUser?->address_line_1) }}" autocomplete="address-line1" required class="{{ $fieldClass }}">
    @error('address_line_1')<p class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
</div>

<div class="space-y-2">
    <label for="address-line-2" class="block font-sans text-sm font-semibold text-ainchors-navy">Apartment, Suite, Unit (optional)</label>
    <input id="address-line-2" name="address_line_2" type="text" value="{{ old('address_line_2', $profileUser?->address_line_2) }}" autocomplete="address-line2" class="{{ $fieldClass }}">
    @error('address_line_2')<p class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
</div>

<div class="grid gap-5 sm:grid-cols-3">
    <div class="space-y-2">
        <label for="city" class="block font-sans text-sm font-semibold text-ainchors-navy">City</label>
        <input id="city" name="city" type="text" value="{{ old('city', $profileUser?->city) }}" autocomplete="address-level2" required class="{{ $fieldClass }}">
        @error('city')<p class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div class="space-y-2">
        <label for="state" class="block font-sans text-sm font-semibold text-ainchors-navy">State / Province</label>
        <input id="state" name="state" type="text" value="{{ old('state', $profileUser?->state) }}" autocomplete="address-level1" required class="{{ $fieldClass }}">
        @error('state')<p class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div class="space-y-2">
        <label for="postal-code" class="block font-sans text-sm font-semibold text-ainchors-navy">Postal Code</label>
        <input id="postal-code" name="postal_code" type="text" value="{{ old('postal_code', $profileUser?->postal_code) }}" autocomplete="postal-code" required class="{{ $fieldClass }}">
        @error('postal_code')<p class="font-sans text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
</div>
