<?php

namespace App\Services\Commerce\Gateways;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class PayPalGateway
{
    public function configured(): bool
    {
        $environment = (string) config('commerce.payment.environment');

        return in_array($environment, ['sandbox', 'live'], true)
            && filled(config('commerce.payment.paypal.client_id'))
            && filled(config('commerce.payment.paypal.client_secret'))
            && filled(config('commerce.payment.paypal.webhook_id'));
    }

    /** @return array{invoice_id: string} */
    public function createInvoice(Order $order): array
    {
        $order->loadMissing(['user', 'items']);
        $item = $order->items->firstOrFail();

        try {
            $response = $this->client()
                ->withHeaders([
                    'PayPal-Request-Id' => $this->requestId($order->idempotency_key.'-invoice-create'),
                    'Prefer' => 'return=representation',
                ])
                ->post($this->apiUrl('/v2/invoicing/invoices'), [
                    'detail' => [
                        'invoice_number' => $this->invoiceNumber($order->order_number),
                        'reference' => $order->order_number,
                        'currency_code' => strtoupper($order->currency),
                        'note' => 'AINCHORS order '.$order->order_number,
                    ],
                    'primary_recipients' => [[
                        'billing_info' => [
                            'email_address' => (string) $order->user->email,
                            'name' => ['full_name' => (string) ($order->user->full_name ?: $order->user->email)],
                        ],
                    ]],
                    'items' => [[
                        'name' => (string) $item->product_name,
                        'quantity' => (string) $item->quantity,
                        'unit_amount' => [
                            'currency_code' => strtoupper($order->currency),
                            'value' => $this->formattedAmount((string) $item->unit_price, $order->currency),
                        ],
                    ]],
                ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('PayPal could not create the provider invoice. Please try again.', previous: $exception);
        }

        $payload = $response->json();
        $links = is_array($payload) && ($payload['rel'] ?? null) === 'self'
            ? [$payload]
            : (array) data_get($payload, 'links', []);
        $self = collect($links)->first(
            fn ($link): bool => is_array($link) && ($link['rel'] ?? null) === 'self' && is_string($link['href'] ?? null),
        );
        $invoiceId = basename((string) ($self['href'] ?? ''));
        if (! $response->successful() || ! str_starts_with($invoiceId, 'INV2-')) {
            throw new RuntimeException('PayPal did not return a valid provider invoice reference.');
        }

        return ['invoice_id' => $invoiceId];
    }

    public function sendInvoice(string $invoiceId, string $requestId): void
    {
        $this->assertInvoiceId($invoiceId);
        try {
            $response = $this->client()
                ->withHeaders(['PayPal-Request-Id' => $this->requestId($requestId.'-invoice-send')])
                ->post($this->apiUrl('/v2/invoicing/invoices/'.rawurlencode($invoiceId).'/send'), [
                    'send_to_invoicer' => false,
                    'send_to_recipient' => false,
                ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('PayPal could not activate the provider invoice. Please try again.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('PayPal could not activate the provider invoice. Please try again.');
        }
    }

    /** @return array<string, mixed> */
    public function retrieveInvoice(string $invoiceId): array
    {
        $this->assertInvoiceId($invoiceId);
        try {
            $response = $this->client()->get($this->apiUrl('/v2/invoicing/invoices/'.rawurlencode($invoiceId)));
        } catch (Throwable $exception) {
            throw new RuntimeException('PayPal could not verify the provider invoice.', previous: $exception);
        }

        $payload = $response->json();
        if (! $response->successful() || ! is_array($payload)) {
            throw new RuntimeException('PayPal could not verify the provider invoice.');
        }

        return $payload;
    }

    public function cancelInvoice(string $invoiceId): void
    {
        $invoice = $this->retrieveInvoice($invoiceId);
        $status = strtoupper((string) ($invoice['status'] ?? ''));

        if (in_array($status, ['CANCELLED', 'AUTO_CANCELLED'], true)) {
            return;
        }

        if (! in_array($status, ['UNPAID', 'SENT'], true)) {
            throw new RuntimeException('This PayPal invoice can no longer be cancelled.');
        }

        try {
            $response = $this->client()->post(
                $this->apiUrl('/v2/invoicing/invoices/'.rawurlencode($invoiceId).'/cancel'),
                [
                    'send_to_invoicer' => false,
                    'send_to_recipient' => false,
                ],
            );
        } catch (Throwable $exception) {
            throw new RuntimeException('PayPal could not cancel the provider invoice. Please try again.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('PayPal could not cancel the provider invoice. Please try again.');
        }

        $confirmed = $this->retrieveInvoice($invoiceId);
        if (! in_array(strtoupper((string) ($confirmed['status'] ?? '')), ['CANCELLED', 'AUTO_CANCELLED'], true)) {
            throw new RuntimeException('PayPal did not confirm that the provider invoice was cancelled.');
        }
    }

    /** @param array<string, string|null> $headers */
    public function webhookIsVerified(array $headers, array|\stdClass $event): bool
    {
        $webhookId = (string) config('commerce.payment.paypal.webhook_id');
        $required = ['paypal-auth-algo', 'paypal-cert-url', 'paypal-transmission-id', 'paypal-transmission-sig', 'paypal-transmission-time'];
        if ($webhookId === '' || collect($required)->contains(fn (string $key): bool => blank($headers[$key] ?? null))) {
            throw new RuntimeException('PayPal webhook verification is not configured or signed.');
        }

        try {
            $response = $this->client()->post($this->apiUrl('/v1/notifications/verify-webhook-signature'), [
                'auth_algo' => $headers['paypal-auth-algo'] ?? null,
                'cert_url' => $headers['paypal-cert-url'] ?? null,
                'transmission_id' => $headers['paypal-transmission-id'] ?? null,
                'transmission_sig' => $headers['paypal-transmission-sig'] ?? null,
                'transmission_time' => $headers['paypal-transmission-time'] ?? null,
                'webhook_id' => $webhookId,
                'webhook_event' => $event,
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('PayPal webhook verification failed.', previous: $exception);
        }

        $verificationStatus = (string) $response->json('verification_status');
        if (! $response->successful() || $verificationStatus !== 'SUCCESS') {
            throw new RuntimeException(sprintf(
                'PayPal rejected webhook verification (HTTP %d, status %s).',
                $response->status(),
                $verificationStatus !== '' ? $verificationStatus : 'missing',
            ));
        }

        return true;
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->withToken($this->accessToken())->connectTimeout(5)->timeout(15)->retry(2, 200);
    }

    private function accessToken(): string
    {
        $clientId = (string) config('commerce.payment.paypal.client_id');
        $secret = (string) config('commerce.payment.paypal.client_secret');
        if ($clientId === '' || $secret === '') {
            throw new RuntimeException('PayPal is not configured.');
        }

        $cacheKey = $this->accessTokenCacheKey($clientId);
        $cachedToken = Cache::get($cacheKey);
        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $secret)
                ->connectTimeout(5)
                ->timeout(15)
                ->post($this->apiUrl('/v1/oauth2/token'), ['grant_type' => 'client_credentials']);
        } catch (Throwable $exception) {
            throw new RuntimeException('PayPal authentication failed.', previous: $exception);
        }

        $token = (string) $response->json('access_token');
        if (! $response->successful() || $token === '') {
            throw new RuntimeException('PayPal authentication failed.');
        }

        $expiresIn = max(60, (int) ($response->json('expires_in') ?? 300));
        Cache::put($cacheKey, $token, now()->addSeconds(max(30, $expiresIn - 60)));

        return $token;
    }

    private function accessTokenCacheKey(string $clientId): string
    {
        return 'commerce:paypal:oauth:'.hash(
            'sha256',
            (string) config('commerce.payment.environment').'|'.$clientId,
        );
    }

    private function apiUrl(string $path): string
    {
        $environment = (string) config('commerce.payment.environment');
        if (! in_array($environment, ['sandbox', 'live'], true)) {
            throw new RuntimeException('PAYMENT_ENVIRONMENT must be either sandbox or live.');
        }

        $baseUrl = $environment === 'live' ? 'live_url' : 'sandbox_url';

        return rtrim((string) config('commerce.payment.paypal.'.$baseUrl), '/').$path;
    }

    private function formattedAmount(string|float $amount, string $currency): string
    {
        $value = (string) $amount;
        $scale = strtoupper($currency) === 'JPY' ? 0 : 2;
        if (! preg_match('/^\d+(?:\.\d+)?$/', $value)) {
            throw new RuntimeException('The order amount is invalid.');
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        if (strlen($fraction) > $scale && trim(substr($fraction, $scale), '0') !== '') {
            throw new RuntimeException('The order amount has unsupported precision.');
        }
        $whole = ltrim($whole, '0') ?: '0';

        return $scale === 0 ? $whole : $whole.'.'.str_pad(substr($fraction, 0, $scale), $scale, '0');
    }

    private function assertInvoiceId(string $invoiceId): void
    {
        if (! preg_match('/^INV2-[A-Z0-9-]+$/i', $invoiceId)) {
            throw new RuntimeException('The PayPal invoice reference is invalid.');
        }
    }

    private function requestId(string $value): string
    {
        return substr(hash('sha256', $value), 0, 32);
    }

    private function invoiceNumber(string $orderNumber): string
    {
        if (strlen($orderNumber) <= 25) {
            return $orderNumber;
        }

        return substr($orderNumber, 0, 18).'-'.substr(hash('sha256', $orderNumber), 0, 6);
    }
}
