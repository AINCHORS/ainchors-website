<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Commerce\Gateways\PayPalGateway;
use App\Services\Commerce\Gateways\StripeGateway;
use RuntimeException;
use Throwable;

class HostedPaymentService
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly PaymentService $payments,
        private readonly StripeGateway $stripe,
        private readonly PayPalGateway $paypal,
        private readonly ExternalInvoiceService $externalInvoices,
        private readonly ProviderInvoiceMailService $invoiceMailer,
    ) {}

    /** @return list<string> */
    public function availableProviders(): array
    {
        $enabled = config('commerce.payment.enabled_providers', []);

        return array_values(array_filter($enabled, fn (string $provider): bool => match ($provider) {
            'stripe' => $this->stripe->configured(),
            'paypal' => $this->paypal->configured(),
            default => false,
        }));
    }

    /** @return array{order: Order, redirect_url: string} */
    public function start(User $user, Product $product, string $idempotencyKey, string $provider): array
    {
        if (! in_array($provider, $this->availableProviders(), true)) {
            throw new RuntimeException('The selected payment provider is not configured.');
        }

        if ($provider === 'stripe') {
            [$order, $payment] = $this->checkout->prepareHostedOrder($user, $product, $idempotencyKey, 'stripe');
            $order->loadMissing(['user', 'items']);

            try {
                $session = $this->stripe->createCheckoutSession($order);
            } catch (RuntimeException $exception) {
                $this->payments->markAttemptFailed($payment, 'Stripe Checkout Session initialization failed.');

                throw $exception;
            }

            $this->payments->attachProviderReference($payment, (string) $session['id'], [
                'environment' => config('commerce.payment.environment'),
                'checkout_session_id' => $session['id'],
            ]);

            return ['order' => $order, 'redirect_url' => (string) $session['url']];
        }

        [$order, $payment] = $this->checkout->prepareHostedOrder($user, $product, $idempotencyKey, 'paypal');
        $order->loadMissing(['user', 'items']);
        $existingPayPalOrderId = (string) data_get($payment->provider_data, 'paypal_order_id', '');
        $existingApprovalUrl = (string) data_get($payment->provider_data, 'paypal_approval_url', '');
        if ($existingPayPalOrderId !== '' || $existingApprovalUrl !== '') {
            $this->assertPaymentEnvironment($payment);
            if ($existingPayPalOrderId === '' || ! $this->isTrustedPayPalApprovalUrl($existingApprovalUrl)) {
                throw new RuntimeException('The existing PayPal checkout cannot be safely reused. Please try again.');
            }

            return ['order' => $order, 'redirect_url' => $existingApprovalUrl];
        }

        try {
            $paypalOrder = $this->paypal->createOrder($order);
        } catch (RuntimeException $exception) {
            $this->payments->markAttemptFailed($payment, 'PayPal hosted checkout initialization failed.');

            throw $exception;
        }

        $paypalOrderId = (string) $paypalOrder['id'];
        $approvalUrl = (string) $paypalOrder['approval_url'];
        $this->payments->attachProviderReference($payment, $paypalOrderId, [
            'environment' => config('commerce.payment.environment'),
            'paypal_order_id' => $paypalOrderId,
            'paypal_approval_url' => $approvalUrl,
        ]);

        return ['order' => $order, 'redirect_url' => $approvalUrl];
    }

    public function completeStripeReturn(Order $order, string $sessionId): Order
    {
        if (! $order->payments()->where('provider', 'stripe')->where('provider_transaction_id', $sessionId)->exists()) {
            throw new RuntimeException('This Stripe session does not belong to the AINCHORS order.');
        }

        $session = $this->stripe->retrieveSession($sessionId);
        $this->assertStripeSessionMatchesOrder($session, $order, $sessionId);

        $completed = $this->checkout->completeHostedPayment(
            $order,
            'stripe',
            $sessionId,
            $this->safeProviderData($session),
            $this->stripeMajorAmount((int) ($session['amount_total'] ?? 0), (string) ($session['currency'] ?? '')),
            (string) ($session['currency'] ?? ''),
        );

        $this->recordStripeInvoice($completed, $session);

        return $completed;
    }

    public function syncStripeInvoice(Order $order): void
    {
        if ($order->status !== 'completed') {
            return;
        }

        $existingInvoice = $order->externalInvoices()
            ->where('provider', 'stripe')
            ->where('status', 'issued')
            ->latest('issued_at')
            ->first();
        if ($existingInvoice) {
            $this->invoiceMailer->sendOnce($existingInvoice);

            return;
        }

        $payment = $order->payments()
            ->where('provider', 'stripe')
            ->where('status', 'paid')
            ->latest('paid_at')
            ->first();
        if (! $payment || blank($payment->provider_transaction_id)) {
            return;
        }

        try {
            $this->assertPaymentEnvironment($payment);
            $session = $this->stripe->retrieveSession((string) $payment->provider_transaction_id);
            $this->assertStripeSessionMatchesOrder($session, $order, (string) $payment->provider_transaction_id);
            $this->recordStripeInvoice($order, $session);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function completePayPalReturn(Order $order, string $paypalOrderId): Order
    {
        $payment = $order->payments()
            ->where('provider', 'paypal')
            ->get()
            ->first(fn (Payment $candidate): bool => $candidate->provider_transaction_id === $paypalOrderId
                || data_get($candidate->provider_data, 'paypal_order_id') === $paypalOrderId);
        if (! $payment) {
            throw new RuntimeException('This PayPal payment does not belong to the AINCHORS order.');
        }
        $this->assertPaymentEnvironment($payment);

        if ($order->status === 'completed' && $payment->status === 'paid') {
            return $order->fresh();
        }

        $capturedOrder = $this->paypal->captureOrder($paypalOrderId, $order->idempotency_key);

        return $this->completeVerifiedPayPalOrder($order, $payment, $capturedOrder);
    }

    /** @param array<string, mixed> $session */
    public function completeStripeWebhook(array $session): void
    {
        if (($session['payment_status'] ?? null) !== 'paid') {
            return;
        }

        $orderNumber = (string) data_get($session, 'metadata.order_number', '');
        $clientReference = (string) ($session['client_reference_id'] ?? '');
        if ($orderNumber === '' || $clientReference === '' || $orderNumber !== $clientReference) {
            throw new RuntimeException('The Stripe Checkout Session does not identify one AINCHORS order.');
        }

        $order = Order::query()->where('order_number', $orderNumber)->first();
        if (! $order) {
            throw new RuntimeException('The Stripe Checkout Session references an unknown AINCHORS order.');
        }

        $this->assertStripeSessionMatchesOrder($session, $order);

        $completed = $this->checkout->completeHostedPayment(
            $order,
            'stripe',
            (string) ($session['id'] ?? ''),
            $this->safeProviderData($session),
            $this->stripeMajorAmount((int) ($session['amount_total'] ?? 0), (string) ($session['currency'] ?? '')),
            (string) ($session['currency'] ?? ''),
        );

        $this->recordStripeInvoice($completed, $session);
    }

    /** @param array<string, mixed> $resource */
    public function completePayPalWebhook(array $resource): void
    {
        $captureId = (string) ($resource['id'] ?? '');
        $paypalOrderId = (string) data_get($resource, 'supplementary_data.related_ids.order_id', '');
        if (($resource['status'] ?? null) !== 'COMPLETED' || $captureId === '' || $paypalOrderId === '') {
            throw new RuntimeException('PayPal has not confirmed a completed capture for one order.');
        }

        $payment = Payment::query()
            ->where('provider', 'paypal')
            ->whereIn('status', ['pending', 'processing', 'paid'])
            ->get()
            ->first(fn (Payment $candidate): bool => $candidate->provider_transaction_id === $paypalOrderId
                || data_get($candidate->provider_data, 'paypal_order_id') === $paypalOrderId);

        if (! $payment) {
            return;
        }
        $this->assertPaymentEnvironment($payment);

        $order = $payment->order;
        if ($order->status === 'completed' && $payment->status === 'paid') {
            return;
        }

        $this->completeVerifiedPayPalOrder(
            $order,
            $payment,
            $this->paypal->retrieveOrder($paypalOrderId),
            $captureId,
        );
    }

    /** @param array<string, mixed> $paypalOrder */
    private function completeVerifiedPayPalOrder(
        Order $order,
        Payment $payment,
        array $paypalOrder,
        ?string $expectedCaptureId = null,
    ): Order {
        $capture = $this->assertPayPalOrderMatchesOrder($paypalOrder, $order, $payment, $expectedCaptureId);

        return $this->checkout->completeHostedPayment(
            $order,
            'paypal',
            (string) $capture['id'],
            array_merge($this->safeProviderData($paypalOrder), [
                'environment' => config('commerce.payment.environment'),
                'paypal_order_id' => (string) $paypalOrder['id'],
                'paypal_capture_id' => (string) $capture['id'],
            ]),
            (string) data_get($capture, 'amount.value', ''),
            (string) data_get($capture, 'amount.currency_code', ''),
        );
    }

    /** @return array<string, mixed> */
    private function safeProviderData(array $data): array
    {
        return collect($data)->except(['customer_details', 'payment_method_details', 'payment_source', 'payer'])->all();
    }

    private function stripeMajorAmount(int $minorAmount, string $currency): string
    {
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];
        $factor = in_array(strtoupper($currency), $zeroDecimalCurrencies, true) ? 1 : 100;

        return number_format($minorAmount / $factor, 2, '.', '');
    }

    /** @param array<string, mixed> $session */
    private function recordStripeInvoice(Order $order, array $session): void
    {
        try {
            $invoice = $session['invoice'] ?? null;
            if (is_string($invoice) && $invoice !== '') {
                $invoice = $this->stripe->retrieveInvoice($invoice);
            }

            if (! is_array($invoice)) {
                return;
            }

            $invoiceId = (string) ($invoice['id'] ?? '');
            $invoiceUrl = (string) ($invoice['hosted_invoice_url'] ?? $invoice['invoice_pdf'] ?? '');
            if ($invoiceId === '' || $invoiceUrl === '') {
                return;
            }

            $externalInvoice = $this->externalInvoices->record(
                $order,
                'stripe',
                $invoiceId,
                $invoiceUrl,
                filled($invoice['number'] ?? null) ? (string) $invoice['number'] : null,
            );
            $this->invoiceMailer->sendOnce($externalInvoice);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @param array<string, mixed> $session */
    private function assertStripeSessionMatchesOrder(array $session, Order $order, ?string $expectedSessionId = null): void
    {
        $sessionId = (string) ($session['id'] ?? '');
        $orderNumber = (string) data_get($session, 'metadata.order_number', '');
        $clientReference = (string) ($session['client_reference_id'] ?? '');
        $amountTotal = $session['amount_total'] ?? null;
        $currency = (string) ($session['currency'] ?? '');
        $expectedLiveMode = config('commerce.payment.environment') === 'live';
        $payment = $order->payments()
            ->where('provider', 'stripe')
            ->where('provider_transaction_id', $sessionId)
            ->first();

        if ($sessionId === ''
            || ($expectedSessionId !== null && $sessionId !== $expectedSessionId)
            || $orderNumber !== $order->order_number
            || $clientReference !== $order->order_number
            || ($session['payment_status'] ?? null) !== 'paid'
            || ! array_key_exists('livemode', $session)
            || ! is_bool($session['livemode'])
            || $session['livemode'] !== $expectedLiveMode
            || ! is_numeric($amountTotal)
            || $currency === ''
            || ! $payment) {
            throw new RuntimeException('Stripe has not confirmed this order as paid.');
        }

        $this->assertPaymentEnvironment($payment);
    }

    /**
     * @param  array<string, mixed>  $paypalOrder
     * @return array<string, mixed>
     */
    private function assertPayPalOrderMatchesOrder(
        array $paypalOrder,
        Order $order,
        Payment $payment,
        ?string $expectedCaptureId = null,
    ): array {
        $paypalOrderId = (string) ($paypalOrder['id'] ?? '');
        $purchaseUnits = (array) ($paypalOrder['purchase_units'] ?? []);
        $purchaseUnit = is_array($purchaseUnits[0] ?? null) ? $purchaseUnits[0] : [];
        $captures = (array) data_get($purchaseUnit, 'payments.captures', []);
        $capture = is_array($captures[0] ?? null) ? $captures[0] : [];
        $captureId = (string) ($capture['id'] ?? '');
        $relatedOrderId = (string) data_get($capture, 'supplementary_data.related_ids.order_id', '');
        if ($relatedOrderId === '') {
            $upLink = collect($capture['links'] ?? [])->first(
                fn ($link): bool => is_array($link) && ($link['rel'] ?? null) === 'up',
            );
            $relatedOrderId = basename((string) data_get($upLink, 'href', ''));
        }
        $expectedAmount = number_format(
            (float) $order->total_amount,
            strtoupper($order->currency) === 'JPY' ? 0 : 2,
            '.',
            '',
        );

        if (($paypalOrder['status'] ?? null) !== 'COMPLETED'
            || $paypalOrderId === ''
            || $payment->provider_transaction_id !== $paypalOrderId
            || data_get($payment->provider_data, 'paypal_order_id') !== $paypalOrderId
            || count($purchaseUnits) !== 1
            || (string) ($purchaseUnit['reference_id'] ?? '') !== $order->order_number
            || (string) ($purchaseUnit['custom_id'] ?? '') !== $order->order_number
            || (string) data_get($purchaseUnit, 'amount.value', '') !== $expectedAmount
            || strtoupper((string) data_get($purchaseUnit, 'amount.currency_code', '')) !== strtoupper($order->currency)
            || count($captures) !== 1
            || ($capture['status'] ?? null) !== 'COMPLETED'
            || $captureId === ''
            || ($expectedCaptureId !== null && $captureId !== $expectedCaptureId)
            || $relatedOrderId !== $paypalOrderId
            || (string) data_get($capture, 'amount.value', '') !== $expectedAmount
            || strtoupper((string) data_get($capture, 'amount.currency_code', '')) !== strtoupper($order->currency)) {
            throw new RuntimeException('PayPal has not confirmed this order as an exact completed payment.');
        }

        return $capture;
    }

    private function assertPaymentEnvironment(Payment $payment): void
    {
        $configured = config('commerce.payment.environment');
        $expected = match ($configured) {
            'sandbox' => 'test',
            'live' => 'live',
            default => throw new RuntimeException('PAYMENT_ENVIRONMENT must be either sandbox or live.'),
        };

        if ($payment->payment_environment !== $expected
            || data_get($payment->provider_data, 'environment') !== $configured) {
            throw new RuntimeException('The payment environment does not match the configured provider environment.');
        }
    }

    private function isTrustedPayPalApprovalUrl(string $url): bool
    {
        $parts = parse_url($url);
        $expectedHost = config('commerce.payment.environment') === 'live'
            ? 'www.paypal.com'
            : 'www.sandbox.paypal.com';

        return ($parts['scheme'] ?? null) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === $expectedHost
            && ! isset($parts['user'], $parts['pass']);
    }
}
