@php
    $errorName = $errorName ?? $name;
    $required = $required ?? true;
    $autofocus = $autofocus ?? false;
@endphp

<div class="space-y-2">
    <label for="{{ $id }}" class="block font-sans text-sm font-semibold text-ainchors-navy">{{ $label }}</label>
    <div x-data="{ visible: false }" class="relative">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            :type="visible ? 'text' : 'password'"
            autocomplete="{{ $autocomplete }}"
            @if ($required) required @endif
            @if ($autofocus) autofocus @endif
            @if ($errors->has($errorName)) aria-describedby="{{ $id }}-error" aria-invalid="true" @endif
            class="block w-full rounded-ainchors-button border border-ainchors-grey-light/45 bg-ainchors-white px-4 py-3 pr-14 font-sans text-ainchors-body text-ainchors-navy shadow-sm outline-none transition placeholder:text-ainchors-grey-light focus:border-ainchors-green focus:ring-2 focus:ring-ainchors-green/25"
        >
        <button
            type="button"
            @click="visible = !visible"
            aria-label="Show password"
            :aria-label="visible ? 'Hide password' : 'Show password'"
            class="absolute inset-y-0 right-0 flex items-center px-4 text-ainchors-green transition hover:text-ainchors-navy focus:outline-none focus:ring-2 focus:ring-ainchors-green"
        >
            <svg x-show="!visible" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7" stroke-width="1.8"/></svg>
            <svg x-cloak x-show="visible" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.6 5.7A9.6 9.6 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17.6 17.6 0 0 1-3.2 3.8M6.2 6.2A17.7 17.7 0 0 0 2.5 12s3.5 6.5 9.5 6.5a9.9 9.9 0 0 0 3.1-.5M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
            <span class="sr-only" x-text="visible ? 'Hide password' : 'Show password'">Show password</span>
        </button>
    </div>
    @if ($errors->has($errorName))
        <p id="{{ $id }}-error" role="alert" class="font-sans text-sm text-red-700">{{ $errors->first($errorName) }}</p>
    @endif
</div>
