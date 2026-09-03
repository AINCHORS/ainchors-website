<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Connecting to PayPal | AINCHORS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<section class="success-section">
    <div class="success-card success-card-compact">
        <span class="eyebrow">Secure PayPal payment</span>
        <h1>Connecting to PayPal</h1>
        <p>AINCHORS is preparing your secure PayPal payment. This tab will continue to PayPal automatically.</p>
        <p id="paypal-handoff-status" class="payment-waiting-status" role="status" aria-live="polite">Waiting for the secure PayPal invoice…</p>
    </div>
</section>

<script>
(() => {
    const origin = window.location.origin;
    const status = document.getElementById('paypal-handoff-status');
    let readyTimer = null;

    const announceReady = () => {
        window.clearTimeout(readyTimer);

        if (!window.opener || window.opener.closed) {
            status.textContent = 'The AINCHORS checkout tab is no longer available. Return to AINCHORS and reopen PayPal from the payment waiting page.';
            return;
        }

        try {
            window.opener.postMessage({ type: 'ainchors-paypal-handoff-ready' }, origin);
        } catch (_) {
            status.textContent = 'Unable to connect this tab to the AINCHORS checkout. Return to AINCHORS and reopen PayPal.';
            return;
        }

        readyTimer = window.setTimeout(announceReady, 500);
    };

    window.addEventListener('message', (event) => {
        if (event.origin !== origin || event.source !== window.opener) return;
        if (!event.data || event.data.type !== 'ainchors-paypal-handoff-invoice') return;

        const invoiceUrl = typeof event.data.invoiceUrl === 'string' ? event.data.invoiceUrl : '';
        let target;
        try {
            target = new URL(invoiceUrl);
        } catch (_) {
            return;
        }

        if (target.protocol !== 'https:' || !/(^|\.)paypal\.com$/i.test(target.hostname)) {
            status.textContent = 'AINCHORS could not verify the PayPal destination. Return to the payment waiting page and try again.';
            return;
        }

        window.clearTimeout(readyTimer);
        status.textContent = 'Opening PayPal…';

        try {
            window.opener = null;
        } catch (_) {
            // Navigation continues even if the browser keeps an opaque opener handle.
        }

        window.location.replace(invoiceUrl);
    });

    announceReady();
})();
</script>
</body>
</html>
