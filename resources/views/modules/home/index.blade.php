@extends('layouts.app')

@section('title', 'AINCHORS Training & Consulting')

@section('content')
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="site-shell hero-grid">
        <div class="hero-copy">
            <h1>{{ $hero['heading'] }}</h1>
            <p>{{ $hero['body'] }}</p>
            <a class="primary-button" href="https://wa.me/+61418802086">Get in touch <span aria-hidden="true">→</span></a>
        </div>
        <div class="hero-image"><img src="{{ asset('assets/hero.webp') }}" alt="AINCHORS immersive learning experience"></div>
    </div>
</section>

<section class="client-section">
    <div class="site-shell"><h2>Our International Clients and Partners</h2><div class="client-grid">
        @foreach ($clients as $client)<img src="{{ asset($client['image']) }}" alt="{{ $client['alt'] }}" loading="lazy">@endforeach
    </div></div>
</section>

<section class="section services-section">
    <div class="site-shell"><div class="section-heading"><span>What We Offer</span><h2>What We Offer</h2><p>Our courses are crafted by industry veterans with decades of real-world expertise.</p></div><div class="services-grid">
        @foreach ($services as $service)<x-cards.service-card :service="$service" />@endforeach
    </div></div>
</section>

<section class="trust-section">
    <div class="site-shell trust-grid">
        <div class="trust-copy"><span>Trusted By Government Regulators</span><h2>Working with the top international banks</h2><p>Here is the average rating training performance based on the 3 weeks programming training project for one of our top banking clients.</p><a href="https://wa.me/+61418802086">Contact Us! <span aria-hidden="true">→</span></a></div>
        <div class="rating-card"><h3>Well-received courses with positive feedbackS</h3><img src="{{ asset('assets/ratings.svg') }}" alt="Training performance ratings"></div>
    </div>
</section>

<section class="section testimonials-section">
    <div class="site-shell"><div class="section-heading"><span>Testimonials</span><h2>What our Customers are Saying</h2></div><div class="testimonial-grid">
        @foreach ($testimonials as $testimonial)<x-cards.testimonial-card :testimonial="$testimonial" />@endforeach
    </div><div class="center-link"><a href="{{ url('/testimonials') }}">View All <span aria-hidden="true">→</span></a></div></div>
</section>

<section class="cta-section"><div class="site-shell"><div class="cta-card"><h2>Innovate and Transform with AINCHORS to unlock the Full Potential</h2><a class="primary-button" href="https://wa.me/+61418802086">Contact Us! <span aria-hidden="true">→</span></a></div></div></section>
@endsection
