<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Commerce\Gateways\PayPalGateway;
use App\Services\Commerce\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayPalWaitingExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_waiting_page_keeps_ainchors_tab_and_hands_the_invoice_to_the_existing_paypal_tab(): void
    {
        $order = new Order(['order_number' => 'AIN-PAYPAL-WAITING-TEST']);
        $invoiceUrl = 'https://www.sandbox.paypal.com/invoice/p/#INV2-WAITING-TEST';

        $html = view('checkout.paypal-waiting', compact('order', 'invoiceUrl'))->render();

        $this->assertStringContainsString('target="ainchors-paypal-payment"', $html);
        $this->assertStringContainsString('Reopen PayPal', $html);
        $this->assertStringContainsString('Cancel Payment', $html);
        $this->assertStringContainsString("window.addEventListener('message'", $html);
        $this->assertStringContainsString('ainchors-paypal-handoff-ready', $html);
        $this->assertStringContainsString('ainchors-paypal-handoff-invoice', $html);
        $this->assertStringContainsString("window.addEventListener('pageshow'", $html);
        $this->assertStringContainsString(route('payments.cancel', ['provider' => 'paypal', 'order' => $order]), $html);
        $this->assertStringNotContainsString('window.open(', $html);
        $this->assertStringNotContainsString('window.location.assign(invoiceUrl)', $html);
    }

    public function test_checkout_opens_a_regular_paypal_handoff_tab_from_the_click_gesture(): void
    {
        $this->enablePayPal();
        $user = User::factory()->create([
            'full_name' => 'PayPal Buyer',
            'email' => 'buyer@example.test',
        ]);
        $product = Product::query()->create([
            'type' => 'service',
            'sku' => 'PAYPAL-WINDOW-TEST',
            'name' => 'PayPal Window Test',
            'slug' => 'paypal-window-test',
            'short_description' => 'PayPal window checkout test.',
            'description' => 'PayPal window checkout test.',
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
            'metadata' => [],
        ]);

        $response = $this->actingAs($user)
            ->get(route('checkout.show', $product))
            ->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString(route('payments.paypal.handoff'), $html);
        $this->assertStringContainsString('target="ainchors-paypal-payment"', $html);
        $this->assertStringContainsString('@click="prepareHostedPayment()"', $html);
        $this->assertStringContainsString('$refs.checkoutForm.requestSubmit($refs.checkoutSubmit)', $html);
        $this->assertStringNotContainsString('window.open(', $html);
        $this->assertStringNotContainsString('popup=yes', $html);
        $this->assertStringNotContainsString('Preparing secure PayPal payment…', $html);
    }

    public function test_paypal_status_actively_verifies_a_paid_invoice_when_the_webhook_is_delayed(): void
    {
        $this->enablePayPal();
        Mail::fake();
        $invoiceId = 'INV2-ACTIVE-VERIFY';
        [$user, $order] = $this->pendingPayPalOrder($invoiceId);

        Http::fake(function (ClientRequest $request) use ($invoiceId, $order) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token', 'expires_in' => 3600], 200);
            }

            if ($request->method() === 'GET' && str_ends_with($request->url(), '/'.$invoiceId)) {
                return Http::response($this->paidInvoicePayload($order, $invoiceId), 200);
            }

            return Http::response([], 500);
        });

        $this->actingAs($user)
            ->get(route('payments.paypal.status', $order))
            ->assertOk()
            ->assertJson([
                'state' => 'completed',
                'redirect_url' => route('checkout.success', $order),
            ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'paypal',
            'provider_transaction_id' => 'PAYPAL-ACTIVE-VERIFY',
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('external_invoices', [
            'order_id' => $order->id,
            'provider' => 'paypal',
            'external_reference' => $invoiceId,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'product_id' => $order->items->firstOrFail()->product_id,
            'status' => 'active',
        ]);
        Http::assertSent(fn (ClientRequest $request): bool =>
            $request->method() === 'GET' && str_ends_with($request->url(), '/'.$invoiceId)
        );
    }

    public function test_paypal_oauth_token_is_reused_during_repeated_provider_checks(): void
    {
        $this->enablePayPal();
        Cache::flush();
        $invoiceId = 'INV2-TOKEN-CACHE';

        Http::fake(function (ClientRequest $request) use ($invoiceId) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token', 'expires_in' => 3600], 200);
            }

            if ($request->method() === 'GET' && str_ends_with($request->url(), '/'.$invoiceId)) {
                return Http::response(['id' => $invoiceId, 'status' => 'UNPAID'], 200);
            }

            return Http::response([], 500);
        });

        $gateway = app(PayPalGateway::class);
        $gateway->retrieveInvoice($invoiceId);
        $gateway->retrieveInvoice($invoiceId);

        $oauthCalls = collect(Http::recorded())->filter(
            fn (array $record): bool => str_ends_with($record[0]->url(), '/v1/oauth2/token')
        )->count();

        $this->assertSame(1, $oauthCalls);
    }

    public function test_final_payment_pages_stay_put_and_distinguish_failed_from_cancelled(): void
    {
        $this->enablePayPal();
        [$user, $order] = $this->pendingPayPalOrder('INV2-FINAL-PAGES');
        $payment = $order->payments->firstOrFail();
        $invoice = $order->externalInvoices->firstOrFail();

        $order->update(['status' => 'completed', 'completed_at' => now()]);
        $payment->update([
            'provider_transaction_id' => 'PAYPAL-FINAL-PAGES',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $invoice->update(['status' => 'paid']);

        $success = $this->actingAs($user)->get(route('checkout.success', $order))->assertOk();
        $success->assertSee('Payment Successful');
        $success->assertDontSee('Redirecting in');
        $success->assertDontSee('data-success-redirect', false);
        $success->assertDontSee('window.location.assign', false);

        $order->update(['status' => 'cancelled', 'completed_at' => null]);
        $payment->update(['status' => 'failed', 'paid_at' => null]);
        $invoice->update(['status' => 'void']);

        $this->actingAs($user)
            ->withSession(['payment_failure_context' => ['state' => 'cancelled', 'provider' => 'paypal']])
            ->get(route('checkout.failed', $order))
            ->assertOk()
            ->assertSee('Payment Cancelled')
            ->assertDontSee('Payment Unsuccessful');

        $order->update(['status' => 'awaiting_payment']);

        $this->actingAs($user)
            ->get(route('payments.paypal.status', $order))
            ->assertOk()
            ->assertJson([
                'state' => 'failed',
                'redirect_url' => route('checkout.failed', $order),
            ]);

        $this->actingAs($user)
            ->withSession(['payment_failure_context' => ['state' => 'failed', 'provider' => 'paypal']])
            ->get(route('checkout.failed', $order))
            ->assertOk()
            ->assertSee('Payment Failed')
            ->assertDontSee('Payment Unsuccessful');
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

    /** @return array<string, mixed> */
    private function paidInvoicePayload(Order $order, string $invoiceId): array
    {
        return [
            'id' => $invoiceId,
            'status' => 'PAID',
            'detail' => [
                'invoice_number' => 'PP-ACTIVE-VERIFY',
                'reference' => $order->order_number,
                'currency_code' => 'USD',
                'metadata' => [
                    'recipient_view_url' => 'https://www.sandbox.paypal.com/invoice/p/#'.$invoiceId,
                ],
            ],
            'amount' => ['value' => '19.00', 'currency_code' => 'USD'],
            'due_amount' => ['value' => '0.00', 'currency_code' => 'USD'],
            'payments' => [
                'paid_amount' => ['value' => '19.00', 'currency_code' => 'USD'],
                'transactions' => [[
                    'type' => 'PAYPAL',
                    'payment_id' => 'PAYPAL-ACTIVE-VERIFY',
                    'method' => 'PAYPAL',
                    'transaction_status' => 'SUCCESS',
                    'amount' => ['value' => '19.00', 'currency_code' => 'USD'],
                ]],
            ],
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
