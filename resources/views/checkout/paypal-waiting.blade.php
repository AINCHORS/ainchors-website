@extends('layouts.app')

@section('title', 'Complete PayPal Payment | AINCHORS')

@section('content')
<section class="success-section">
    <div class="success-card payment-waiting-card"
         data-paypal-waiting
         data-invoice-url="{{ $invoiceUrl }}"
         data-status-url="{{ route('payments.paypal.status', $order) }}"
         data-storage-key="ainchors-paypal-opened-{{ $order->order_number }}">
        <span class="eyebrow">Secure PayPal payment</span>
        <h1>Complete payment with PayPal</h1>
        <p>Pay through the genuine PayPal-hosted invoice. This page will only continue after AINCHORS receives and verifies PayPal's signed payment notification.</p>
        <p class="payment-waiting-status" role="status" aria-live="polite">Waiting for verified payment confirmation…</p>
        <div class="success-actions">
            <a class="success-action-button" href="{{ $invoiceUrl }}">Open PayPal Invoice</a>
            <a class="success-action-button" href="{{ route('purchase-history') }}">Purchase History</a>
        </div>
    </div>
</section>

<script>
(() => {
    const card = document.querySelector('[data-paypal-waiting]');
    if (!card) return;

    const invoiceUrl = card.dataset.invoiceUrl;
    const statusUrl = card.dataset.statusUrl;
    const storageKey = card.dataset.storageKey;
    const status = card.querySelector('[role="status"]');
    let timer;

    const checkStatus = async () => {
        try {
            const response = await fetch(statusUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) throw new Error('status unavailable');
            const result = await response.json();
            if (result.state === 'completed' || result.state === 'failed') {
                window.location.assign(result.redirect_url);
                return;
            }
            status.textContent = 'Waiting for verified payment confirmation…';
        } catch (_) {
            status.textContent = 'Confirmation is taking longer than expected. Keep this page open or check Purchase History.';
        }
        timer = window.setTimeout(checkStatus, 2000);
    };

    window.addEventListener('pageshow', () => {
        window.clearTimeout(timer);
        checkStatus();
    });

    if (window.sessionStorage.getItem(storageKey) !== 'yes') {
        window.sessionStorage.setItem(storageKey, 'yes');
        window.location.assign(invoiceUrl);
    }
})();
</script>
@endsection
