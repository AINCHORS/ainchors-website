<?php

namespace Tests\Feature;

use App\Models\ExternalInvoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class PayPalInvoicingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
        $this->artisan('ainchors:populate-legacy-course-catalogue')->assertExitCode(0);
        $this->artisan('ainchors:populate-course-learning-content')->assertExitCode(0);
    }

    public function test_paypal_checkout_creates_sends_and_reuses_one_genuine_unpaid_invoice(): void
    {
        $this->enablePayPal();
        [$user, $product] = $this->customerAndCourse();
        $invoiceId = 'INV2-TEST-ONE';
        $invoice = $this->invoicePayload($invoiceId, 'DRAFT', 'AIN-PLACEHOLDER');

        Http::fake(function (ClientRequest $request) use (&$invoice, $invoiceId) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token'], 200);
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
        $first = $this->actingAs($user)->post(route('checkout.store', $product), [
            'checkout_token' => $token,
            'payment_provider' => 'paypal',
        ]);
        $order = Order::query()->latest('id')->firstOrFail();
        $invoice['detail']['reference'] = $order->order_number;
        $invoiceUrl = 'https://www.sandbox.paypal.com/invoice/p/#'.$invoiceId;
        $waitingUrl = route('payments.paypal.waiting', $order);
        $first->assertRedirect($invoiceUrl);

        $payment = $order->payments()->where('provider', 'paypal')->firstOrFail();
        $this->assertSame($invoiceId, data_get($payment->provider_data, 'paypal_invoice_id'));
        $this->assertDatabaseHas('external_invoices', [
            'order_id' => $order->id,
            'provider' => 'paypal',
            'external_reference' => $invoiceId,
            'status' => 'unpaid',
        ]);

        $this->actingAs($user)->post(route('checkout.store', $product), [
            'checkout_token' => $token,
            'payment_provider' => 'paypal',
        ])->assertRedirect($invoiceUrl);

        $this->actingAs($user)->get($waitingUrl)
            ->assertOk()
            ->assertSee('Awaiting Payment')
            ->assertSee($invoiceUrl, false);
        $this->actingAs($user)->get(route('payments.paypal.status', $order))
            ->assertOk()
            ->assertJson(['state' => 'pending']);

        $recorded = collect(Http::recorded());
        $this->assertSame(1, $recorded->filter(
            fn (array $record): bool => str_ends_with($record[0]->url(), '/v1/oauth2/token')
        )->count());
        $this->assertSame(1, $recorded->filter(
            fn (array $record): bool => $record[0]->method() === 'POST'
                && str_ends_with($record[0]->url(), '/v2/invoicing/invoices')
        )->count());
        $this->assertSame(1, $recorded->filter(
            fn (array $record): bool => $record[0]->method() === 'POST'
                && str_ends_with($record[0]->url(), '/'.$invoiceId.'/send')
        )->count());
        Http::assertSent(fn (ClientRequest $request): bool =>
            $request->method() === 'POST'
            && str_ends_with($request->url(), '/v2/invoicing/invoices')
            && data_get($request->data(), 'detail.reference') === $order->order_number
            && strlen((string) data_get($request->data(), 'detail.invoice_number')) <= 25
            && data_get($request->data(), 'detail.invoice_number') !== $order->order_number
            && data_get($request->data(), 'detail.currency_code') === 'USD'
            && data_get($request->data(), 'primary_recipients.0.billing_info.email_address') === 'buyer@example.test'
            && data_get($request->data(), 'items.0.name') === 'AI Prompt Engineering 101'
            && data_get($request->data(), 'items.0.quantity') === '1'
            && data_get($request->data(), 'items.0.unit_amount.value') === '19.00'
            && data_get($request->data(), 'items.0.unit_amount.currency_code') === 'USD'
        );
        Http::assertSent(fn (ClientRequest $request): bool =>
            $request->method() === 'POST'
            && str_ends_with($request->url(), '/'.$invoiceId.'/send')
            && $request->data() === [
                'send_to_invoicer' => false,
                'send_to_recipient' => false,
            ]
        );
    }

    public function test_verified_paid_invoice_webhook_completes_once_and_rejects_external_transactions(): void
    {
        $this->enablePayPal();
        [$user, $product] = $this->customerAndCourse();
        $invoiceId = 'INV2-TEST-PAID';
        $invoice = $this->invoicePayload($invoiceId, 'DRAFT', 'AIN-PLACEHOLDER');

        Http::fake(function (ClientRequest $request) use (&$invoice, $invoiceId) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token'], 200);
            }
            if (str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature')) {
                return Http::response(['verification_status' => 'SUCCESS'], 200);
            }
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/v2/invoicing/invoices')) {
                $invoice['detail']['reference'] = (string) data_get($request->data(), 'detail.reference');
                return Http::response(['links' => [['rel' => 'self', 'href' => 'https://api-m.sandbox.paypal.com/v2/invoicing/invoices/'.$invoiceId]]], 201);
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
        $invoice['detail']['reference'] = $order->order_number;
        $invoice['status'] = 'PAID';
        $invoice['payments'] = [
            'paid_amount' => ['currency_code' => 'USD', 'value' => '19.00'],
            'transactions' => [[
                'payment_id' => 'PAYPAL-PAYMENT-123',
                'type' => 'PAYPAL',
                'method' => 'PAYPAL',
                'transaction_status' => 'SUCCESS',
                'amount' => ['currency_code' => 'USD', 'value' => '19.00'],
            ]],
        ];
        $invoice['due_amount'] = ['currency_code' => 'USD', 'value' => '0.00'];

        $headers = $this->webhookHeaders();
        $event = [
            'event_type' => 'INVOICING.INVOICE.PAID',
            'resource' => ['invoice' => ['id' => $invoiceId]],
        ];
        $this->withHeaders($headers)->postJson(route('payments.paypal.webhook'), $event)->assertOk();
        $this->withHeaders($headers)->postJson(route('payments.paypal.webhook'), $event)->assertOk();

        Http::assertSent(fn (ClientRequest $request): bool =>
            str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature')
            && data_get($request->data(), 'auth_algo') === $headers['PayPal-Auth-Algo']
            && data_get($request->data(), 'cert_url') === $headers['PayPal-Cert-Url']
            && data_get($request->data(), 'transmission_id') === $headers['PayPal-Transmission-Id']
            && data_get($request->data(), 'transmission_sig') === $headers['PayPal-Transmission-Sig']
            && data_get($request->data(), 'transmission_time') === $headers['PayPal-Transmission-Time']
            && data_get($request->data(), 'webhook_id') === 'webhook-id'
            && data_get($request->data(), 'webhook_event.event_type') === 'INVOICING.INVOICE.PAID'
        );

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'paypal',
            'provider_transaction_id' => 'PAYPAL-PAYMENT-123',
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('external_invoices', [
            'order_id' => $order->id,
            'provider' => 'paypal',
            'external_reference' => $invoiceId,
            'status' => 'paid',
        ]);
        $this->assertDatabaseCount('enrollments', 1);

        $externalInvoice = ExternalInvoice::query()->where('provider', 'paypal')->firstOrFail();
        $this->actingAs($user)->get(route('payments.paypal.status', $order))
            ->assertOk()
            ->assertJson([
                'state' => 'completed',
                'redirect_url' => route('checkout.success', $order),
            ]);
        $this->actingAs($user)->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('Access Course')
            ->assertSee('My Courses')
            ->assertSee('View Receipt')
            ->assertDontSee('Redirecting in')
            ->assertDontSee('data-success-seconds', false)
            ->assertDontSee('data-success-redirect', false)
            ->assertSee(route('purchase-history.invoice', $externalInvoice), false);
        $this->actingAs($user)->get(route('purchase-history'))
            ->assertOk()
            ->assertSee('View Receipt')
            ->assertSee(route('purchase-history.invoice', $externalInvoice), false)
            ->assertDontSee('paypal_invoice_id');
        $this->actingAs($user)->get(route('purchase-history.invoice', $externalInvoice))
            ->assertRedirect('https://www.sandbox.paypal.com/invoice/p/#'.$invoiceId);
        $this->actingAs(User::factory()->create())->get(route('purchase-history.invoice', $externalInvoice))->assertNotFound();

    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidPaidInvoiceCases')]
    public function test_invoice_integrity_mismatch_never_completes_or_enrolls(string $case): void
    {
        $this->enablePayPal();
        [$user, $product] = $this->customerAndCourse();
        $invoiceId = 'INV2-REJECT-'.strtoupper(str_replace('_', '-', $case));
        $invoice = $this->invoicePayload($invoiceId, 'DRAFT', 'placeholder');

        Http::fake(function (ClientRequest $request) use (&$invoice, $invoiceId) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token'], 200);
            }
            if (str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature')) {
                return Http::response(['verification_status' => 'SUCCESS'], 200);
            }
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/v2/invoicing/invoices')) {
                $invoice['detail']['reference'] = (string) data_get($request->data(), 'detail.reference');
                return Http::response(['rel' => 'self', 'href' => 'https://api-m.sandbox.paypal.com/v2/invoicing/invoices/'.$invoiceId], 201);
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
        $response = $this->actingAs($user)->post(route('checkout.store', $product), [
            'checkout_token' => $token,
            'payment_provider' => 'paypal',
        ]);
        $order = Order::query()->firstOrFail();
        $response->assertRedirect('https://www.sandbox.paypal.com/invoice/p/#'.$invoiceId);

        $invoice['status'] = 'PAID';
        $invoice['detail']['reference'] = $order->order_number;
        $invoice['payments'] = [
            'paid_amount' => ['currency_code' => 'USD', 'value' => '19.00'],
            'transactions' => [[
                'payment_id' => 'PAYPAL-REJECT-PAYMENT',
                'type' => 'PAYPAL',
                'method' => 'PAYPAL',
                'transaction_status' => 'SUCCESS',
                'amount' => ['currency_code' => 'USD', 'value' => '19.00'],
            ]],
        ];

        match ($case) {
            'invoice_id' => $invoice['id'] = 'INV2-DIFFERENT',
            'reference' => $invoice['detail']['reference'] = 'AIN-DIFFERENT',
            'amount' => $invoice['amount']['value'] = '18.99',
            'currency' => $invoice['amount']['currency_code'] = 'AUD',
            'partial' => $invoice['payments']['paid_amount']['value'] = '10.00',
            'external' => $invoice['payments']['transactions'][0]['type'] = 'EXTERNAL',
            'missing_payment_id' => $invoice['payments']['transactions'][0]['payment_id'] = '',
            'pending_transaction' => $invoice['payments']['transactions'][0]['transaction_status'] = 'PENDING',
            'draft' => $invoice['status'] = 'DRAFT',
            'unpaid' => $invoice['status'] = 'UNPAID',
        };

        $this->withHeaders($this->webhookHeaders())->postJson(route('payments.paypal.webhook'), [
            'event_type' => 'INVOICING.INVOICE.PAID',
            'resource' => ['id' => $invoiceId],
        ])->assertStatus(400);

        $this->assertSame('awaiting_payment', $order->fresh()->status);
        $this->assertSame('pending', $order->payments()->where('provider', 'paypal')->firstOrFail()->status);
        $this->assertDatabaseCount('enrollments', 0);
    }

    /** @return array<string, array{string}> */
    public static function invalidPaidInvoiceCases(): array
    {
        return [
            'wrong invoice ID' => ['invoice_id'],
            'wrong order reference' => ['reference'],
            'wrong amount' => ['amount'],
            'wrong currency' => ['currency'],
            'partial payment' => ['partial'],
            'external transaction' => ['external'],
            'missing payment id' => ['missing_payment_id'],
            'pending transaction' => ['pending_transaction'],
            'draft invoice' => ['draft'],
            'unpaid invoice' => ['unpaid'],
        ];
    }

    public function test_missing_or_invalid_webhook_signature_cannot_mutate_payment_state(): void
    {
        $this->enablePayPal();
        Http::fake(fn (ClientRequest $request) => str_ends_with($request->url(), '/v1/oauth2/token')
            ? Http::response(['access_token' => 'token'], 200)
            : Http::response(['verification_status' => 'FAILURE'], 200));

        $event = ['event_type' => 'INVOICING.INVOICE.PAID', 'resource' => ['id' => 'INV2-UNTRUSTED']];
        $this->postJson(route('payments.paypal.webhook'), $event)->assertStatus(400);
        $this->withHeaders($this->webhookHeaders())->postJson(route('payments.paypal.webhook'), $event)->assertStatus(400);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_webhook_signature_verification_uses_the_unmodified_json_body(): void
    {
        $this->enablePayPal();

        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token'], 200);
            }

            if (str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature')) {
                return Http::response(['verification_status' => 'SUCCESS'], 200);
            }

            return Http::response([], 500);
        });

        $event = [
            'event_type' => 'TEST.EVENT',
            'resource' => ['id' => '', 'note' => '  unchanged  ', 'settings' => (object) [], 'items' => []],
        ];

        $this->withHeaders($this->webhookHeaders())
            ->postJson(route('payments.paypal.webhook'), $event)
            ->assertOk();

        Http::assertSent(fn (ClientRequest $request): bool =>
            str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature')
            && data_get($request->data(), 'webhook_event.resource.id') === ''
            && data_get($request->data(), 'webhook_event.resource.note') === '  unchanged  '
            && data_get(json_decode($request->body()), 'webhook_event.resource.settings') instanceof \stdClass
            && data_get(json_decode($request->body()), 'webhook_event.resource.items') === []
        );
    }

    public function test_send_failure_keeps_stored_draft_and_retry_does_not_create_a_second_invoice(): void
    {
        $this->enablePayPal();
        [$user, $product] = $this->customerAndCourse();
        $invoiceId = 'INV2-SEND-RETRY';
        $invoice = $this->invoicePayload($invoiceId, 'DRAFT', 'placeholder');
        $sendFails = true;

        Http::fake(function (ClientRequest $request) use (&$invoice, &$sendFails, $invoiceId) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'token'], 200);
            }
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/v2/invoicing/invoices')) {
                $invoice['detail']['reference'] = (string) data_get($request->data(), 'detail.reference');
                return Http::response(['rel' => 'self', 'href' => 'https://api-m.sandbox.paypal.com/v2/invoicing/invoices/'.$invoiceId], 201);
            }
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/'.$invoiceId.'/send')) {
                if ($sendFails) {
                    return Http::response([], 503);
                }
                $invoice['status'] = 'UNPAID';
                return Http::response([], 202);
            }
            if ($request->method() === 'GET' && str_ends_with($request->url(), '/'.$invoiceId)) {
                return Http::response($invoice, 200);
            }
            return Http::response([], 500);
        });

        $token = $this->checkoutToken($user, $product);
        $this->actingAs($user)->from(route('checkout.show', $product))->post(route('checkout.store', $product), [
            'checkout_token' => $token,
            'payment_provider' => 'paypal',
        ])->assertRedirect(route('checkout.show', $product))->assertSessionHasErrors('payment');

        $payment = Payment::query()->where('provider', 'paypal')->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame($invoiceId, data_get($payment->provider_data, 'paypal_invoice_id'));
        $this->assertDatabaseCount('enrollments', 0);

        $sendFails = false;
        $response = $this->actingAs($user)->post(route('checkout.store', $product), [
            'checkout_token' => $token,
            'payment_provider' => 'paypal',
        ]);
        $response->assertRedirect('https://www.sandbox.paypal.com/invoice/p/#'.$invoiceId);

        $creates = collect(Http::recorded())->filter(fn (array $record): bool =>
            $record[0]->method() === 'POST' && str_ends_with($record[0]->url(), '/v2/invoicing/invoices')
        );
        $this->assertCount(1, $creates);
    }

    public function test_paypal_external_invoice_rejects_an_untrusted_host(): void
    {
        $this->enablePayPal();
        [$user, $product] = $this->customerAndCourse();
        [$order] = app(\App\Services\Commerce\OrderService::class)->createForProduct($user, $product, 'untrusted-paypal-invoice');
        $order->update(['status' => 'completed']);

        $this->expectException(InvalidArgumentException::class);
        app(\App\Services\Commerce\ExternalInvoiceService::class)->record(
            $order,
            'paypal',
            'INV2-UNTRUSTED-HOST',
            'https://attacker.example.test/invoice/INV2-UNTRUSTED-HOST',
            'PP-UNTRUSTED',
            'paid',
        );
    }

    public function test_invoice_create_failure_never_grants_access_and_leaves_only_sanitized_failure_state(): void
    {
        $this->enablePayPal();
        [$user, $product] = $this->customerAndCourse();
        Http::fake(fn (ClientRequest $request) => str_ends_with($request->url(), '/v1/oauth2/token')
            ? Http::response(['access_token' => 'token'], 200)
            : Http::response(['debug_id' => 'provider-private-debug', 'details' => [['issue' => 'INTERNAL_ERROR']]], 500));

        $token = $this->checkoutToken($user, $product);
        $this->actingAs($user)->from(route('checkout.show', $product))->post(route('checkout.store', $product), [
            'checkout_token' => $token,
            'payment_provider' => 'paypal',
        ])->assertRedirect(route('checkout.show', $product))->assertSessionHasErrors('payment');

        $payment = Payment::query()->where('provider', 'paypal')->firstOrFail();
        $this->assertSame('failed', $payment->status);
        $this->assertNull($payment->provider_transaction_id);
        $this->assertStringNotContainsString('provider-private-debug', (string) $payment->failure_reason);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('external_invoices', 0);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payableProductTypes')]
    public function test_verified_invoice_uses_central_fulfillment_for_each_payable_product_type(string $type, int $expectedEnrollments): void
    {
        $this->enablePayPal();
        $user = User::factory()->create();
        $product = $this->productForType($type);
        $invoiceId = 'INV2-TYPE-'.strtoupper(str_replace('_', '-', $type));
        $amount = (string) $product->price;
        $invoice = $this->invoicePayload($invoiceId, 'DRAFT', 'placeholder');
        $invoice['amount']['value'] = $amount;

        Http::fake(function (ClientRequest $request) use (&$invoice, $invoiceId, $amount) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) return Http::response(['access_token' => 'token'], 200);
            if (str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature')) return Http::response(['verification_status' => 'SUCCESS'], 200);
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/v2/invoicing/invoices')) {
                $invoice['detail']['reference'] = (string) data_get($request->data(), 'detail.reference');
                return Http::response(['rel' => 'self', 'href' => 'https://api-m.sandbox.paypal.com/v2/invoicing/invoices/'.$invoiceId], 201);
            }
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/'.$invoiceId.'/send')) {
                $invoice['status'] = 'UNPAID';
                return Http::response([], 202);
            }
            if ($request->method() === 'GET' && str_ends_with($request->url(), '/'.$invoiceId)) return Http::response($invoice, 200);
            return Http::response([], 500);
        });

        $token = $this->checkoutToken($user, $product);
        $this->actingAs($user)->post(route('checkout.store', $product), ['checkout_token' => $token, 'payment_provider' => 'paypal']);
        $order = Order::query()->firstOrFail();
        $invoice['status'] = 'PAID';
        $invoice['detail']['reference'] = $order->order_number;
        $invoice['payments'] = [
            'paid_amount' => ['currency_code' => 'USD', 'value' => $amount],
            'transactions' => [[
                'payment_id' => 'PAYMENT-'.$type, 'type' => 'PAYPAL', 'method' => 'PAYPAL',
                'transaction_status' => 'SUCCESS',
                'amount' => ['currency_code' => 'USD', 'value' => $amount],
            ]],
        ];

        $this->withHeaders($this->webhookHeaders())->postJson(route('payments.paypal.webhook'), [
            'event_type' => 'INVOICING.INVOICE.PAID', 'resource' => ['id' => $invoiceId],
        ])->assertOk();
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame($expectedEnrollments, $user->enrollments()->count());
    }

    /** @return array<string, array{string,int}> */
    public static function payableProductTypes(): array
    {
        return [
            'course' => ['course', 1],
            'package' => ['course_package', 10],
            'service' => ['service', 0],
            'consulting' => ['consulting', 0],
        ];
    }

    /** @return array{User, Product} */
    private function customerAndCourse(): array
    {
        return [
            User::factory()->create(['email' => 'buyer@example.test']),
            Product::query()->where('sku', 'SL-AI-001')->firstOrFail(),
        ];
    }

    private function course(string $slug, string $name): Product
    {
        return Product::query()->create([
            'type' => 'course', 'sku' => strtoupper(str_replace('-', '-', $slug)), 'name' => $name,
            'slug' => $slug, 'short_description' => 'Course', 'description' => 'Course',
            'price' => 19, 'currency' => 'USD', 'billing_type' => 'one_time', 'status' => 'active',
            'is_featured' => false, 'sort_order' => 1,
        ]);
    }

    private function productForType(string $type): Product
    {
        if ($type === 'course') {
            return Product::query()->where('sku', 'SL-AI-001')->firstOrFail();
        }
        if ($type === 'course_package') {
            return Product::query()->where('sku', 'SL-PACKAGE-ALL-10')->firstOrFail();
        }

        return Product::query()->create([
            'type' => $type,
            'sku' => 'PAYPAL-'.strtoupper($type),
            'name' => 'PayPal '.ucfirst($type),
            'slug' => 'paypal-'.$type,
            'short_description' => 'PayPal '.$type,
            'description' => 'PayPal '.$type,
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
            'is_featured' => false,
            'sort_order' => 1,
        ]);
    }

    /** @return array<string, mixed> */
    private function invoicePayload(string $invoiceId, string $status, string $reference): array
    {
        return [
            'id' => $invoiceId,
            'status' => $status,
            'detail' => [
                'invoice_number' => 'PP-0001',
                'reference' => $reference,
                'currency_code' => 'USD',
                'metadata' => ['recipient_view_url' => 'https://www.sandbox.paypal.com/invoice/p/#'.$invoiceId],
            ],
            'amount' => ['currency_code' => 'USD', 'value' => '19.00'],
        ];
    }

    /** @return array<string, string> */
    private function webhookHeaders(): array
    {
        return [
            'PayPal-Auth-Algo' => 'SHA256withRSA',
            'PayPal-Cert-Url' => 'https://api-m.sandbox.paypal.com/cert.pem',
            'PayPal-Transmission-Id' => 'transmission-id',
            'PayPal-Transmission-Sig' => 'signature',
            'PayPal-Transmission-Time' => now()->toIso8601String(),
        ];
    }

    private function enablePayPal(): void
    {
        config([
            'commerce.payment.driver' => 'hosted',
            'commerce.payment.environment' => 'sandbox',
            'commerce.payment.enabled_providers' => ['paypal'],
            'commerce.payment.paypal.client_id' => 'client-id',
            'commerce.payment.paypal.client_secret' => 'client-secret',
            'commerce.payment.paypal.webhook_id' => 'webhook-id',
            'commerce.invoices.provider_hosts.paypal' => ['www.sandbox.paypal.com'],
        ]);
    }

    private function checkoutToken(User $user, Product $product): string
    {
        $this->actingAs($user)->get(route('checkout.show', $product))->assertOk();

        return (string) session('checkout_tokens.'.$product->id);
    }
}
