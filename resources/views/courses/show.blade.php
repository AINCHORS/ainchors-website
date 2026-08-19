@extends('layouts.app')

@section('title', $product->name.' | AINCHORS')

@section('content')
<section class="product-detail-section">
    <div class="site-shell product-detail-grid">
        <div class="product-detail-image">
            @if ($product->image)
                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}">
            @else
                <div class="package-art">10<br><span>COURSES</span></div>
            @endif
        </div>
        <div class="product-detail-copy">
            <span class="eyebrow">{{ $product->isPackage() ? 'Complete learning package' : 'Self-learning course' }}</span>
            <h1>{{ $product->name }}</h1>
            <p>{{ $product->description }}</p>

            @if ($product->isPackage())
                <ul class="included-course-list">
                    @foreach ($product->bundleProducts as $included)
                        <li>{{ $included->name }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="price-line large"><del>USD {{ number_format($product->listPrice(), 0) }}</del><strong>USD {{ number_format((float) $product->price, 0) }}</strong></div>

            @if (session('error'))<p class="notice-error">{{ session('error') }}</p>@endif

            @if ($owned)
                <a class="primary-button" href="{{ $product->isPackage() ? route('my-courses') : route('learn.show', $product) }}">
                    {{ $product->isPackage() ? 'ACCESS MY COURSES' : 'ACCESS COURSE' }}
                </a>
            @else
                <a class="primary-button" href="{{ route('checkout.show', $product) }}">
                    {{ $product->isPackage() ? 'BUY PACKAGE' : 'BUY NOW' }}
                </a>
            @endif
        </div>
    </div>
</section>
@endsection
