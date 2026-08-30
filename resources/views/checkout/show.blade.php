@extends('layouts.app')

@section('title', 'Checkout | '.$product->name)

@section('content')
<section class="checkout-section">
    <div class="site-shell checkout-shell">
        <div class="checkout-heading"><span class="eyebrow">Secure account checkout</span><h1>Checkout</h1></div>
        @if ($paymentDriver === 'demo')
            <div class="test-payment-notice"><strong>TEST PAYMENT</strong><span>No real payment will be charged.</span></div>
        @elseif (config('commerce.payment.environment') === 'sandbox')
            <div class="test-payment-notice"><strong>SANDBOX PAYMENT</strong><span>Hosted payment test environment is enabled. No live charge will be made.</span></div>
        @endif

        @if (session('payment_cancelled'))
            <div class="test-payment-notice"><strong>PAYMENT CANCELLED</strong><span>{{ session('payment_cancelled') }}</span></div>
        @endif
        @error('payment')<p class="form-error" role="alert">{{ $message }}</p>@enderror

        <form method="POST" action="{{ route('checkout.store', $product) }}" class="checkout-grid" x-data="{ submitting: false, provider: @js($availableProviders[0] ?? '') }" @submit="submitting = true">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $token }}">

            <div class="checkout-form-card">
                <h2>Customer</h2>
                <div class="form-grid two-columns">
                    <label>Name<input type="text" value="{{ auth()->user()->full_name }}" readonly></label>
                    <label>Email<input type="email" value="{{ auth()->user()->email }}" readonly></label>
                </div>

                @if ($paymentDriver === 'demo')
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
                @else
                    <h2>Payment Method</h2>
                    <p class="demo-values">You will enter your payment details on the provider's secure hosted checkout page. AINCHORS does not receive or store your card number or CVV.</p>
                    @if (empty($availableProviders))
                        <p class="form-error" role="alert">Online payment is not configured yet. Please contact AINCHORS before placing this order.</p>
                    @else
                        <fieldset class="payment-methods">
                            <legend class="sr-only">Select a payment provider</legend>

                            @if (in_array('stripe', $availableProviders, true))
                                <label class="payment-provider-card" :class="{ 'payment-provider-card-selected': provider === 'stripe' }" @click="provider = 'stripe'">
                                    <input type="radio" name="payment_provider" value="stripe" x-model="provider" @checked(($availableProviders[0] ?? '') === 'stripe')>
                                    <span class="payment-provider-indicator" aria-hidden="true"></span>
                                    <span class="payment-provider-logo payment-provider-logo-stripe" aria-hidden="true"><img src="{{ asset('assets/stripe-logo.png') }}" alt=""><strong>stripe</strong></span>
                                    <span class="payment-provider-copy">
                                        <span class="payment-provider-heading"><strong>Stripe</strong></span>
                                        <span>Secure payment processed by Stripe</span>
                                    </span>
                                    <span class="payment-provider-badge payment-provider-badge-test">{{ config('commerce.payment.environment') === 'live' ? 'Live' : 'Test mode' }}</span>
                                </label>
                            @endif

                            @if (in_array('paypal', $availableProviders, true))
                                <label class="payment-provider-card" :class="{ 'payment-provider-card-selected': provider === 'paypal' }" @click="provider = 'paypal'">
                                    <input type="radio" name="payment_provider" value="paypal" x-model="provider" @checked(($availableProviders[0] ?? '') === 'paypal')>
                                    <span class="payment-provider-indicator" aria-hidden="true"></span>
                                    <span class="payment-provider-logo payment-provider-logo-paypal" aria-hidden="true"><img src="{{ asset('assets/paypal-logo.png') }}" alt=""></span>
                                    <span class="payment-provider-copy">
                                        <span class="payment-provider-heading"><strong>PayPal</strong></span>
                                        <span>Pay securely with your PayPal account</span>
                                    </span>
                                    <span class="payment-provider-badge payment-provider-badge-test">{{ config('commerce.payment.environment') === 'live' ? 'Live' : 'Test mode' }}</span>
                                </label>
                            @else
                                <div class="payment-provider-card payment-provider-card-disabled" aria-disabled="true">
                                    <span class="payment-provider-indicator" aria-hidden="true"></span>
                                    <span class="payment-provider-logo payment-provider-logo-paypal" aria-hidden="true"><img src="{{ asset('assets/paypal-logo.png') }}" alt=""></span>
                                    <span class="payment-provider-copy">
                                        <span class="payment-provider-heading"><strong>PayPal</strong></span>
                                        <span>Add PayPal API credentials to enable</span>
                                    </span>
                                    <span class="payment-provider-badge">Coming soon</span>
                                </div>
                            @endif
                        </fieldset>

                        <button type="submit" class="primary-button form-button checkout-provider-cta" :disabled="submitting" x-text="submitting ? 'Redirecting…' : `Continue with ${provider === 'paypal' ? 'PayPal' : 'Stripe'}`">Continue with {{ str($availableProviders[0] ?? 'payment provider')->headline() }}</button>
                    @endif
                    @error('payment_provider')<p class="form-error">{{ $message }}</p>@enderror
                @endif
            </div>

            <aside class="order-summary-card">
                <h2>Order Summary</h2>
                <h3>{{ $product->name }}</h3>
                <div class="price-line large"><del>{{ $product->currency }} {{ number_format($product->listPrice(), 0) }}</del><strong>{{ $product->currency }} {{ number_format((float) $product->price, 0) }}</strong></div>
                @if ($paymentDriver === 'demo')
                    <button type="submit" class="primary-button form-button" :disabled="submitting" x-text="submitting ? 'Processing…' : 'Pay {{ $product->currency }} {{ number_format((float) $product->price, 0) }}'">Pay {{ $product->currency }} {{ number_format((float) $product->price, 0) }}</button>
                @else
                    <p class="security-note">Access is granted only after AINCHORS verifies the completed payment with Stripe or PayPal.</p>
                @endif
            </aside>
        </form>
    </div>
</section>
@endsection
