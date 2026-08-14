@props(['testimonial'])

<article class="testimonial-card">
    <span class="quote-mark" aria-hidden="true">“</span>
    <p>{{ $testimonial['quote'] }}</p>
    <div class="testimonial-person"><img src="{{ asset($testimonial['avatar']) }}" alt="{{ $testimonial['name'] }}"><strong>{{ $testimonial['name'] }}</strong></div>
</article>
