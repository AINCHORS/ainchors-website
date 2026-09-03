<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalDirectTabFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    public function test_continue_with_paypal_submits_directly_into_one_regular_provider_tab_without_handoff_page(): void
    {
        $this->enablePayPal();
        [$user, $product] = $this->customerAndProduct();

        $html = (string) $this->actingAs($user)
            ->get(route('checkout.show', $product))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('formtarget="ainchors-paypal-payment"', $html);
        $this->assertStringContainsString('data-paypal-waiting-target-url=', $html);
        $this->assertStringContainsString('Continue with PayPal', $html);
        $this->assertStringNotContainsString('/payments/paypal/handoff', $html);
        $this->assertStringNotContainsString('window.open(', $html);
        $this->assertStringNotContainsString('postMessage(', $html);
        $this->assertStringNotContainsString('Preparing secure PayPal payment', $html);
        $this->assertStringNotContainsString('Connecting to PayPal', $html);
    }

    public function test_paypal_checkout_post_redirects_the_provider_tab_directly_to_the_genuine_invoice(): void
    {
        $this->enablePayPal();
        [$user, $product] = $this->customerAndProduct();
        $invoiceId = 'INV2-DIRECT-TAB';
        $invoice = $this->invoicePayload($invoiceId, 'DRAFT', 'AIN-PLACEHOLDER');

        Http::fake(function (ClientRequest $request) use (&$invoice, $invoiceId) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token', 'expires_in' => 3600], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($request->url(), '/v2/invoicing/invoices')) {
                $invoice['detail']['reference'] = (string) data_get($request->data(), 'detail.reference');

                return Http::response([
                    'rel' => 'self',
                    'href' => 'https://api-m.sandbox.paypal.com/v2/invoicing/invoices/'.$invoiceId,
                    'method' => 'GET',
                ], 201);
            }

            if ($request->method() === 'POST' && str_ends_with($request->url(), '/'.$invoiceId.'/send')) {
                $invoice['status'] = 'UNPAID';

                return Http::response([], 202);
            }

            if ($request->method() === 'GET' && str_ends_with($request->url(), '/'.$invoiceId)) {
                return Http::response($invoice, 200);
            }

            return Http::response([], 500);
        });

        $token = $this->checkoutToken($user, $product);
        $invoiceUrl = 'https://www.sandbox.paypal.com/invoice/p/#'.$invoiceId;

        $this->actingAs($user)
            ->post(route('checkout.store', $product), [
                'checkout_token' => $token,
                'payment_provider' => 'paypal',
            ])
            ->assertRedirect($invoiceUrl);

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertDatabaseHas('external_invoices', [
            'order_id' => $order->id,
            'provider' => 'paypal',
            'external_reference' => $invoiceId,
            'invoice_url' => $invoiceUrl,
            'status' => 'unpaid',
        ]);
    }

    public function test_original_checkout_tab_can_resolve_its_waiting_page_from_the_same_checkout_token(): void
    {
        $this->enablePayPal();
        [$user, $product] = $this->customerAndProduct();
        $invoiceId = 'INV2-WAITING-TARGET';
        $invoice = $this->invoicePayload($invoiceId, 'DRAFT', 'AIN-PLACEHOLDER');

        Http::fake(function (ClientRequest $request) use (&$invoice, $invoiceId) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token', 'expires_in' => 3600], 200);
            }
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/v2/invoicing/invoices')) {
                $invoice['detail']['reference'] = (string) data_get($request->data(), 'detail.reference');
                return Http::response([
                    'rel' => 'self',
                    'href' => 'https://api-m.sandbox.paypal.com/v2/invoicing/invoices/'.$invoiceId,
                    'method' => 'GET',
                ], 201);
            }
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/'.$invoiceId.'/send')) {
                $invoice['status'] = 'UNPAID';
                return Http::response([], 202);
            }
            if ($request->method() === 'GET' && str_ends_with($request->url(), '/'.$invoiceId)) {
                return Http::response($invoice, 200);
            }
            return Http::response([], 500);
        });

        $token = $this->checkoutToken($user, $product);
        $this->actingAs($user)->post(route('checkout.store', $product), [
            'checkout_token' => $token,
            'payment_provider' => 'paypal',
        ]);

        $order = Order::query()->latest('id')->firstOrFail();
        $targetUrl = url('/checkouts/'.$product->slug.'/paypal/waiting-target').'?checkout_token='.urlencode($token);

        $this->actingAs($user)
            ->getJson($targetUrl)
            ->assertOk()
            ->assertJson([
                'state' => 'ready',
                'redirect_url' => route('payments.paypal.waiting', $order),
            ]);
    }

    public function test_waiting_page_only_polls_payment_status_and_does_not_coordinate_tabs(): void
    {
        $order = new Order(['order_number' => 'AIN-PAYPAL-DIRECT-WAITING']);
        $invoiceUrl = 'https://www.sandbox.paypal.com/invoice/p/#INV2-DIRECT-WAITING';

        $html = view('checkout.paypal-waiting', compact('order', 'invoiceUrl'))->render();

        $this->assertStringContainsString('Complete payment with PayPal', $html);
        $this->assertStringContainsString('Reopen PayPal', $html);
        $this->assertStringContainsString('Cancel Payment', $html);
        $this->assertStringContainsString('fetch(statusUrl', $html);
        $this->assertStringNotContainsString('postMessage(', $html);
        $this->assertStringNotContainsString('ainchors-paypal-handoff', $html);
        $this->assertStringNotContainsString('window.open(', $html);
    }

    /** @return array{User, Product} */
    private function customerAndProduct(): array
    {
        $user = User::factory()->create([
            'full_name' => 'PayPal Direct Buyer',
            'email' => 'paypal-direct@example.test',
        ]);
        $product = Product::query()->create([
            'type' => 'service',
            'sku' => 'PAYPAL-DIRECT-TAB',
            'name' => 'PayPal Direct Tab',
            'slug' => 'paypal-direct-tab',
            'short_description' => 'Direct PayPal tab regression test.',
            'description' => 'Direct PayPal tab regression test.',
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
            'metadata' => [],
        ]);

        return [$user, $product];
    }

    private function checkoutToken(User $user, Product $product): string
    {
        $response = $this->actingAs($user)->get(route('checkout.show', $product))->assertOk();
        $html = (string) $response->getContent();
        preg_match('/name="checkout_token" value="([^"]+)"/', $html, $matches);

        return (string) ($matches[1] ?? '');
    }

    /** @return array<string, mixed> */
    private function invoicePayload(string $invoiceId, string $status, string $reference): array
    {
        return [
            'id' => $invoiceId,
            'status' => $status,
            'detail' => [
                'invoice_number' => 'PP-DIRECT-TAB',
                'reference' => $reference,
                'currency_code' => 'USD',
                'metadata' => [
                    'recipient_view_url' => 'https://www.sandbox.paypal.com/invoice/p/#'.$invoiceId,
                ],
            ],
            'amount' => ['currency_code' => 'USD', 'value' => '19.00'],
            'due_amount' => ['currency_code' => 'USD', 'value' => '19.00'],
        ];
    }

    private function enablePayPal(): void
    {
        config([
            'commerce.payment.driver' => 'hosted',
            'commerce.payment.environment' => 'sandbox',
            'commerce.payment.enabled_providers' => ['paypal'],
            'commerce.payment.paypal.client_id' => 'paypal-client-id',
            'commerce.payment.paypal.client_secret' => 'paypal-client-secret',
            'commerce.payment.paypal.webhook_id' => 'paypal-webhook-id',
            'commerce.payment.paypal.sandbox_url' => 'https://api-m.sandbox.paypal.com',
            'commerce.invoices.provider_hosts.paypal' => ['www.sandbox.paypal.com'],
        ]);
    }
}
