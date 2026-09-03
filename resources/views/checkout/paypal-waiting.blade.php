@extends('layouts.app')

@section('title', 'Complete PayPal Payment | AINCHORS')

@section('content')
<section class="success-section">
    <div class="success-card success-card-compact payment-waiting-card"
         data-paypal-waiting
         data-invoice-url="{{ $invoiceUrl }}"
         data-status-url="{{ route('payments.paypal.status', $order) }}">
        <span class="eyebrow">Secure PayPal payment</span>
        <h1>Complete payment with PayPal</h1>
        <p>PayPal opens in a separate browser tab while this AINCHORS page stays available to verify the payment. AINCHORS will continue only after the server confirms the payment directly with PayPal.</p>
        @if (session('payment_cancel_error'))
            <p class="form-error" role="alert">{{ session('payment_cancel_error') }}</p>
        @endif
        <p class="payment-waiting-status" role="status" aria-live="polite">Connecting the PayPal tab and waiting for verified payment confirmation…</p>
        <div class="success-actions payment-waiting-actions">
            <a data-paypal-open class="success-action-button" href="{{ $invoiceUrl }}" target="ainchors-paypal-payment" rel="noopener noreferrer" aria-label="Reopen PayPal Payment">Reopen PayPal</a>
            <a data-paypal-cancel class="success-action-button" href="{{ route('payments.cancel', ['provider' => 'paypal', 'order' => $order]) }}">Cancel Payment</a>
            <a class="success-action-button" href="{{ route('purchase-history') }}">Purchase History</a>
        </div>
    </div>
</section>

<style>
.payment-waiting-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    width: 100%;
}
.payment-waiting-actions .success-action-button {
    box-sizing: border-box;
    width: 100%;
    min-width: 0;
}
@media (max-width: 640px) {
    .payment-waiting-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
(() => {
    const card = document.querySelector('[data-paypal-waiting]');
    if (!card) return;

    const invoiceUrl = card.dataset.invoiceUrl;
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

    window.addEventListener('message', (event) => {
        if (terminal || event.origin !== window.location.origin) return;
        if (!event.data || event.data.type !== 'ainchors-paypal-handoff-ready') return;

        try {
            event.source?.postMessage({
                type: 'ainchors-paypal-handoff-invoice',
                invoiceUrl,
            }, event.origin);
            status.textContent = 'PayPal is opening in the payment tab. Waiting for verified payment confirmation…';
        } catch (_) {
            status.textContent = 'PayPal could not open automatically. Select Reopen PayPal to continue.';
        }
    });

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
