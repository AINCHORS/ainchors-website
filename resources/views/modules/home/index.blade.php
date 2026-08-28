@extends('layouts.app')

@section('title', 'AINCHORS Training & Consulting')

@section('content')
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="site-shell hero-grid">
        <div class="hero-copy">
            <h1>{{ $hero['heading'] }}</h1>
            <p>{{ $hero['body'] }}</p>
            <a class="primary-button" href="https://wa.me/+61418802086" target="_blank" rel="noopener noreferrer">
                <svg class="hero-whatsapp-icon" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12.04 2a9.84 9.84 0 0 0-8.47 14.84L2 22l5.29-1.51A9.96 9.96 0 1 0 12.04 2Zm0 17.98a8.04 8.04 0 0 1-4.1-1.12l-.29-.17-3.14.9.92-3.05-.19-.31A8.01 8.01 0 1 1 12.04 20Zm4.4-6.02c-.24-.12-1.43-.7-1.65-.79-.22-.08-.38-.12-.54.12-.16.24-.62.79-.76.95-.14.16-.28.18-.52.06-.24-.12-1.02-.38-1.94-1.2a7.28 7.28 0 0 1-1.34-1.67c-.14-.24-.01-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.19-.47-.39-.41-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.69 2.58 4.1 3.62.57.25 1.02.39 1.37.5.58.18 1.1.16 1.51.1.46-.07 1.43-.59 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28Z"/>
                </svg>
                <span>Get in touch</span>
            </a>
        </div>
        <div class="hero-image"><img src="{{ asset('assets/hero.webp') }}" alt="AINCHORS immersive learning experience"></div>
    </div>
</section>

<section class="client-section">
    <div class="site-shell">
        <h2>Our International Clients and Partners</h2>
        <div class="logo-showcase-wrapper" aria-label="AINCHORS international clients and partners">
            <div class="logo-showcase-track py-4">
                @foreach ($clients as $client)
                    <img src="{{ asset($client['image']) }}" alt="{{ $client['alt'] }}" class="h-auto w-[250px] flex-none object-contain" loading="lazy">
                @endforeach
                @foreach ($clients as $client)
                    <img src="{{ asset($client['image']) }}" alt="" aria-hidden="true" class="h-auto w-[250px] flex-none object-contain" loading="lazy">
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="section services-section">
    <div class="site-shell"><div class="section-heading"><span>What We Offer</span><h2>What We Offer</h2><p>Our courses are crafted by industry veterans with decades of real-world expertise.</p></div><div class="services-grid">
        @foreach ($services as $index => $service)
            <x-service-card
                :variant="['blue', 'green', 'orange'][$index]"
                :image="asset($service['image'])"
                :title="$service['heading']"
                :description="$service['body']"
                :button-label="$service['label']"
                :button-href="$service['url']"
            />
        @endforeach
    </div></div>
</section>

<section class="trust-section">
    <div class="site-shell trust-grid">
        <div class="trust-copy"><span>Trusted By Government Regulators</span><h2>Working with the top international banks</h2><p>Here is the average rating training performance based on the 3 weeks programming training project for one of our top banking clients.</p><a href="https://wa.me/+61418802086" target="_blank" rel="noopener noreferrer">Contact Us! <span aria-hidden="true">→</span></a></div>
        <div class="rating-card"><h3>Well-received courses with positive feedbackS</h3><img src="{{ asset('assets/ratings.svg') }}" alt="Training performance ratings"></div>
    </div>
</section>

<section class="section testimonials-section">
    <div class="site-shell"><div class="section-heading"><span>Testimonials</span><h2>What our Customers are Saying</h2></div><div class="testimonial-grid">
        @foreach ($testimonials as $testimonial)<x-cards.testimonial-card :testimonial="$testimonial" />@endforeach
    </div><div class="center-link"><a href="{{ route('testimonials') }}">View All <span aria-hidden="true">→</span></a></div></div>
</section>

<section class="cta-section"><div class="site-shell"><div class="cta-card"><h2>Innovate and Transform with AINCHORS to unlock the Full Potential</h2><a class="primary-button" href="https://wa.me/+61418802086">Contact Us! <span aria-hidden="true">→</span></a></div></div></section>
@endsection
