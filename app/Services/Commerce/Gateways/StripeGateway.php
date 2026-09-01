<?php

namespace App\Services\Commerce\Gateways;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class StripeGateway
{
    public function configured(): bool
    {
        $secret = (string) config('commerce.payment.stripe.secret');

        $environment = match (config('commerce.payment.environment')) {
            'sandbox' => 'test',
            'live' => 'live',
            default => null,
        };

        return ($environment === 'test' && (str_starts_with($secret, 'rk_test_') || str_starts_with($secret, 'sk_test_')))
            || ($environment === 'live' && (str_starts_with($secret, 'rk_live_') || str_starts_with($secret, 'sk_live_')));
    }

    /** @return array<string, mixed> */
    public function createCheckoutSession(Order $order): array
    {
        $item = $order->items->firstOrFail();
        $successUrl = route('payments.stripe.return', $order).'?session_id={CHECKOUT_SESSION_ID}';

        try {
            $response = $this->client()
                ->withHeaders([
                    'Idempotency-Key' => $order->idempotency_key.'-stripe',
                    'Stripe-Version' => '2026-07-29.dahlia',
                ])
                ->asForm()
                ->post($this->apiUrl('/v1/checkout/sessions'), [
                    'mode' => 'payment',
                    'integration_identifier' => $this->integrationIdentifier($order),
                    'client_reference_id' => $order->order_number,
                    'customer_email' => $order->user->email,
                    'success_url' => $successUrl,
                    'cancel_url' => route('payments.cancel', ['order' => $order, 'provider' => 'stripe']),
                    'metadata' => ['order_number' => $order->order_number],
                    'payment_intent_data' => ['metadata' => ['order_number' => $order->order_number]],
                    'invoice_creation' => ['enabled' => 'true'],
                    'line_items' => [[
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => strtolower($order->currency),
                            'unit_amount' => $this->minorAmount($order->total_amount, $order->currency),
                            'product_data' => ['name' => $item->product_name],
                        ],
                    ]],
                ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('Stripe could not start the hosted checkout. Please try again.', previous: $exception);
        }

        $data = $response->json();
        if (! $response->successful() || blank(data_get($data, 'id')) || blank(data_get($data, 'url'))) {
            throw new RuntimeException('Stripe could not start the hosted checkout. Please try again.');
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function retrieveSession(string $sessionId): array
    {
        try {
            $response = $this->client()->get(
                $this->apiUrl('/v1/checkout/sessions/'.rawurlencode($sessionId)),
                ['expand' => ['invoice']],
            );
        } catch (Throwable $exception) {
            throw new RuntimeException('Stripe could not verify this payment.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Stripe could not verify this payment.');
        }

        return $response->json();
    }

    /** @return array<string, mixed> */
    public function retrieveInvoice(string $invoiceId): array
    {
        try {
            $response = $this->client()->get($this->apiUrl('/v1/invoices/'.rawurlencode($invoiceId)));
        } catch (Throwable $exception) {
            throw new RuntimeException('Stripe could not retrieve the invoice.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Stripe could not retrieve the invoice.');
        }

        return $response->json();
    }

    /** @return array<string, mixed> */
    public function verifiedWebhook(string $payload, string $signatureHeader): array
    {
        $secret = (string) config('commerce.payment.stripe.webhook_secret');
        if ($secret === '') {
            throw new RuntimeException('Stripe webhook verification is not configured.');
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        if ($timestamp <= 0 || abs(time() - $timestamp) > 300) {
            throw new RuntimeException('Stripe webhook timestamp is invalid.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        $valid = collect($parts['v1'] ?? [])->contains(fn (string $signature): bool => hash_equals($expected, $signature));
        if (! $valid) {
            throw new RuntimeException('Stripe webhook signature is invalid.');
        }

        $event = json_decode($payload, true);
        if (! is_array($event)) {
            throw new RuntimeException('Stripe webhook payload is invalid.');
        }

        return $event;
    }

    private function client(): PendingRequest
    {
        $secret = (string) config('commerce.payment.stripe.secret');
        if (! $this->configured()) {
            throw new RuntimeException('Stripe is not configured with a key matching PAYMENT_ENVIRONMENT.');
        }

        return Http::acceptJson()->withToken($secret)->timeout(20)->retry(2, 200);
    }

    private function apiUrl(string $path): string
    {
        return rtrim((string) config('commerce.payment.stripe.api_url'), '/').$path;
    }

    private function minorAmount(string|float $amount, string $currency): int
    {
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];
        $factor = in_array(strtoupper($currency), $zeroDecimalCurrencies, true) ? 1 : 100;

        return (int) round((float) $amount * $factor);
    }

    private function integrationIdentifier(Order $order): string
    {
        $hash = hash('sha256', $order->order_number);
        $suffix = '';

        for ($index = 0; $index < 8; $index++) {
            $suffix .= chr(97 + (hexdec($hash[$index]) % 26));
        }

        return 'ainchors_checkout_'.$suffix;
    }
}
