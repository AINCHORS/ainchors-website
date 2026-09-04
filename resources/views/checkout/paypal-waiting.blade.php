@extends('layouts.app')

@php($item = $order->items->first())

@section('title', 'Awaiting Payment | AINCHORS')

@section('content')
<section class="success-section">
    <div class="success-card payment-state-card payment-waiting-card"
         data-paypal-waiting
         data-status-url="{{ route('payments.paypal.status', $order) }}">
        <div class="success-icon is-pending" aria-hidden="true">◷</div>
        <span class="eyebrow">Payment pending</span>
        <h1 class="payment-result-title">Awaiting Payment</h1>
        <h2>{{ $item->product_name }}</h2>
        <p class="payment-state-highlight">PAYPAL</p>
        <p>Complete your payment in the PayPal tab. This page will update automatically once AINCHORS verifies the payment.</p>
        @if (session('payment_cancel_error'))
            <p class="form-error" role="alert">{{ session('payment_cancel_error') }}</p>
        @endif
        <p class="payment-waiting-status" role="status" aria-live="polite">Waiting for verified PayPal payment confirmation…</p>
        <div class="transaction-reference">
            <span>Order Reference</span>
            <strong>{{ $order->order_number }}</strong>
        </div>
        <div class="success-actions payment-state-actions">
            <a data-paypal-open class="payment-state-button" href="{{ $invoiceUrl }}" target="ainchors-paypal-payment" rel="noopener noreferrer" aria-label="Reopen PayPal Payment">Reopen PayPal</a>
            <a data-paypal-cancel class="payment-state-button" href="{{ route('payments.cancel', ['provider' => 'paypal', 'order' => $order]) }}">Cancel Payment</a>
            <a class="payment-state-button" href="{{ route('purchase-history') }}">Purchase History</a>
        </div>
    </div>
</section>
@include('checkout.partials.payment-state-styles')

<script>
(() => {
    const card = document.querySelector('[data-paypal-waiting]');
    if (!card) return;

    const statusUrl = card.dataset.statusUrl;
    const status = card.querySelector('[role="status"]');
    let statusTimer = null;
    let checking = false;
    let terminal = false;

    const clearTimers = () => {
        window.clearTimeout(statusTimer);
    };

    const scheduleCheck = (delay = 2000) => {
        window.clearTimeout(statusTimer);
        if (!terminal) statusTimer = window.setTimeout(checkStatus, delay);
    };

    const checkStatus = async () => {
        if (terminal || checking) return;
        checking = true;

        try {
            const response = await fetch(statusUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) throw new Error('status unavailable');

            const result = await response.json();
            if (['completed', 'failed', 'cancelled'].includes(result.state)) {
                terminal = true;
                clearTimers();
                window.location.assign(result.redirect_url);
                return;
            }

            status.textContent = 'Waiting for verified PayPal payment confirmation…';
        } catch (_) {
            status.textContent = 'Confirmation is taking longer than expected. Keep this page open while AINCHORS checks the payment status.';
        } finally {
            checking = false;
            scheduleCheck();
        }
    };

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) scheduleCheck(0);
    });

    window.addEventListener('pageshow', () => {
        clearTimers();
        scheduleCheck(0);
    });

    window.addEventListener('pagehide', clearTimers);
})();
</script>
@endsection
