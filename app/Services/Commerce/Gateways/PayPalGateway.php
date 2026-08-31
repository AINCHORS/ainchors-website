<?php

namespace App\Services\Commerce\Gateways;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class PayPalGateway
{
    public function configured(): bool
    {
        return filled(config('commerce.payment.paypal.client_id'))
            && filled(config('commerce.payment.paypal.client_secret'))
            && filled(config('commerce.payment.paypal.webhook_id'));
    }

    /** @return array<string, mixed> */
    public function createOrder(Order $order): array
    {
        $item = $order->items->firstOrFail();
        try {
            $response = $this->client()
                ->withHeaders([
                    'PayPal-Request-Id' => $this->requestId($order->idempotency_key.'-create'),
                    'Prefer' => 'return=representation',
                ])
                ->post($this->apiUrl('/v2/checkout/orders'), [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => $order->order_number,
                        'custom_id' => $order->order_number,
                        'invoice_id' => $order->order_number,
                        'description' => $item->product_name,
                        'amount' => [
                            'currency_code' => strtoupper($order->currency),
                            'value' => $this->formattedAmount($order->total_amount, $order->currency),
                        ],
                    ]],
                    'application_context' => [
                        'brand_name' => 'AINCHORS',
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'PAY_NOW',
                        'return_url' => route('payments.paypal.return', $order),
                        'cancel_url' => route('payments.cancel', ['order' => $order, 'provider' => 'paypal']),
                    ],
                ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('PayPal could not start the hosted checkout. Please try again.', previous: $exception);
        }

        $data = $response->json();
        $approval = collect(data_get($data, 'links', []))->firstWhere('rel', 'approve');
        $approvalUrl = is_array($approval) ? ($approval['href'] ?? null) : null;
        if (! $response->successful() || blank(data_get($data, 'id')) || blank($approvalUrl)) {
            throw new RuntimeException('PayPal could not start the hosted checkout. Please try again.');
        }

        $data['approval_url'] = $approvalUrl;

        return $data;
    }

    /** @return array<string, mixed> */
    public function captureOrder(string $payPalOrderId, string $requestId): array
    {
        try {
            $response = $this->client()
                ->withHeaders([
                    'PayPal-Request-Id' => $this->requestId($requestId),
                    'Prefer' => 'return=representation',
                ])
                ->post($this->apiUrl('/v2/checkout/orders/'.rawurlencode($payPalOrderId).'/capture'), []);
        } catch (Throwable $exception) {
            throw new RuntimeException('PayPal could not verify and capture this payment.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('PayPal could not verify and capture this payment.');
        }

        return $response->json();
    }

    /** @param array<string, string|null> $headers */
    public function webhookIsVerified(array $headers, array $event): bool
    {
        $webhookId = (string) config('commerce.payment.paypal.webhook_id');
        if ($webhookId === '') {
            throw new RuntimeException('PayPal webhook verification is not configured.');
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

        return $response->successful() && $response->json('verification_status') === 'SUCCESS';
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->withToken($this->accessToken())->timeout(20)->retry(2, 200);
    }

    private function accessToken(): string
    {
        $clientId = (string) config('commerce.payment.paypal.client_id');
        $secret = (string) config('commerce.payment.paypal.client_secret');
        if ($clientId === '' || $secret === '') {
            throw new RuntimeException('PayPal is not configured.');
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $secret)
                ->timeout(20)
                ->post($this->apiUrl('/v1/oauth2/token'), ['grant_type' => 'client_credentials']);
        } catch (Throwable $exception) {
            throw new RuntimeException('PayPal authentication failed.', previous: $exception);
        }

        $token = (string) $response->json('access_token');
        if (! $response->successful() || $token === '') {
            throw new RuntimeException('PayPal authentication failed.');
        }

        return $token;
    }

    private function apiUrl(string $path): string
    {
        $environment = config('commerce.payment.environment') === 'live' ? 'live_url' : 'sandbox_url';

        return rtrim((string) config('commerce.payment.paypal.'.$environment), '/').$path;
    }

    private function formattedAmount(string|float $amount, string $currency): string
    {
        return number_format((float) $amount, strtoupper($currency) === 'JPY' ? 0 : 2, '.', '');
    }

    private function requestId(string $value): string
    {
        return substr(hash('sha256', $value), 0, 32);
    }
}
