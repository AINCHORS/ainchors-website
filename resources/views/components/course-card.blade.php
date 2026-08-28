@props([
    'image' => null,
    'title' => '',
    'description' => '',
    'priceOriginal' => null,
    'priceCurrent' => null,
    'currency' => 'USD',
    'href' => '#',
    'buttonLabel' => 'View Course',
    'badge' => 'Self-learning course',
])

<article class="bg-ainchors-white border border-ainchors-grey-light/30 rounded-ainchors-card overflow-hidden flex flex-col h-full hover:shadow-lg transition duration-200">
    @if ($image)
        <div class="overflow-hidden">
            <img src="{{ $image }}" alt="{{ $title }}" class="w-full h-44 object-cover" loading="lazy">
        </div>
    @endif

    <div class="p-5 flex flex-col flex-grow">
        <span class="font-sans text-xs uppercase tracking-wide text-ainchors-green font-semibold mb-2">
            {{ $badge }}
        </span>

        <h3 class="font-sans font-bold text-ainchors-navy mb-2 text-xl leading-[1.3]">
            {{ $title }}
        </h3>

        <p class="font-sans text-ainchors-body text-ainchors-grey-dark mb-4 flex-grow">
            {{ $description }}
        </p>

        @if ($priceCurrent)
            <div class="mb-4 flex items-baseline gap-2">
                @if ($priceOriginal)
                    <span class="font-sans text-sm text-ainchors-grey-light line-through">
                        {{ $currency }} {{ $priceOriginal }}
                    </span>
                @endif
                <span class="font-sans text-lg font-bold text-ainchors-navy">
                    {{ $currency }} {{ $priceCurrent }}
                </span>
            </div>
        @endif

        <x-button variant="course" :href="$href" class="gap-2">
            <span>{{ $buttonLabel }}</span>
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h12m-4-4 4 4-4 4" />
            </svg>
        </x-button>
    </div>
</article>
