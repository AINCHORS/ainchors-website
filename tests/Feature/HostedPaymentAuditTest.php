<?php

namespace Tests\Feature;

use App\Models\ExternalInvoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Commerce\ExternalInvoiceService;
use App\Services\Commerce\Gateways\StripeGateway;
use App\Services\Commerce\Gateways\PayPalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HostedPaymentAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        $this->artisan('ainchors:populate-legacy-course-catalogue')->assertExitCode(0);
        $this->artisan('ainchors:populate-course-learning-content')->assertExitCode(0);

        Product::query()->where('type', 'course')->with('courseContent')->get()
            ->each(function (Product $course): void {
                if (filled($course->courseContent?->video_url)) {
                    Storage::disk('local')->put($course->courseContent->video_url, 'payment-audit-video');
                }
            });
    }

    public function test_stripe_package_and_consulting_use_the_same_verified_fulfilment_path(): void
    {
        $this->enableStripe();
        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'POST') {
                $order = Order::query()->latest('id')->firstOrFail();

                return Http::response([
                    'id' => 'cs_test_audit_'.$order->id,
                    'url' => 'https://checkout.stripe.test/audit/'.$order->id,
                ]);
            }

            $sessionId = basename(parse_url($request->url(), PHP_URL_PATH));
            $payment = Payment::query()->where('provider_transaction_id', $sessionId)->firstOrFail();
            $order = $payment->order;

            return Http::response([
                'id' => $sessionId,
                'client_reference_id' => $order->order_number,
                'metadata' => ['order_number' => $order->order_number],
                'payment_status' => 'paid',
                'livemode' => false,
                'amount_total' => (int) round((float) $order->total_amount * 100),
                'currency' => strtolower($order->currency),
            ]);
        });

        $consulting = $this->oneTimeProduct('consulting', 'AUDIT-CONSULTING', 'Payment Audit Consulting', 75);
        foreach ([Product::query()->where('sku', 'SL-PACKAGE-ALL-10')->firstOrFail(), $consulting] as $product) {
            $user = User::factory()->create();
            $this->startAndReturnStripe($user, $product);

            $expectedEnrollments = $product->isPackage() ? $product->bundleProducts()->count() : 0;
            $this->assertSame($expectedEnrollments, $user->enrollments()->count());
        }

        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame(2, Payment::query()->where('provider', 'stripe')->where('status', 'paid')->count());
        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'POST'
            && $request->hasHeader('Stripe-Version', '2026-07-29.dahlia')
            && preg_match('/^ainchors_checkout_[a-z]{8}$/', (string) data_get($request->data(), 'integration_identifier')) === 1
            && data_get($request->data(), 'invoice_creation.enabled') === 'true'
            && filled(data_get($request->data(), 'client_reference_id'))
            && data_get($request->data(), 'client_reference_id') === data_get($request->data(), 'metadata.order_number'));
    }

    public function test_paypal_course_service_and_consulting_complete_only_after_server_capture(): void
    {
        $this->enablePayPal();
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-audit-token']);
            }

            if (str_ends_with($request->url(), '/v2/checkout/orders')) {
                $order = Order::query()->latest('id')->firstOrFail();
                $providerOrder = 'PAYPAL-AUDIT-'.$order->id;

                return Http::response([
                    'id' => $providerOrder,
                    'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/audit/'.$providerOrder]],
                ], 201);
            }

            $providerOrder = basename(dirname(parse_url($request->url(), PHP_URL_PATH)));
            $payment = Payment::query()->get()->first(fn (Payment $candidate): bool => data_get($candidate->provider_data, 'paypal_order_id') === $providerOrder);
            $order = $payment->order;

            return Http::response([
                'id' => $providerOrder,
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'custom_id' => $order->order_number,
                    'payments' => ['captures' => [[
                        'id' => 'CAPTURE-AUDIT-'.$order->id,
                        'status' => 'COMPLETED',
                        'amount' => ['value' => number_format((float) $order->total_amount, 2, '.', ''), 'currency_code' => $order->currency],
                        'supplementary_data' => ['related_ids' => ['order_id' => $providerOrder]],
                    ]]],
                ]],
            ], 201);
        });

        $course = Product::query()->where('sku', 'SL-AI-001')->firstOrFail();
        $service = $this->oneTimeProduct('service', 'AUDIT-SERVICE', 'Payment Audit Service', 49);
        $consulting = $this->oneTimeProduct('consulting', 'AUDIT-CONSULTING-PP', 'PayPal Audit Consulting', 89);

        foreach ([$course, $service, $consulting] as $product) {
            $user = User::factory()->create();
            $this->startAndReturnPayPal($user, $product);
            $this->assertSame($product->isCourse() ? 1 : 0, $user->enrollments()->count());
        }

        $this->assertSame(3, Payment::query()->where('provider', 'paypal')->where('status', 'paid')->count());
        $this->assertDatabaseCount('external_invoices', 0);
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/v2/checkout/orders')
            && $request->method() === 'POST'
            && data_get($request->data(), 'purchase_units.0.invoice_id') === null
            && filled(data_get($request->data(), 'purchase_units.0.reference_id'))
            && filled(data_get($request->data(), 'purchase_units.0.custom_id')));
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/capture')
            && $request->method() === 'POST'
            && $request->body() === '{}');
    }

    public function test_environment_configuration_and_provider_evidence_must_agree(): void
    {
        config([
            'commerce.payment.environment' => 'sandbox',
            'commerce.payment.stripe.secret' => implode('_', ['sk', 'test', 'audit', 'fixture']),
        ]);
        $this->assertTrue(app(StripeGateway::class)->configured());

        config(['commerce.payment.stripe.secret' => implode('_', ['sk', 'live', 'audit', 'fixture'])]);
        $this->assertFalse(app(StripeGateway::class)->configured());

        config(['commerce.payment.environment' => 'live']);
        $this->assertTrue(app(StripeGateway::class)->configured());

        config(['commerce.payment.stripe.secret' => implode('_', ['sk', 'test', 'audit', 'fixture'])]);
        $this->assertFalse(app(StripeGateway::class)->configured());

        config([
            'commerce.payment.environment' => 'production',
            'commerce.payment.stripe.secret' => implode('_', ['sk', 'live', 'audit', 'fixture']),
            'commerce.payment.paypal.client_id' => 'paypal-invalid-env-client',
            'commerce.payment.paypal.client_secret' => 'paypal-invalid-env-secret',
            'commerce.payment.paypal.webhook_id' => 'paypal-invalid-env-webhook',
        ]);
        $this->assertFalse(app(StripeGateway::class)->configured());
        $this->assertFalse(app(PayPalGateway::class)->configured());

        $this->enableStripe();
        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'POST') {
                return Http::response(['id' => 'cs_test_wrong_mode', 'url' => 'https://checkout.stripe.test/wrong-mode']);
            }

            $order = Order::query()->firstOrFail();

            return Http::response([
                'id' => 'cs_test_wrong_mode',
                'client_reference_id' => $order->order_number,
                'metadata' => ['order_number' => $order->order_number],
                'payment_status' => 'paid',
                'livemode' => true,
                'amount_total' => 1900,
                'currency' => 'usd',
            ]);
        });

        $user = User::factory()->create();
        $course = Product::query()->where('sku', 'SL-AI-001')->firstOrFail();
        $token = $this->checkoutToken($user, $course);
        $this->actingAs($user)->post(route('checkout.store', $course), [
            'checkout_token' => $token,
            'payment_provider' => 'stripe',
        ]);
        $order = Order::query()->firstOrFail();
        $this->actingAs($user)->get(route('payments.stripe.return', $order).'?session_id=cs_test_wrong_mode')
            ->assertRedirect(route('checkout.failed', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'awaiting_payment']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'pending', 'payment_environment' => 'test']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_admin_and_user_reference_one_trusted_invoice_record(): void
    {
        config(['commerce.invoices.provider_hosts.stripe' => ['invoice.stripe.com']]);
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $product = $this->oneTimeProduct('service', 'AUDIT-INVOICE-SERVICE', 'Invoice Audit Service', 99);
        [$order] = app(\App\Services\Commerce\OrderService::class)->createForProduct($user, $product, 'audit-invoice-order');
        $order->update(['status' => 'completed']);
        $payment = $order->payments()->create([
            'provider' => 'stripe',
            'payment_environment' => 'test',
            'provider_transaction_id' => 'cs_test_admin_invoice',
            'amount' => 99,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
            'provider_data' => ['environment' => 'sandbox'],
        ]);
        $invoice = app(ExternalInvoiceService::class)->record(
            $order,
            'stripe',
            'in_audit_admin_user',
            'https://invoice.stripe.com/i/audit-admin-user',
            'AUDIT-1001',
        );

        $this->actingAs($user)->get(route('purchase-history'))->assertOk()
            ->assertSee(route('purchase-history.invoice', $invoice), false);
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()
            ->assertSee('AUDIT-1001')->assertSee(route('admin.invoices.show', $invoice), false);
        $this->actingAs($admin)->get(route('admin.payments.show', $payment))->assertOk()
            ->assertSee('AUDIT-1001')->assertSee('Test')->assertSee(route('admin.invoices.show', $invoice), false);

        $paypalAttempt = $order->payments()->create([
            'provider' => 'paypal',
            'payment_environment' => 'test',
            'provider_transaction_id' => 'PAYPAL-NO-INVOICE-AUDIT',
            'amount' => 99,
            'currency' => 'USD',
            'status' => 'failed',
            'failure_reason' => 'Audit fixture',
            'provider_data' => ['environment' => 'sandbox'],
        ]);
        $this->actingAs($admin)->get(route('admin.payments.show', $paypalAttempt))->assertOk()
            ->assertDontSee('AUDIT-1001')
            ->assertSee('No provider-hosted invoice or receipt is available.');

        $this->actingAs($admin)->get(route('admin.invoices.show', $invoice))
            ->assertRedirect('https://invoice.stripe.com/i/audit-admin-user');
        $this->assertSame(1, ExternalInvoice::query()->count());
    }

    public function test_provider_invoice_reference_cannot_be_reassigned_between_orders(): void
    {
        config(['commerce.invoices.provider_hosts.stripe' => ['invoice.stripe.com']]);

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $firstProduct = $this->oneTimeProduct('service', 'INVOICE-OWNER-A', 'Invoice Owner A', 20);
        $secondProduct = $this->oneTimeProduct('service', 'INVOICE-OWNER-B', 'Invoice Owner B', 20);

        [$firstOrder] = app(\App\Services\Commerce\OrderService::class)
            ->createForProduct($firstUser, $firstProduct, 'invoice-owner-a');
        [$secondOrder] = app(\App\Services\Commerce\OrderService::class)
            ->createForProduct($secondUser, $secondProduct, 'invoice-owner-b');
        $firstOrder->update(['status' => 'completed']);
        $secondOrder->update(['status' => 'completed']);

        $service = app(ExternalInvoiceService::class);
        $service->record(
            $firstOrder,
            'stripe',
            'in_shared_provider_reference',
            'https://invoice.stripe.com/i/first-order',
        );

        $this->expectException(\InvalidArgumentException::class);
        $service->record(
            $secondOrder,
            'stripe',
            'in_shared_provider_reference',
            'https://invoice.stripe.com/i/second-order',
        );
    }

    public function test_paypal_rejects_wrong_order_capture_amount_currency_status_and_capture_id(): void
    {
        $this->enablePayPal();
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-security-token']);
            }

            if (str_ends_with($request->url(), '/v2/checkout/orders')) {
                $order = Order::query()->latest('id')->firstOrFail();
                $providerOrder = 'PAYPAL-SECURITY-'.$order->id;

                return Http::response([
                    'id' => $providerOrder,
                    'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/security/'.$providerOrder]],
                ], 201);
            }

            $providerOrder = basename(dirname(parse_url($request->url(), PHP_URL_PATH)));
            $payment = Payment::query()->get()->first(fn (Payment $candidate): bool => data_get($candidate->provider_data, 'paypal_order_id') === $providerOrder);
            $order = $payment->order;
            $scenario = (string) data_get($order->items()->firstOrFail()->metadata, 'sku');
            $captureId = $scenario === 'SECURITY-BLANK-CAPTURE' ? '' : 'CAPTURE-'.$order->id;
            $relatedOrder = $scenario === 'SECURITY-WRONG-ORDER' ? 'PAYPAL-OTHER-ORDER' : $providerOrder;
            $amount = $scenario === 'SECURITY-WRONG-AMOUNT' ? '1.00' : number_format((float) $order->total_amount, 2, '.', '');
            $currency = $scenario === 'SECURITY-WRONG-CURRENCY' ? 'AUD' : $order->currency;
            $status = $scenario === 'SECURITY-WRONG-STATUS' ? 'APPROVED' : 'COMPLETED';
            $captureStatus = $scenario === 'SECURITY-WRONG-CAPTURE-STATUS' ? 'PENDING' : 'COMPLETED';

            return Http::response([
                'id' => $providerOrder,
                'status' => $status,
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'custom_id' => $order->order_number,
                    'payments' => ['captures' => [[
                        'id' => $captureId,
                        'status' => $captureStatus,
                        'amount' => ['value' => $amount, 'currency_code' => $currency],
                        'supplementary_data' => ['related_ids' => ['order_id' => $relatedOrder]],
                    ]]],
                ]],
            ], 201);
        });

        foreach (['WRONG-AMOUNT', 'WRONG-CURRENCY', 'WRONG-ORDER', 'WRONG-STATUS', 'WRONG-CAPTURE-STATUS', 'BLANK-CAPTURE'] as $scenario) {
            $product = $this->oneTimeProduct('service', 'SECURITY-'.$scenario, 'Security '.$scenario, 25);
            $user = User::factory()->create();
            $token = $this->checkoutToken($user, $product);
            $this->actingAs($user)->post(route('checkout.store', $product), [
                'checkout_token' => $token,
                'payment_provider' => 'paypal',
            ]);
            $order = $user->orders()->firstOrFail();
            $providerOrder = (string) data_get($order->payments()->firstOrFail()->provider_data, 'paypal_order_id');

            $this->actingAs($user)->get(route('payments.paypal.return', $order).'?token='.$providerOrder)
                ->assertRedirect(route('checkout.failed', $order));
            $this->assertSame('awaiting_payment', $order->fresh()->status);
            $this->assertSame('pending', $order->payments()->firstOrFail()->status);
        }

        $this->assertDatabaseCount('enrollments', 0);
        $this->assertSame(0, Payment::query()->where('status', 'paid')->count());
    }

    public function test_invalid_stripe_signature_and_failed_paypal_verification_are_rejected(): void
    {
        $this->enableStripe();
        $this->postJson(route('payments.stripe.webhook'), ['type' => 'checkout.session.completed'], [
            'Stripe-Signature' => 't='.time().',v1=invalid',
        ])->assertBadRequest()->assertJson(['received' => false]);

        $this->enablePayPal();
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-verification-token']);
            }

            return Http::response(['verification_status' => 'FAILURE']);
        });

        $this->withHeaders([
            'PayPal-Auth-Algo' => 'SHA256withRSA',
            'PayPal-Cert-Url' => 'https://api-m.sandbox.paypal.com/cert.pem',
            'PayPal-Transmission-Id' => 'audit-transmission',
            'PayPal-Transmission-Sig' => 'audit-signature',
            'PayPal-Transmission-Time' => now()->toIso8601String(),
        ])->postJson(route('payments.paypal.webhook'), [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [],
        ])->assertBadRequest()->assertJson(['received' => false]);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_invalid_payment_configuration_and_live_demo_checkout_fail_closed(): void
    {
        $user = User::factory()->create();
        $course = Product::query()->where('sku', 'SL-AI-001')->firstOrFail();

        config([
            'commerce.payment.driver' => 'hostd',
            'commerce.payment.environment' => 'sandbox',
        ]);
        $this->actingAs($user)->get(route('checkout.show', $course))->assertStatus(503);

        config([
            'commerce.payment.driver' => 'demo',
            'commerce.payment.environment' => 'live',
        ]);
        $this->actingAs($user)->get(route('checkout.show', $course))->assertStatus(503);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_demo_service_cannot_complete_an_order_outside_sandbox(): void
    {
        config([
            'commerce.payment.driver' => 'demo',
            'commerce.payment.environment' => 'live',
        ]);

        $user = User::factory()->create();
        $course = Product::query()->where('sku', 'SL-AI-001')->firstOrFail();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Demo checkout is not enabled');
        app(\App\Services\Commerce\CheckoutService::class)
            ->purchase($user, $course, 'live-demo-must-fail');
    }

    public function test_live_ready_configuration_uses_live_labels_and_paypal_api_without_a_live_charge(): void
    {
        config([
            'commerce.payment.driver' => 'hosted',
            'commerce.payment.environment' => 'live',
            'commerce.payment.enabled_providers' => ['stripe', 'paypal'],
            'commerce.payment.stripe.secret' => implode('_', ['sk', 'live', 'audit', 'fixture']),
            'commerce.payment.paypal.client_id' => 'paypal-live-client-fixture',
            'commerce.payment.paypal.client_secret' => 'paypal-live-secret-fixture',
            'commerce.payment.paypal.webhook_id' => 'paypal-live-webhook-fixture',
        ]);

        $user = User::factory()->create();
        $course = Product::query()->where('sku', 'SL-AI-001')->firstOrFail();
        $this->actingAs($user)->get(route('checkout.show', $course))
            ->assertOk()
            ->assertSee('Live')
            ->assertDontSee('SANDBOX PAYMENT')
            ->assertDontSee('Test mode');

        $service = $this->oneTimeProduct('service', 'LIVE-URL-AUDIT', 'Live URL Audit', 15);
        [$order] = app(\App\Services\Commerce\OrderService::class)->createForProduct($user, $service, 'live-url-audit');
        $order->load(['user', 'items']);
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-live-token-fixture']);
            }

            return Http::response([
                'id' => 'PAYPAL-LIVE-URL-AUDIT',
                'links' => [['rel' => 'approve', 'href' => 'https://www.paypal.com/live-url-audit']],
            ], 201);
        });

        app(PayPalGateway::class)->createOrder($order);
        Http::assertSent(fn (ClientRequest $request): bool => str_starts_with($request->url(), 'https://api-m.paypal.com/'));
    }

    private function enableStripe(): void
    {
        config([
            'commerce.payment.driver' => 'hosted',
            'commerce.payment.environment' => 'sandbox',
            'commerce.payment.enabled_providers' => ['stripe'],
            'commerce.payment.stripe.secret' => implode('_', ['sk', 'test', 'audit', 'fixture']),
            'commerce.payment.stripe.webhook_secret' => implode('_', ['whsec', 'audit', 'fixture']),
        ]);
    }

    private function enablePayPal(): void
    {
        config([
            'commerce.payment.driver' => 'hosted',
            'commerce.payment.environment' => 'sandbox',
            'commerce.payment.enabled_providers' => ['paypal'],
            'commerce.payment.paypal.client_id' => 'paypal-audit-client',
            'commerce.payment.paypal.client_secret' => 'paypal-audit-secret',
            'commerce.payment.paypal.webhook_id' => 'paypal-audit-webhook',
        ]);
    }

    private function startAndReturnStripe(User $user, Product $product): void
    {
        $token = $this->checkoutToken($user, $product);
        $this->actingAs($user)->post(route('checkout.store', $product), [
            'checkout_token' => $token,
            'payment_provider' => 'stripe',
        ])->assertRedirectContains('checkout.stripe.test');
        $order = $user->orders()->latest('id')->firstOrFail();
        $sessionId = (string) $order->payments()->value('provider_transaction_id');
        $this->actingAs($user)->get(route('payments.stripe.return', $order).'?session_id='.$sessionId)
            ->assertRedirect(route('checkout.success', $order));
        $this->assertSame('completed', $order->fresh()->status);
    }

    private function startAndReturnPayPal(User $user, Product $product): void
    {
        $token = $this->checkoutToken($user, $product);
        $this->actingAs($user)->post(route('checkout.store', $product), [
            'checkout_token' => $token,
            'payment_provider' => 'paypal',
        ])->assertRedirectContains('sandbox.paypal.com');
        $order = $user->orders()->latest('id')->firstOrFail();
        $providerOrder = (string) data_get($order->payments()->firstOrFail()->provider_data, 'paypal_order_id');
        $this->actingAs($user)->get(route('payments.paypal.return', $order).'?token='.$providerOrder)
            ->assertRedirect(route('checkout.success', $order));
        $this->actingAs($user)->get(route('payments.paypal.return', $order).'?token='.$providerOrder)
            ->assertRedirect(route('checkout.success', $order));
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(1, $order->payments()->count());
    }

    private function checkoutToken(User $user, Product $product): string
    {
        $this->actingAs($user)->get(route('checkout.show', $product))->assertOk();

        return (string) session('checkout_tokens.'.$product->id);
    }

    private function oneTimeProduct(string $type, string $sku, string $name, float $price): Product
    {
        return Product::query()->create([
            'type' => $type,
            'sku' => $sku,
            'name' => $name,
            'slug' => str($sku)->lower()->replace('_', '-'),
            'short_description' => $name,
            'description' => $name,
            'price' => $price,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
        ]);
    }
}
