<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseCommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['commerce.payment.driver' => 'demo']);
        Storage::fake('local');
        $this->artisan('ainchors:populate-legacy-course-catalogue')->assertExitCode(0);
        $this->artisan('ainchors:populate-course-learning-content')->assertExitCode(0);

        Product::query()
            ->where('type', 'course')
            ->with('courseContent')
            ->get()
            ->each(function (Product $course): void {
                if (filled($course->courseContent?->video_url)) {
                    Storage::disk('local')->put($course->courseContent->video_url, 'phase-one-test-video');
                }
            });
    }

    public function test_guest_checkout_redirects_to_login_and_preserves_intention(): void
    {
        $course = $this->course();
        $response = $this->get(route('checkout.show', $course));

        $response->assertRedirect(route('login'));
        $this->assertSame(route('checkout.show', $course), session('url.intended'));
    }

    public function test_registration_returns_to_original_checkout(): void
    {
        $course = $this->course();
        $this->get(route('checkout.show', $course));

        $response = $this->post(route('register.store'), [
            'full_name' => 'New Learner',
            'email' => 'new@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('checkout.show', $course));
        $this->assertAuthenticated();
    }

    public function test_login_returns_to_original_checkout(): void
    {
        $user = User::factory()->create(['email' => 'learner@example.com']);
        $course = $this->course();
        $this->get(route('checkout.show', $course));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('checkout.show', $course));
    }

    public function test_checkout_uses_readonly_account_identity_and_canonical_prices(): void
    {
        $user = User::factory()->create();
        $course = $this->course();

        $this->actingAs($user)->get(route('checkout.show', $course))
            ->assertOk()
            ->assertSee('value="'.$user->full_name.'" readonly', false)
            ->assertSee('value="'.$user->email.'" readonly', false)
            ->assertSee('USD 50')
            ->assertSee('USD 19');

        $package = $this->package();
        $this->actingAs($user)->get(route('checkout.show', $package))
            ->assertOk()->assertSee('USD 190')->assertSee('USD 150');
    }

    public function test_individual_demo_payment_creates_one_order_payment_and_enrollment_without_card_data(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $token = $this->checkoutToken($user, $course);

        $response = $this->actingAs($user)->post(route('checkout.store', $course), $this->demoPayment($token));
        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $response->assertRedirect(route('checkout.success', $order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['provider' => 'demo', 'status' => 'paid', 'amount' => 19, 'currency' => 'USD']);
        $this->assertDatabaseHas('enrollments', ['user_id' => $user->id, 'product_id' => $course->id]);
        $safePersistence = json_encode([$order->toArray(), $payment->toArray()]);
        $this->assertStringNotContainsString('4242424242424242', $safePersistence);
        $this->assertStringNotContainsString('12/30', $safePersistence);
        $this->assertStringNotContainsString('123', $safePersistence);
    }

    public function test_stripe_hosted_checkout_waits_for_server_verification_before_granting_access(): void
    {
        $this->enableStripeHostedCheckout();
        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'POST') {
                return Http::response(['id' => 'cs_test_ainchors', 'url' => 'https://checkout.stripe.test/session'], 200);
            }

            return Http::response([
                'id' => 'cs_test_ainchors',
                'client_reference_id' => Order::query()->firstOrFail()->order_number,
                'metadata' => ['order_number' => Order::query()->firstOrFail()->order_number],
                'payment_status' => 'paid',
                'amount_total' => 1900,
                'currency' => 'usd',
                'livemode' => false,
            ], 200);
        });

        $user = User::factory()->create();
        $course = $this->course();
        $token = $this->checkoutToken($user, $course);
        $this->actingAs($user)->get(route('checkout.show', $course))
            ->assertSee('Payment Method')
            ->assertSee('Secure payment processed by Stripe')
            ->assertSee('Continue with Stripe')
            ->assertSee('PayPal')
            ->assertSee('Coming soon')
            ->assertDontSee('name="payment_provider" value="paypal"', false)
            ->assertSee('name="payment_provider" value="stripe"', false)
            ->assertSee('Hosted payment test environment is enabled. No live charge will be made.')
            ->assertDontSee('Card Number');

        $this->actingAs($user)->post(route('checkout.store', $course), [
            'checkout_token' => $token,
            'payment_provider' => 'stripe',
        ])->assertRedirect('https://checkout.stripe.test/session');

        $order = Order::query()->firstOrFail();
        $this->assertSame('awaiting_payment', $order->status);
        $this->assertDatabaseHas('payments', ['provider' => 'stripe', 'payment_environment' => 'test', 'provider_transaction_id' => 'cs_test_ainchors', 'status' => 'pending']);
        $this->assertDatabaseCount('enrollments', 0);

        $this->actingAs($user)->get(route('payments.stripe.return', $order).'?session_id=cs_test_ainchors')
            ->assertRedirect(route('checkout.success', $order));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('payments', ['provider' => 'stripe', 'payment_environment' => 'test', 'provider_transaction_id' => 'cs_test_ainchors', 'status' => 'paid']);
        $this->assertDatabaseHas('enrollments', ['user_id' => $user->id, 'product_id' => $course->id]);
    }

    public function test_hosted_checkout_rejects_unconfigured_paypal_and_supports_course_packages_with_stripe(): void
    {
        $this->enableStripeHostedCheckout(['stripe', 'paypal']);
        config([
            'commerce.payment.paypal.client_id' => null,
            'commerce.payment.paypal.client_secret' => null,
            'commerce.payment.paypal.webhook_id' => null,
        ]);
        Http::fake(fn () => Http::response([
            'id' => 'cs_test_package',
            'url' => 'https://checkout.stripe.test/package',
        ], 200));

        $user = User::factory()->create();
        $course = $this->course();
        $courseToken = $this->checkoutToken($user, $course);

        $this->actingAs($user)->from(route('checkout.show', $course))->post(route('checkout.store', $course), [
            'checkout_token' => $courseToken,
            'payment_provider' => 'paypal',
        ])->assertRedirect(route('checkout.show', $course))->assertSessionHasErrors('payment_provider');

        $package = $this->package();
        $packageToken = $this->checkoutToken($user, $package);
        $this->actingAs($user)->from(route('checkout.show', $package))->post(route('checkout.store', $package), [
            'checkout_token' => $packageToken,
            'payment_provider' => 'stripe',
        ])->assertRedirect('https://checkout.stripe.test/package');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('payments', [
            'provider' => 'stripe',
            'provider_transaction_id' => 'cs_test_package',
            'status' => 'pending',
            'amount' => 150,
            'currency' => 'USD',
        ]);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertSame(10, $package->bundleProducts()->count());
    }

    public function test_paypal_hosted_package_payment_captures_before_unlocking_courses(): void
    {
        $this->enablePayPalHostedCheckout();
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-access-token', 'expires_in' => 3600], 200);
            }

            if (str_ends_with($request->url(), '/v2/checkout/orders')) {
                return Http::response([
                    'id' => 'PAYPAL-ORDER-PACKAGE',
                    'status' => 'CREATED',
                    'links' => [[
                        'rel' => 'approve',
                        'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-PACKAGE',
                    ]],
                ], 201);
            }

            $order = Order::query()->firstOrFail();

            return Http::response([
                'id' => 'PAYPAL-ORDER-PACKAGE',
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'custom_id' => $order->order_number,
                    'payments' => ['captures' => [[
                        'id' => 'PAYPAL-CAPTURE-PACKAGE',
                        'status' => 'COMPLETED',
                        'amount' => ['value' => '150.00', 'currency_code' => 'USD'],
                        'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-ORDER-PACKAGE']],
                    ]]],
                ]],
            ], 201);
        });

        $user = User::factory()->create();
        $package = $this->package();
        $token = $this->checkoutToken($user, $package);

        $this->actingAs($user)->get(route('checkout.show', $package))
            ->assertOk()
            ->assertSee('name="payment_provider" value="paypal"', false)
            ->assertSee('Continue with Paypal');

        $this->actingAs($user)->post(route('checkout.store', $package), [
            'checkout_token' => $token,
            'payment_provider' => 'paypal',
        ])->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-PACKAGE');

        $order = Order::query()->firstOrFail();
        $this->assertSame('awaiting_payment', $order->status);
        $this->assertDatabaseCount('enrollments', 0);

        $this->actingAs($user)
            ->get(route('payments.paypal.return', $order).'?token=PAYPAL-ORDER-PACKAGE')
            ->assertRedirect(route('checkout.success', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'paypal',
            'payment_environment' => 'test',
            'provider_transaction_id' => 'PAYPAL-CAPTURE-PACKAGE',
            'status' => 'paid',
        ]);
        $this->assertDatabaseCount('enrollments', 10);
    }

    public function test_stripe_hosted_service_payment_completes_order_without_creating_course_access(): void
    {
        $this->enableStripeHostedCheckout();
        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'POST') {
                return Http::response(['id' => 'cs_test_service', 'url' => 'https://checkout.stripe.test/service'], 200);
            }

            $order = Order::query()->firstOrFail();

            return Http::response([
                'id' => 'cs_test_service',
                'client_reference_id' => $order->order_number,
                'metadata' => ['order_number' => $order->order_number],
                'payment_status' => 'paid',
                'amount_total' => 4900,
                'currency' => 'usd',
                'livemode' => false,
            ], 200);
        });

        $service = Product::query()->create([
            'type' => 'service',
            'sku' => 'SERVICE-HOSTED-001',
            'name' => 'Hosted Advisory Service',
            'slug' => 'hosted-advisory-service',
            'short_description' => 'A focused advisory service.',
            'description' => 'A focused advisory service.',
            'price' => 49,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
        ]);
        $user = User::factory()->create();
        $token = $this->checkoutToken($user, $service);

        $this->actingAs($user)->post(route('checkout.store', $service), [
            'checkout_token' => $token,
            'payment_provider' => 'stripe',
        ])->assertRedirect('https://checkout.stripe.test/service');

        $order = Order::query()->firstOrFail();
        $this->actingAs($user)
            ->get(route('payments.stripe.return', $order).'?session_id=cs_test_service')
            ->assertRedirect(route('checkout.success', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseCount('enrollments', 0);
        $this->actingAs($user)->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('Your service order is confirmed.')
            ->assertSee('Purchase History')
            ->assertDontSee('Access Course');
    }

    public function test_verified_paypal_webhook_completes_the_matching_pending_package_once(): void
    {
        $this->enablePayPalHostedCheckout();
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-access-token', 'expires_in' => 3600], 200);
            }

            if (str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature')) {
                return Http::response(['verification_status' => 'SUCCESS'], 200);
            }

            return Http::response([
                'id' => 'PAYPAL-ORDER-WEBHOOK',
                'status' => 'CREATED',
                'links' => [[
                    'rel' => 'approve',
                    'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-WEBHOOK',
                ]],
            ], 201);
        });

        $user = User::factory()->create();
        $package = $this->package();
        $token = $this->checkoutToken($user, $package);
        $this->actingAs($user)->post(route('checkout.store', $package), [
            'checkout_token' => $token,
            'payment_provider' => 'paypal',
        ])->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-WEBHOOK');

        $order = Order::query()->firstOrFail();
        $event = [
            'id' => 'WH-PAYPAL-CAPTURE',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'PAYPAL-CAPTURE-WEBHOOK',
                'status' => 'COMPLETED',
                'amount' => ['value' => '150.00', 'currency_code' => 'USD'],
                'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-ORDER-WEBHOOK']],
            ],
        ];
        $headers = [
            'PayPal-Auth-Algo' => 'SHA256withRSA',
            'PayPal-Cert-Url' => 'https://api-m.sandbox.paypal.com/cert.pem',
            'PayPal-Transmission-Id' => 'paypal-transmission-id',
            'PayPal-Transmission-Sig' => 'paypal-signature',
            'PayPal-Transmission-Time' => now()->toIso8601String(),
        ];

        $this->withHeaders($headers)->postJson(route('payments.paypal.webhook'), $event)
            ->assertOk()
            ->assertJson(['received' => true]);
        $this->withHeaders($headers)->postJson(route('payments.paypal.webhook'), $event)
            ->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'paypal',
            'payment_environment' => 'test',
            'provider_transaction_id' => 'PAYPAL-CAPTURE-WEBHOOK',
            'status' => 'paid',
        ]);
        $this->assertDatabaseCount('enrollments', 10);
    }

    public function test_draft_inactive_incomplete_and_monthly_courses_cannot_checkout(): void
    {
        $user = User::factory()->create();
        $course = $this->course();

        $course->update(['status' => 'draft']);
        $this->actingAs($user)->get(route('checkout.show', $course))->assertNotFound();

        $course->update(['status' => 'inactive']);
        $this->actingAs($user)->get(route('checkout.show', $course))->assertNotFound();

        $course->update(['status' => 'active', 'billing_type' => 'monthly']);
        $this->actingAs($user)->get(route('checkout.show', $course))->assertNotFound();

        $course->update(['billing_type' => 'one_time']);
        Storage::disk('local')->delete($course->courseContent->video_url);
        $this->actingAs($user)->get(route('checkout.show', $course))->assertNotFound();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_already_enrolled_customer_cannot_start_another_course_payment(): void
    {
        $this->enableStripeHostedCheckout();
        Http::preventStrayRequests();

        $user = User::factory()->create();
        $course = $this->course();
        Enrollment::query()->create([
            'user_id' => $user->id,
            'product_id' => $course->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($user)->get(route('checkout.show', $course))->assertRedirect(route('learn.show', $course));
        $token = 'already-owned-checkout-token';
        $this->actingAs($user)
            ->withSession(['checkout_tokens.'.$course->id => $token])
            ->post(route('checkout.store', $course), [
                'checkout_token' => $token,
                'payment_provider' => 'stripe',
                'price' => '0.01',
                'currency' => 'JPY',
            ])
            ->assertRedirect(route('learn.show', $course));

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_inactive_authenticated_account_cannot_start_checkout(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);
        $course = $this->course();

        $this->actingAs($user)->get(route('checkout.show', $course))->assertForbidden();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_stripe_start_uses_canonical_values_and_keeps_complete_order_item_snapshots(): void
    {
        $this->enableStripeHostedCheckout();
        Http::fake(function (ClientRequest $request) {
            $this->assertDatabaseHas('orders', ['status' => 'awaiting_payment', 'total_amount' => 19, 'currency' => 'USD']);
            $this->assertDatabaseHas('payments', [
                'provider' => 'stripe',
                'provider_transaction_id' => null,
                'status' => 'pending',
                'amount' => 19,
                'currency' => 'USD',
            ]);
            $this->assertDatabaseCount('enrollments', 0);

            return Http::response(['id' => 'cs_test_snapshot', 'url' => 'https://checkout.stripe.test/snapshot'], 200);
        });

        $user = User::factory()->create();
        $course = $this->course();
        $originalName = $course->name;
        $originalSku = $course->sku;
        $token = $this->checkoutToken($user, $course);

        $this->actingAs($user)->post(route('checkout.store', $course), [
            'checkout_token' => $token,
            'payment_provider' => 'stripe',
            'price' => '0.01',
            'currency' => 'JPY',
            'name' => 'Browser supplied title',
            'sku' => 'BROWSER-SKU',
        ])->assertRedirect('https://checkout.stripe.test/snapshot');

        $order = Order::query()->firstOrFail();
        $item = $order->items()->firstOrFail();
        $course->update(['name' => 'Changed After Checkout', 'sku' => 'CHANGED-SKU', 'price' => 999, 'currency' => 'AUD']);

        $this->assertSame('19.00', $order->subtotal);
        $this->assertSame('19.00', $order->total_amount);
        $this->assertSame('USD', $order->currency);
        $this->assertSame($originalName, $item->product_name);
        $this->assertSame('19.00', $item->unit_price);
        $this->assertSame('course', data_get($item->metadata, 'product_type'));
        $this->assertSame($originalSku, data_get($item->metadata, 'sku'));
        $this->assertSame('USD', data_get($item->metadata, 'currency'));
        $this->assertDatabaseHas('payments', ['provider_transaction_id' => 'cs_test_snapshot', 'status' => 'pending']);
        $this->assertDatabaseCount('enrollments', 0);
        Http::assertSent(fn (ClientRequest $request): bool =>
            data_get($request->data(), 'invoice_creation.enabled') === 'true'
        );
    }

    public function test_failed_stripe_session_marks_attempt_failed_without_enrollment_and_can_retry(): void
    {
        $this->enableStripeHostedCheckout();
        Http::fakeSequence()
            ->push(['error' => ['message' => 'sanitized test failure']], 500)
            ->push(['error' => ['message' => 'sanitized test failure']], 500)
            ->push(['error' => ['message' => 'sanitized test failure']], 500)
            ->push(['id' => 'cs_test_retry', 'url' => 'https://checkout.stripe.test/retry'], 200);

        $user = User::factory()->create();
        $course = $this->course();
        $token = $this->checkoutToken($user, $course);

        $this->actingAs($user)->from(route('checkout.show', $course))->post(route('checkout.store', $course), [
            'checkout_token' => $token,
            'payment_provider' => 'stripe',
        ])->assertRedirect(route('checkout.show', $course))->assertSessionHasErrors('payment');

        $this->assertDatabaseHas('orders', ['status' => 'awaiting_payment']);
        $this->assertDatabaseHas('payments', [
            'provider' => 'stripe',
            'provider_transaction_id' => null,
            'status' => 'failed',
            'failure_reason' => 'Stripe Checkout Session initialization failed.',
        ]);
        $this->assertDatabaseCount('enrollments', 0);

        $this->actingAs($user)->post(route('checkout.store', $course), [
            'checkout_token' => $token,
            'payment_provider' => 'stripe',
        ])->assertRedirect('https://checkout.stripe.test/retry');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseHas('payments', ['provider_transaction_id' => 'cs_test_retry', 'status' => 'pending']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_cancelled_stripe_checkout_shows_unsuccessful_page_without_enrollment(): void
    {
        $this->enableStripeHostedCheckout();
        Http::fake(fn () => Http::response([
            'id' => 'cs_test_cancelled',
            'url' => 'https://checkout.stripe.test/cancelled',
        ], 200));

        $user = User::factory()->create();
        $course = $this->course();
        $token = $this->checkoutToken($user, $course);

        $this->actingAs($user)->post(route('checkout.store', $course), [
            'checkout_token' => $token,
            'payment_provider' => 'stripe',
        ])->assertRedirect('https://checkout.stripe.test/cancelled');

        $order = Order::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('payments.cancel', ['provider' => 'stripe', 'order' => $order]))
            ->assertRedirect(route('checkout.failed', $order))
            ->assertSessionHas('payment_failure_context', [
                'state' => 'cancelled',
                'provider' => 'stripe',
            ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'failed',
        ]);
        $this->assertDatabaseCount('enrollments', 0);

        $this->actingAs($user)
            ->get(route('checkout.failed', $order))
            ->assertOk()
            ->assertSee('Payment Unsuccessful')
            ->assertSee('You have not been charged.')
            ->assertSee('Try Again')
            ->assertSee('My Courses')
            ->assertSee('Purchase History');
    }

    public function test_different_checkout_tokens_reuse_one_pending_course_purchase(): void
    {
        $this->enableStripeHostedCheckout();
        Http::fake(fn () => Http::response(['id' => 'cs_test_single_pending', 'url' => 'https://checkout.stripe.test/single-pending'], 200));

        $user = User::factory()->create();
        $course = $this->course();
        $firstToken = $this->checkoutToken($user, $course);
        $payload = ['checkout_token' => $firstToken, 'payment_provider' => 'stripe'];

        $this->actingAs($user)->post(route('checkout.store', $course), $payload)
            ->assertRedirect('https://checkout.stripe.test/single-pending');

        $secondToken = 'different-course-checkout-token';
        $this->actingAs($user)
            ->withSession(['checkout_tokens.'.$course->id => $secondToken])
            ->post(route('checkout.store', $course), [
                'checkout_token' => $secondToken,
                'payment_provider' => 'stripe',
            ])
            ->assertRedirect('https://checkout.stripe.test/single-pending');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['provider_transaction_id' => 'cs_test_single_pending', 'status' => 'pending']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_invalid_demo_card_values_are_not_flushed_to_session_or_persisted(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $token = $this->checkoutToken($user, $course);

        $this->actingAs($user)->from(route('checkout.show', $course))->post(route('checkout.store', $course), [
            'checkout_token' => $token,
            'card_number' => '5555 5555 5555 4444',
            'expiry' => '01/40',
            'cvv' => '999',
        ])->assertRedirect(route('checkout.show', $course))->assertSessionHasErrors(['card_number', 'expiry', 'cvv']);

        $oldInput = session()->getOldInput();
        $this->assertArrayNotHasKey('card_number', $oldInput);
        $this->assertArrayNotHasKey('expiry', $oldInput);
        $this->assertArrayNotHasKey('cvv', $oldInput);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_individual_checkout_is_idempotent_and_owned_course_cta_changes(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $token = $this->checkoutToken($user, $course);
        $payload = $this->demoPayment($token);

        $this->actingAs($user)->post(route('checkout.store', $course), $payload);
        $this->actingAs($user)->post(route('checkout.store', $course), $payload);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('enrollments', 1);
        $this->actingAs($user)->get(route('courses.show', $course))->assertSee('ACCESS COURSE');
        $this->actingAs($user)->get(route('checkout.show', $course))->assertRedirect(route('learn.show', $course));
    }

    public function test_package_purchase_creates_one_order_one_payment_and_all_ten_enrollments(): void
    {
        $user = User::factory()->create();
        $package = $this->package();
        $token = $this->checkoutToken($user, $package);

        $response = $this->actingAs($user)->post(route('checkout.store', $package), $this->demoPayment($token));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['amount' => 150, 'currency' => 'USD', 'status' => 'paid']);
        $this->assertDatabaseCount('enrollments', 10);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('checkout.success', $order));
        $this->actingAs($user)->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('10 Courses Unlocked')
            ->assertSee('Go to My Courses');
    }

    public function test_package_creates_only_missing_enrollments_when_two_courses_are_owned(): void
    {
        $user = User::factory()->create();
        $courses = Product::query()->where('type', 'course')->orderBy('id')->get();
        foreach ($courses->take(2) as $course) {
            Enrollment::query()->create(['user_id' => $user->id, 'product_id' => $course->id, 'status' => 'active', 'enrolled_at' => now()]);
        }

        $package = $this->package();
        $token = $this->checkoutToken($user, $package);
        $this->actingAs($user)->post(route('checkout.store', $package), $this->demoPayment($token));

        $this->assertDatabaseCount('enrollments', 10);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_fully_owned_package_shows_access_my_courses_and_cannot_be_bought_again(): void
    {
        $user = User::factory()->create();
        foreach (Product::query()->where('type', 'course')->get() as $course) {
            Enrollment::query()->create(['user_id' => $user->id, 'product_id' => $course->id, 'status' => 'active', 'enrolled_at' => now()]);
        }

        $package = $this->package();
        $this->actingAs($user)->get(route('packages.show', $package))->assertSee('ACCESS MY COURSES');
        $this->actingAs($user)->get(route('checkout.show', $package))->assertRedirect(route('my-courses'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_learning_access_requires_authentication_and_enrollment(): void
    {
        $course = $this->course();
        $this->get(route('learn.show', $course))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('learn.show', $course))->assertRedirect(route('courses.show', $course));

        Enrollment::query()->create(['user_id' => $user->id, 'product_id' => $course->id, 'status' => 'active', 'enrolled_at' => now()]);
        $this->actingAs($user)->get(route('learn.show', $course))->assertOk()->assertSee('01 Start Here')->assertSee('02 Full Course')->assertSee('03 Course Recap');
    }

    public function test_protected_video_and_slides_require_enrollment_and_video_supports_ranges(): void
    {
        Storage::fake('local');
        $course = $this->course();
        Storage::disk('local')->put('courses/'.$course->slug.'/video/course.mp4', '0123456789');
        Storage::disk('local')->put('courses/'.$course->slug.'/slides/course-slides.pptx', 'pptx-test');
        $course->courseContent()->update(['slide_name' => 'Board Strategy Deck']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('course-media.video', $course))->assertForbidden();
        $this->actingAs($user)->get(route('course-media.slides', $course))->assertForbidden();

        Enrollment::query()->create(['user_id' => $user->id, 'product_id' => $course->id, 'status' => 'active', 'enrolled_at' => now()]);
        $this->actingAs($user)->call('GET', route('course-media.video', $course), server: ['HTTP_RANGE' => 'bytes=0-3'])
            ->assertStatus(206)->assertHeader('Content-Range', 'bytes 0-3/10');
        $this->actingAs($user)->get(route('course-media.slides', $course))
            ->assertOk()
            ->assertDownload('Board-Strategy-Deck.pptx');
        $this->assertFalse(file_exists(public_path('storage/courses/'.$course->slug.'/video/course.mp4')));
    }

    public function test_my_courses_only_lists_enrolled_course_products(): void
    {
        $user = User::factory()->create();
        $owned = $this->course();
        $other = Product::query()->where('type', 'course')->whereKeyNot($owned->id)->firstOrFail();
        Enrollment::query()->create(['user_id' => $user->id, 'product_id' => $owned->id, 'status' => 'active', 'enrolled_at' => now()]);

        $this->actingAs($user)->get(route('my-courses'))
            ->assertOk()->assertSee($owned->name)->assertDontSee($other->name)->assertDontSee($this->package()->name);
    }

    public function test_my_courses_uses_category_filter_without_a_search_field(): void
    {
        $user = User::factory()->create();
        $selfTraining = $this->course();
        $digitalMoney = Product::query()->where('type', 'course')->whereKeyNot($selfTraining->id)->firstOrFail();
        $selfTraining->update(['course_category' => 'self_training']);
        $digitalMoney->update(['course_category' => 'digital_money_mastery']);

        foreach ([$selfTraining, $digitalMoney] as $course) {
            Enrollment::query()->create([
                'user_id' => $user->id,
                'product_id' => $course->id,
                'status' => 'active',
                'enrolled_at' => now(),
            ]);
        }

        $this->actingAs($user)
            ->get(route('my-courses', ['course_category' => 'self_training']))
            ->assertOk()
            ->assertSee('Course category')
            ->assertSee($selfTraining->name)
            ->assertDontSee($digitalMoney->name)
            ->assertDontSee('name="q"', false)
            ->assertSee('value="self_training" selected', false);
    }

    public function test_catalogue_has_only_canonical_active_names_slugs_and_package_relations(): void
    {
        $this->assertDatabaseHas('products', ['sku' => 'SL-AI-001', 'name' => 'AI Prompt Engineering 101', 'slug' => 'ai-prompt-engineering-101', 'status' => 'active']);
        $this->assertDatabaseHas('products', ['sku' => 'SL-EP-006', 'name' => 'E-Payment Fundamentals', 'slug' => 'e-payment-fundamentals', 'status' => 'active']);
        $this->assertDatabaseMissing('products', ['type' => 'course', 'name' => 'Artificial Intelligence (AI)', 'status' => 'active']);
        $this->assertDatabaseMissing('products', ['type' => 'course', 'name' => 'E-Payment Systems', 'status' => 'active']);
        $this->assertSame(10, $this->package()->bundleProducts()->count());
        $this->assertDatabaseCount('course_contents', 10);
    }

    /** @param list<string> $providers */
    private function enableStripeHostedCheckout(array $providers = ['stripe']): void
    {
        config([
            'commerce.payment.driver' => 'hosted',
            'commerce.payment.environment' => 'sandbox',
            'commerce.payment.enabled_providers' => $providers,
            'commerce.payment.stripe.secret' => implode('_', ['sk', 'test', 'fixture']),
        ]);
    }

    private function enablePayPalHostedCheckout(): void
    {
        config([
            'commerce.payment.driver' => 'hosted',
            'commerce.payment.environment' => 'sandbox',
            'commerce.payment.enabled_providers' => ['paypal'],
            'commerce.payment.paypal.client_id' => 'paypal-client-id-fixture',
            'commerce.payment.paypal.client_secret' => 'paypal-client-secret-fixture',
            'commerce.payment.paypal.webhook_id' => 'paypal-webhook-id-fixture',
        ]);
    }

    /** @return array<string, string> */
    private function demoPayment(string $token): array
    {
        return ['checkout_token' => $token, 'card_number' => '4242 4242 4242 4242', 'expiry' => '12/30', 'cvv' => '123'];
    }

    private function checkoutToken(User $user, Product $product): string
    {
        $this->actingAs($user)->get(route('checkout.show', $product))->assertOk();

        return (string) session('checkout_tokens.'.$product->id);
    }

    private function course(): Product
    {
        return Product::query()->where('sku', 'SL-AI-001')->firstOrFail();
    }

    private function package(): Product
    {
        return Product::query()->where('sku', 'SL-PACKAGE-ALL-10')->firstOrFail();
    }
}
