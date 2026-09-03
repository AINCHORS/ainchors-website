<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Commerce\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PayPalWaitingExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_waiting_page_keeps_ainchors_tab_and_controls_one_named_paypal_window(): void
    {
        $order = new Order(['order_number' => 'AIN-PAYPAL-WAITING-TEST']);
        $invoiceUrl = 'https://www.sandbox.paypal.com/invoice/p/#INV2-WAITING-TEST';

        $html = view('checkout.paypal-waiting', compact('order', 'invoiceUrl'))->render();

        $this->assertStringContainsString('data-paypal-window-name="ainchors-paypal-payment"', $html);
        $this->assertStringContainsString('Open PayPal Payment', $html);
        $this->assertStringContainsString('Cancel Payment', $html);
        $this->assertStringContainsString('window.open(invoiceUrl, providerWindowName', $html);
        $this->assertStringContainsString('The PayPal payment window was closed.', $html);
        $this->assertStringContainsString(route('payments.cancel', ['provider' => 'paypal', 'order' => $order]), $html);
        $this->assertStringNotContainsString('window.location.assign(invoiceUrl)', $html);
    }

    public function test_checkout_preopens_named_paypal_window_from_the_submit_gesture(): void
    {
        config(['commerce.payment.environment' => 'sandbox']);
        $this->be(User::factory()->make([
            'full_name' => 'PayPal Buyer',
            'email' => 'buyer@example.test',
        ]));
        $product = new Product([
            'type' => 'course',
            'sku' => 'PAYPAL-WINDOW-TEST',
            'name' => 'PayPal Window Test',
            'slug' => 'paypal-window-test',
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
            'metadata' => [],
        ]);

        $html = view('checkout.show', [
            'product' => $product,
            'paymentDriver' => 'hosted',
            'availableProviders' => ['stripe', 'paypal'],
            'token' => 'paypal-window-token',
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString("if (this.provider !== 'paypal') return;", $html);
        $this->assertStringContainsString("window.open(\n                          '',\n                          'ainchors-paypal-payment'", $html);
        $this->assertStringContainsString('Preparing secure PayPal payment…', $html);
    }

    public function test_paypal_cancel_cancels_the_provider_invoice_before_local_order_cancellation(): void
    {
        $this->enablePayPal();
        $invoiceId = 'INV2-CANCEL-SUCCESS';
        $providerStatus = 'UNPAID';

        Http::fake(function (ClientRequest $request) use ($invoiceId, &$providerStatus) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token'], 200);
            }

            if ($request->method() === 'GET' && str_ends_with($request->url(), '/'.$invoiceId)) {
                return Http::response(['id' => $invoiceId, 'status' => $providerStatus], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($request->url(), '/'.$invoiceId.'/cancel')) {
                $providerStatus = 'CANCELLED';

                return Http::response([], 204);
            }

            return Http::response([], 500);
        });

        [$user, $order] = $this->pendingPayPalOrder($invoiceId);

        $this->actingAs($user)
            ->get(route('payments.cancel', ['provider' => 'paypal', 'order' => $order]))
            ->assertRedirect(route('checkout.failed', $order))
            ->assertSessionHas('payment_failure_context', [
                'state' => 'cancelled',
                'provider' => 'paypal',
            ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'provider' => 'paypal', 'status' => 'failed']);
        $this->assertDatabaseHas('external_invoices', ['order_id' => $order->id, 'external_reference' => $invoiceId, 'status' => 'void']);
        Http::assertSent(fn (ClientRequest $request): bool =>
            $request->method() === 'POST'
            && str_ends_with($request->url(), '/'.$invoiceId.'/cancel')
            && data_get($request->data(), 'send_to_invoicer') === false
            && data_get($request->data(), 'send_to_recipient') === false
        );
    }

    public function test_paypal_cancel_failure_leaves_the_invoice_and_local_payment_pending(): void
    {
        $this->enablePayPal();
        $invoiceId = 'INV2-CANCEL-FAIL';

        Http::fake(function (ClientRequest $request) use ($invoiceId) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token'], 200);
            }

            if ($request->method() === 'GET' && str_ends_with($request->url(), '/'.$invoiceId)) {
                return Http::response(['id' => $invoiceId, 'status' => 'UNPAID'], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($request->url(), '/'.$invoiceId.'/cancel')) {
                return Http::response(['name' => 'CANNOT_CANCEL'], 422);
            }

            return Http::response([], 500);
        });

        [$user, $order] = $this->pendingPayPalOrder($invoiceId);

        $this->actingAs($user)
            ->get(route('payments.cancel', ['provider' => 'paypal', 'order' => $order]))
            ->assertRedirect(route('payments.paypal.waiting', $order))
            ->assertSessionHas('payment_cancel_error');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'awaiting_payment']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'provider' => 'paypal', 'status' => 'pending']);
        $this->assertDatabaseHas('external_invoices', ['order_id' => $order->id, 'external_reference' => $invoiceId, 'status' => 'unpaid']);
    }

    /** @return array{User, Order} */
    private function pendingPayPalOrder(string $invoiceId): array
    {
        $user = User::factory()->create();
        $product = Product::query()->create([
            'type' => 'course',
            'course_category' => 'self_training',
            'sku' => 'PAYPAL-CANCEL-'.substr(hash('sha256', $invoiceId), 0, 8),
            'name' => 'PayPal Cancel Test',
            'slug' => 'paypal-cancel-'.substr(hash('sha256', $invoiceId), 0, 8),
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
            'metadata' => [],
        ]);
        [$order] = app(OrderService::class)->createForProduct($user, $product, 'paypal-cancel-'.$invoiceId);
        $order->payments()->create([
            'provider' => 'paypal',
            'payment_environment' => 'test',
            'provider_transaction_id' => $invoiceId,
            'amount' => 19,
            'currency' => 'USD',
            'status' => 'pending',
            'provider_data' => [
                'environment' => 'sandbox',
                'paypal_invoice_id' => $invoiceId,
            ],
        ]);
        $order->externalInvoices()->create([
            'provider' => 'paypal',
            'external_reference' => $invoiceId,
            'invoice_number' => 'PP-CANCEL-TEST',
            'invoice_url' => 'https://www.sandbox.paypal.com/invoice/p/#'.$invoiceId,
            'status' => 'unpaid',
            'issued_at' => now(),
        ]);

        return [$user, $order->fresh(['items.product', 'payments', 'externalInvoices'])];
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
