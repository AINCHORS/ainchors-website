@extends('layouts.app')

@section('title', 'Complete PayPal Payment | AINCHORS')

@section('content')
<section class="success-section">
    <div class="success-card success-card-compact payment-waiting-card"
         data-paypal-waiting
         data-invoice-url="{{ $invoiceUrl }}"
         data-status-url="{{ route('payments.paypal.status', $order) }}"
         data-paypal-window-name="ainchors-paypal-payment">
        <span class="eyebrow">Secure PayPal payment</span>
        <h1>Complete payment with PayPal</h1>
        <p>PayPal will open automatically in the payment window. Keep this AINCHORS page open while you pay; AINCHORS will continue only after the server verifies the payment directly with PayPal.</p>
        @if (session('payment_cancel_error'))
            <p class="form-error" role="alert">{{ session('payment_cancel_error') }}</p>
        @endif
        <p class="payment-waiting-status" role="status" aria-live="polite">Opening PayPal and waiting for verified payment confirmation…</p>
        <div class="success-actions payment-waiting-actions">
            <a data-paypal-open class="success-action-button" href="{{ $invoiceUrl }}" target="ainchors-paypal-payment" aria-label="Reopen PayPal Payment">Reopen PayPal</a>
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
    const providerWindowName = card.dataset.paypalWindowName;
    const openButton = card.querySelector('[data-paypal-open]');
    const cancelButton = card.querySelector('[data-paypal-cancel]');
    const status = card.querySelector('[role="status"]');
    const windowFeatures = 'popup=yes,width=560,height=760,resizable=yes,scrollbars=yes';
    let providerWindow = null;
    let providerWindowSeen = false;
    let providerWindowClosed = false;
    let statusTimer = null;
    let windowTimer = null;
    let checking = false;
    let terminal = false;

    const clearTimers = () => {
        window.clearTimeout(statusTimer);
        window.clearTimeout(windowTimer);
    };

    const closePaymentWindow = () => {
        if (!providerWindow || providerWindow.closed) return;
        try {
            providerWindow.close();
        } catch (_) {
            // Provider windows are best-effort to close after a terminal server state.
        }
    };

    const openPaymentWindow = () => {
        const opened = window.open(invoiceUrl, providerWindowName, windowFeatures);
        if (!opened) {
            status.textContent = 'Your browser blocked the PayPal payment window. Select Reopen PayPal to continue.';
            return null;
        }

        providerWindow = opened;
        providerWindowSeen = true;
        providerWindowClosed = false;
        try {
            providerWindow.opener = null;
            providerWindow.focus();
        } catch (_) {
            // Cross-origin provider navigation can limit window access.
        }
        status.textContent = 'Waiting for verified PayPal payment confirmation…';

        return providerWindow;
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
                closePaymentWindow();
                window.location.assign(result.redirect_url);
                return;
            }

            if (!providerWindowClosed) {
                status.textContent = 'Waiting for verified PayPal payment confirmation…';
            }
        } catch (_) {
            status.textContent = 'Confirmation is taking longer than expected. Keep this page open while AINCHORS checks the payment status.';
        } finally {
            checking = false;
            scheduleCheck();
        }
    };

    const watchPaymentWindow = () => {
        window.clearTimeout(windowTimer);
        if (terminal) return;

        if (providerWindowSeen && providerWindow && providerWindow.closed && !providerWindowClosed) {
            providerWindowClosed = true;
            status.textContent = 'The PayPal payment window was closed. If you already paid, confirmation may still take a moment. Otherwise reopen PayPal or cancel this payment.';
            scheduleCheck(0);
        }

        windowTimer = window.setTimeout(watchPaymentWindow, 750);
    };

    openButton?.addEventListener('click', (event) => {
        event.preventDefault();
        openPaymentWindow();
    });

    cancelButton?.addEventListener('click', () => {
        closePaymentWindow();
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) scheduleCheck(0);
    });

    window.addEventListener('pageshow', () => {
        clearTimers();
        openPaymentWindow();
        scheduleCheck(0);
        watchPaymentWindow();
    });

    window.addEventListener('pagehide', clearTimers);
})();
</script>
@endsection
