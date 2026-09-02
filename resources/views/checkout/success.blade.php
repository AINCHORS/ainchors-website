@extends('layouts.app')

@php($item = $order->items->first())
@php($product = $item->product)
@php($payment = $order->payments->firstWhere('status', 'paid'))

@section('title', 'Payment Successful | AINCHORS')

@section('content')
<section class="success-section" data-success-redirect="{{ route('purchase-history') }}" data-success-seconds="5">
    <div class="success-card success-card-compact">
        <div class="success-icon">✓</div>
        <span class="eyebrow">Payment complete</span>
        <h1>Payment Successful</h1>
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
        <div class="success-actions">
            @if ($product->isCourse())<a class="success-action-button" href="{{ route('learn.show', $product) }}">Access Course</a>@endif
            @if ($product->isCourse() || $product->isPackage())
                <a class="success-action-button" href="{{ route('my-courses') }}">{{ $product->isPackage() ? 'Go to My Courses' : 'My Courses' }}</a>
            @else
                <a class="success-action-button" href="{{ route('purchase-history') }}">Purchase History</a>
            @endif
            @if ($invoice)
                <a class="success-action-button" href="{{ route('purchase-history.invoice', $invoice) }}" target="_blank" rel="noopener noreferrer">View Receipt</a>
            @endif
        </div>
        @if (! $invoice && $payment?->provider === 'stripe')
            <p class="invoice-pending-note">The Stripe provider invoice is being prepared and will appear in Purchase History when available.</p>
        @elseif (! $invoice)
            <p class="invoice-pending-note">The verified payment and order reference are available in Purchase History.</p>
        @endif
        <p class="success-countdown" role="status" aria-live="polite">Redirecting in <span>5</span> seconds…</p>
    </div>
</section>

<script>
(() => {
    const section = document.querySelector('[data-success-redirect]');
    if (!section) return;

    const output = section.querySelector('.success-countdown span');
    let seconds = Number(section.dataset.successSeconds || 5);
    let cancelled = false;
    let timer;

    const stopRedirect = () => {
        cancelled = true;
        window.clearTimeout(timer);
    };
    section.querySelectorAll('.success-actions a').forEach(link => link.addEventListener('click', stopRedirect));

    const tick = () => {
        if (cancelled) return;
        output.textContent = String(seconds);
        if (seconds <= 0) {
            window.location.assign(section.dataset.successRedirect);
            return;
        }
        seconds -= 1;
        timer = window.setTimeout(tick, 1000);
    };
    tick();
})();
</script>
@endsection
