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

        <x-button variant="primary" :href="$href">
            {{ $buttonLabel }} <span aria-hidden="true">&rarr;</span>
        </x-button>
    </div>
</article>
