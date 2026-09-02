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
        $this->assertPaymentEnvironment($payment);
        if (filled(data_get($payment->provider_data, 'paypal_order_id'))) {
            throw new RuntimeException('This legacy PayPal attempt cannot be reused. Please start a new checkout.');
        }

        $invoiceId = (string) data_get($payment->provider_data, 'paypal_invoice_id', '');
        if ($invoiceId === '') {
            try {
                $created = $this->paypal->createInvoice($order);
                $invoiceId = (string) $created['invoice_id'];
                $payment = $this->payments->attachProviderReference($payment, $invoiceId, [
                    'environment' => config('commerce.payment.environment'),
                    'paypal_invoice_id' => $invoiceId,
                    'stage' => 'paypal_invoice_draft_created',
                ]);
            } catch (RuntimeException $exception) {
                $this->payments->markAttemptFailed($payment, 'PayPal provider invoice initialization failed.');
                throw $exception;
            }
        }

        $invoice = $this->paypal->retrieveInvoice($invoiceId);
        if (($invoice['status'] ?? null) === 'DRAFT') {
            $this->paypal->sendInvoice($invoiceId, $order->idempotency_key);
            $invoice = $this->paypal->retrieveInvoice($invoiceId);
        }

        if (($invoice['status'] ?? null) === 'PAID') {
            return [
                'order' => $this->completeVerifiedPayPalInvoice($order, $payment, $invoice),
                'redirect_url' => route('checkout.success', $order),
            ];
        }

        $invoiceUrl = $this->assertPayPalInvoiceCanBePaid($invoice, $order, $payment);
        $this->externalInvoices->record(
            $order,
            'paypal',
            $invoiceId,
            $invoiceUrl,
            filled(data_get($invoice, 'detail.invoice_number')) ? (string) data_get($invoice, 'detail.invoice_number') : null,
            'unpaid',
        );

        return ['order' => $order, 'redirect_url' => $invoiceUrl];
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

    public function completePayPalInvoiceWebhook(string $invoiceId): void
    {
        if (! str_starts_with($invoiceId, 'INV2-')) {
            throw new RuntimeException('PayPal did not identify one provider invoice.');
        }

        $payment = Payment::query()
            ->where('provider', 'paypal')
            ->whereIn('status', ['pending', 'processing', 'paid'])
            ->get()
            ->first(fn (Payment $candidate): bool => data_get($candidate->provider_data, 'paypal_invoice_id') === $invoiceId);

        if (! $payment) {
            return;
        }
        $this->assertPaymentEnvironment($payment);

        $this->completeVerifiedPayPalInvoice($payment->order, $payment, $this->paypal->retrieveInvoice($invoiceId));
    }

    /** @param array<string, mixed> $invoice */
    private function completeVerifiedPayPalInvoice(Order $order, Payment $payment, array $invoice): Order
    {
        $verified = $this->assertPayPalInvoicePaid($invoice, $order, $payment);
        $completed = $this->checkout->completeHostedPayment(
            $order,
            'paypal',
            $verified['payment_id'],
            [
                'environment' => config('commerce.payment.environment'),
                'paypal_invoice_id' => $verified['invoice_id'],
                'paypal_payment_id' => $verified['payment_id'],
                'stage' => 'paypal_invoice_paid_verified',
            ],
            $verified['amount'],
            $verified['currency'],
        );

        $externalInvoice = $this->externalInvoices->record(
            $completed,
            'paypal',
            $verified['invoice_id'],
            $verified['invoice_url'],
            $verified['invoice_number'],
            'paid',
        );
        $this->invoiceMailer->sendOnce($externalInvoice);

        return $completed;
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

    /** @param array<string, mixed> $invoice */
    private function assertPayPalInvoiceCanBePaid(array $invoice, Order $order, Payment $payment): string
    {
        $invoiceId = (string) ($invoice['id'] ?? '');
        $invoiceUrl = (string) data_get($invoice, 'detail.metadata.recipient_view_url', '');
        if (($invoice['status'] ?? null) !== 'UNPAID'
            || $invoiceId === ''
            || data_get($payment->provider_data, 'paypal_invoice_id') !== $invoiceId
            || (string) data_get($invoice, 'detail.reference', '') !== $order->order_number
            || ! $this->moneyMatches((string) data_get($invoice, 'amount.value', ''), (string) $order->total_amount, $order->currency)
            || strtoupper((string) data_get($invoice, 'amount.currency_code', '')) !== strtoupper($order->currency)
            || ! $this->externalInvoices->isTrustedProviderUrl('paypal', $invoiceUrl)) {
            throw new RuntimeException('PayPal did not return a payable invoice for this exact order.');
        }

        return $invoiceUrl;
    }

    /**
     * @param array<string, mixed> $invoice
     * @return array{invoice_id:string,payment_id:string,amount:string,currency:string,invoice_url:string,invoice_number:?string}
     */
    private function assertPayPalInvoicePaid(array $invoice, Order $order, Payment $payment): array
    {
        $invoiceId = (string) ($invoice['id'] ?? '');
        $allTransactions = collect(data_get($invoice, 'payments.transactions', []));
        $transactions = $allTransactions
            ->filter(fn ($transaction): bool => is_array($transaction)
                && strtoupper((string) ($transaction['type'] ?? '')) === 'PAYPAL'
                && strtoupper((string) ($transaction['method'] ?? '')) === 'PAYPAL'
                && strtoupper((string) ($transaction['transaction_status'] ?? '')) === 'SUCCESS'
                && filled($transaction['payment_id'] ?? null));
        if ($allTransactions->count() !== 1 || $transactions->count() !== 1) {
            throw new RuntimeException('PayPal has not confirmed one genuine successful PayPal transaction.');
        }

        $transaction = $transactions->first();
        $amount = (string) data_get($transaction, 'amount.value', '');
        $currency = strtoupper((string) data_get($transaction, 'amount.currency_code', ''));
        $paidAmount = (string) data_get($invoice, 'payments.paid_amount.value', '');
        $paidCurrency = strtoupper((string) data_get($invoice, 'payments.paid_amount.currency_code', ''));
        $invoiceUrl = (string) data_get($invoice, 'detail.metadata.recipient_view_url', '');

        if (($invoice['status'] ?? null) !== 'PAID'
            || $invoiceId === ''
            || data_get($payment->provider_data, 'paypal_invoice_id') !== $invoiceId
            || (string) data_get($invoice, 'detail.reference', '') !== $order->order_number
            || ! $this->moneyMatches((string) data_get($invoice, 'amount.value', ''), (string) $order->total_amount, $order->currency)
            || ! $this->moneyMatches($paidAmount, (string) $order->total_amount, $order->currency)
            || ! $this->moneyMatches($amount, (string) $order->total_amount, $order->currency)
            || strtoupper((string) data_get($invoice, 'amount.currency_code', '')) !== strtoupper($order->currency)
            || $paidCurrency !== strtoupper($order->currency)
            || $currency !== strtoupper($order->currency)
            || ! $this->externalInvoices->isTrustedProviderUrl('paypal', $invoiceUrl)) {
            throw new RuntimeException('PayPal has not confirmed this invoice as an exact completed payment.');
        }

        return [
            'invoice_id' => $invoiceId,
            'payment_id' => (string) $transaction['payment_id'],
            'amount' => $amount,
            'currency' => $currency,
            'invoice_url' => $invoiceUrl,
            'invoice_number' => filled(data_get($invoice, 'detail.invoice_number')) ? (string) data_get($invoice, 'detail.invoice_number') : null,
        ];
    }

    private function moneyMatches(string $actual, string $expected, string $currency): bool
    {
        $scale = strtoupper($currency) === 'JPY' ? 0 : 2;
        $normalize = static function (string $value) use ($scale): ?string {
            if (! preg_match('/^\d+(?:\.\d+)?$/', $value)) {
                return null;
            }
            [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
            if (strlen($fraction) > $scale && trim(substr($fraction, $scale), '0') !== '') {
                return null;
            }
            $whole = ltrim($whole, '0') ?: '0';

            return $scale === 0 ? $whole : $whole.str_pad(substr($fraction, 0, $scale), $scale, '0');
        };

        return $normalize($actual) !== null && $normalize($actual) === $normalize($expected);
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

}
