@extends('layouts.app')

@section('title', 'Checkout | '.$product->name)

@section('content')
<section class="checkout-section">
    <div class="site-shell checkout-shell">
        <div class="checkout-heading"><span class="eyebrow">Secure account checkout</span><h1>Checkout</h1></div>
        <div class="test-payment-notice"><strong>TEST PAYMENT</strong><span>No real payment will be charged.</span></div>

        <form method="POST" action="{{ route('checkout.store', $product) }}" class="checkout-grid" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $token }}">

            <div class="checkout-form-card">
                <h2>Customer</h2>
                <div class="form-grid two-columns">
                    <label>Name<input type="text" value="{{ auth()->user()->full_name }}" readonly></label>
                    <label>Email<input type="email" value="{{ auth()->user()->email }}" readonly></label>
                </div>

                <h2>Demo Payment Details</h2>
                <p class="demo-values">Use card <strong>4242 4242 4242 4242</strong>, expiry <strong>12/30</strong>, CVV <strong>123</strong>.</p>
                <div class="form-grid">
                    <label>Card Number<input type="text" name="card_number" value="4242 4242 4242 4242" inputmode="numeric" autocomplete="off" required></label>
                    @error('card_number')<p class="form-error">{{ $message }}</p>@enderror
                    <div class="two-columns">
                        <label>Expiry<input type="text" name="expiry" value="12/30" autocomplete="off" required></label>
                        <label>CVV<input type="password" name="cvv" value="123" inputmode="numeric" autocomplete="off" required></label>
                    </div>
                    @error('expiry')<p class="form-error">{{ $message }}</p>@enderror
                    @error('cvv')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <p class="security-note">Demo card details are validated for this request only and are never stored or logged.</p>
            </div>

            <aside class="order-summary-card">
                <h2>Order Summary</h2>
                <h3>{{ $product->name }}</h3>
                <div class="price-line large"><del>USD {{ number_format($product->listPrice(), 0) }}</del><strong>USD {{ number_format((float) $product->price, 0) }}</strong></div>
                <button type="submit" class="primary-button form-button" :disabled="submitting" x-text="submitting ? 'Processing…' : 'Pay USD {{ number_format((float) $product->price, 0) }}'">Pay USD {{ number_format((float) $product->price, 0) }}</button>
            </aside>
        </form>
    </div>
</section>
@endsection
