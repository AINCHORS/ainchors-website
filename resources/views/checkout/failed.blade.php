@extends('layouts.app')

@php($product = $item->product)

@section('title', 'Payment Unsuccessful | AINCHORS')

@section('content')
<section class="success-section">
    <div class="success-card">
        <div class="success-icon is-unsuccessful" aria-hidden="true">×</div>
        <span class="eyebrow">Payment not completed</span>
        <h1>Payment Unsuccessful</h1>
        <h2>{{ $item->product_name }}</h2>

        @if ($state === 'cancelled')
            <p>Your {{ $provider ? str($provider)->headline().' ' : '' }}payment was cancelled. You have not been charged.</p>
        @else
            <p>We could not confirm this payment. Check My Courses or Purchase History before trying again.</p>
        @endif

        <div class="transaction-reference">
            <span>Order Reference</span>
            <strong>{{ $order->order_number }}</strong>
        </div>

        <div class="success-actions">
            <a class="primary-button" href="{{ route('checkout.show', $product) }}">Try Again</a>
            @if ($product->isCourse() || $product->isPackage())
                <a class="secondary-button" href="{{ route('my-courses') }}">My Courses</a>
            @endif
            <a class="secondary-button" href="{{ route('purchase-history') }}">Purchase History</a>
        </div>
    </div>
</section>
@endsection
