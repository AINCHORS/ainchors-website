@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center font-sans font-semibold rounded-ainchors-button px-6 py-3 transition duration-200 text-ainchors-body';
    $variants = [
        'primary' => 'bg-ainchors-green text-ainchors-white hover:bg-opacity-90 hover:shadow-md',
        'secondary' => 'bg-transparent border border-ainchors-green text-ainchors-green hover:bg-ainchors-green hover:text-ainchors-white',
    ];
    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
