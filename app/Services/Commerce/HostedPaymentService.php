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
        try {
            $payPalOrder = $this->paypal->createOrder($order);
        } catch (RuntimeException $exception) {
            $this->payments->markAttemptFailed($payment, 'PayPal Order initialization failed.');

            throw $exception;
        }

        $this->payments->attachProviderReference($payment, (string) $payPalOrder['id'], [
            'environment' => config('commerce.payment.environment'),
            'paypal_order_id' => $payPalOrder['id'],
        ]);

        return ['order' => $order, 'redirect_url' => (string) $payPalOrder['approval_url']];
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

    public function completePayPalReturn(Order $order, string $payPalOrderId): Order
    {
        if ($order->status === 'completed') {
            return $order->fresh(['items.product', 'payments']);
        }

        $payment = $order->payments()
            ->where('provider', 'paypal')
            ->where('provider_transaction_id', $payPalOrderId)
            ->first();
        if (! $payment) {
            throw new RuntimeException('This PayPal order does not belong to the AINCHORS order.');
        }

        $capture = $this->paypal->captureOrder($payPalOrderId, $order->idempotency_key.'-paypal-capture');
        $captureResource = data_get($capture, 'purchase_units.0.payments.captures.0', []);
        $this->assertPayPalCaptureMatchesOrder($capture, $order, $payPalOrderId);

        return $this->checkout->completeHostedPayment(
            $order,
            'paypal',
            (string) data_get($captureResource, 'id'),
            $this->safeProviderData($capture),
            (string) data_get($captureResource, 'amount.value', '0'),
            (string) data_get($captureResource, 'amount.currency_code', ''),
        );
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

    /** @param array<string, mixed> $capture */
    public function completePayPalWebhook(array $capture): void
    {
        $payPalOrderId = (string) data_get($capture, 'supplementary_data.related_ids.order_id', '');
        $payment = Payment::query()
            ->where('provider', 'paypal')
            ->where('status', 'pending')
            ->get()
            ->first(fn (Payment $candidate): bool => data_get($candidate->provider_data, 'paypal_order_id') === $payPalOrderId);

        if (! $payment || ($capture['status'] ?? null) !== 'COMPLETED') {
            return;
        }

        $this->checkout->completeHostedPayment(
            $payment->order,
            'paypal',
            (string) ($capture['id'] ?? ''),
            $this->safeProviderData($capture),
            (string) data_get($capture, 'amount.value', '0'),
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

            $this->externalInvoices->record(
                $order,
                'stripe',
                $invoiceId,
                $invoiceUrl,
                filled($invoice['number'] ?? null) ? (string) $invoice['number'] : null,
            );
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

        if ($sessionId === ''
            || ($expectedSessionId !== null && $sessionId !== $expectedSessionId)
            || $orderNumber !== $order->order_number
            || $clientReference !== $order->order_number
            || ($session['payment_status'] ?? null) !== 'paid'
            || ! is_numeric($amountTotal)
            || $currency === '') {
            throw new RuntimeException('Stripe has not confirmed this order as paid.');
        }
    }

    /** @param array<string, mixed> $capture */
    private function assertPayPalCaptureMatchesOrder(array $capture, Order $order, string $expectedOrderId): void
    {
        $purchaseUnit = (array) data_get($capture, 'purchase_units.0', []);
        $captureResource = (array) data_get($purchaseUnit, 'payments.captures.0', []);
        $relatedOrderId = (string) data_get($captureResource, 'supplementary_data.related_ids.order_id', $capture['id'] ?? '');
        $reference = (string) ($purchaseUnit['reference_id'] ?? '');
        $customId = (string) ($purchaseUnit['custom_id'] ?? '');

        if (($capture['status'] ?? null) !== 'COMPLETED'
            || blank($captureResource['id'] ?? null)
            || ($relatedOrderId !== '' && $relatedOrderId !== $expectedOrderId)
            || ($reference !== '' && $reference !== $order->order_number)
            || ($customId !== '' && $customId !== $order->order_number)) {
            throw new RuntimeException('PayPal has not confirmed this order as paid.');
        }
    }
}
