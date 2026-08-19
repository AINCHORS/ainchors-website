@extends('layouts.app')

@php($product = $order->items->first()->product)
@php($payment = $order->payments->firstWhere('status', 'paid'))

@section('title', 'Payment Successful | AINCHORS')

@section('content')
<section class="success-section">
    <div class="success-card">
        <div class="success-icon">✓</div>
        <span class="eyebrow">Test payment complete</span>
        <h1>Payment Successful</h1>
        <h2>{{ $product->name }}</h2>
        <p class="success-price">USD {{ number_format((float) $order->total_amount, 0) }}</p>
        @if ($product->isPackage())
            <p><strong>{{ $product->bundleProducts()->count() }} Courses Unlocked</strong></p>
        @else
            <p>Your course is now available.</p>
        @endif
        <div class="transaction-reference"><span>Transaction Reference</span><strong>{{ $payment?->provider_transaction_id }}</strong></div>
        <div class="success-actions">
            @if ($product->isCourse())<a class="primary-button" href="{{ route('learn.show', $product) }}">Access Course</a>@endif
            <a class="secondary-button" href="{{ route('my-courses') }}">{{ $product->isPackage() ? 'Go to My Courses' : 'My Courses' }}</a>
        </div>
    </div>
</section>
@endsection
