@extends('layouts.app')

@php($item = $order->items->first())
@php($product = $item->product)
@php($payment = $order->payments->firstWhere('status', 'paid'))

@section('title', 'Payment Successful | AINCHORS')

@section('content')
<section class="success-section">
    <div class="success-card payment-state-card">
        <div class="success-icon">✓</div>
        <span class="eyebrow">Payment complete</span>
        <h1 class="payment-result-title">Payment Successful</h1>
        <h2>{{ $item->product_name }}</h2>
        <p class="success-price">{{ $order->currency }} {{ number_format((float) $order->total_amount, 0) }}</p>
        @if ($product->isPackage())
            <p><strong>{{ $product->bundleProducts()->count() }} Courses Unlocked</strong></p>
        @elseif ($product->isCourse())
            <p>Your course is now available.</p>
        @else
            <p>Your service order is confirmed. The AINCHORS team can now follow up from the recorded order.</p>
        @endif
        <div class="transaction-reference"><span>Transaction Reference</span><strong>{{ $payment?->provider_transaction_id }}</strong></div>
        <div class="success-actions payment-state-actions">
            @if ($product->isCourse())<a class="payment-state-button" href="{{ route('learn.show', $product) }}">Access Course</a>@endif
            @if ($product->isCourse() || $product->isPackage())
                <a class="payment-state-button" href="{{ route('my-courses') }}">{{ $product->isPackage() ? 'Go to My Courses' : 'My Courses' }}</a>
            @else
                <a class="payment-state-button" href="{{ route('purchase-history') }}">Purchase History</a>
            @endif
            @if ($invoice)
                <a class="payment-state-button" href="{{ route('purchase-history.invoice', $invoice) }}" target="_blank" rel="noopener noreferrer">View Receipt</a>
            @endif
        </div>
        @if (! $invoice && $payment?->provider === 'stripe')
            <p class="invoice-pending-note">The Stripe provider invoice is being prepared and will appear in Purchase History when available.</p>
        @elseif (! $invoice)
            <p class="invoice-pending-note">The verified payment and order reference are available in Purchase History.</p>
        @endif
    </div>
</section>
@include('checkout.partials.payment-state-styles')
@endsection
