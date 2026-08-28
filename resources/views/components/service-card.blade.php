@props([
    'variant' => 'green',
    'image' => null,
    'title' => '',
    'description' => '',
    'buttonLabel' => 'Learn More',
    'buttonHref' => '#',
])

@php
    $bgMap = [
        'blue' => 'bg-ainchors-card-blue',
        'green' => 'bg-ainchors-card-green',
        'orange' => 'bg-ainchors-card-orange',
    ];
    $bgClass = $bgMap[$variant] ?? $bgMap['green'];
    $buttonColor = match ($variant) {
        'blue' => '#07d5f0',
        'orange' => '#f6ad55',
        default => null,
    };
@endphp

<article class="{{ $bgClass }} rounded-ainchors-card p-6 flex flex-col h-full">
    @if ($image)
        <div class="rounded-ainchors-card overflow-hidden mb-4">
            <img src="{{ $image }}" alt="{{ $title }}" class="w-full h-40 object-cover" loading="lazy">
        </div>
    @endif

    <h3 class="font-heading text-ainchors-navy mb-2 text-[22px] leading-[1.3] font-semibold">
        {{ $title }}
    </h3>

    <p class="font-sans text-ainchors-body text-ainchors-grey-dark mb-6 flex-grow">
        {{ $description }}
    </p>

    <x-button variant="primary" :href="$buttonHref" :style="$buttonColor ? 'background-color: '.$buttonColor : null">
        {{ $buttonLabel }}
    </x-button>
</article>
