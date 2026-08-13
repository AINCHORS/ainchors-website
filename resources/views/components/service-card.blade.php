@props(['service'])

<article class="service-card">
    <img src="{{ asset($service['image']) }}" alt="{{ $service['heading'] }}">
    <div><h3>{{ $service['heading'] }}</h3><p>{{ $service['body'] }}</p><a href="{{ $service['url'] }}">{{ $service['label'] }} <span aria-hidden="true">→</span></a></div>
</article>
