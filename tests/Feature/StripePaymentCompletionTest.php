<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ExternalInvoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StripePaymentCompletionTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        $this->artisan('ainchors:populate-legacy-course-catalogue')->assertExitCode(0);
        $this->artisan('ainchors:populate-course-learning-content')->assertExitCode(0);

        Product::query()
            ->where('type', 'course')
            ->with('courseContent')
            ->get()
            ->each(function (Product $course): void {
                if (filled($course->courseContent?->video_url)) {
                    Storage::disk('local')->put($course->courseContent->video_url, 'phase-two-test-video');
                }
            });

        $this->webhookSecret = implode('_', ['whsec', 'phase', 'two', 'fixture']);
        config([
            'commerce.payment.driver' => 'hosted',
            'commerce.payment.environment' => 'sandbox',
            'commerce.payment.enabled_providers' => ['stripe'],
            'commerce.payment.stripe.secret' => implode('_', ['sk', 'test', 'phase', 'two', 'fixture']),
            'commerce.payment.stripe.webhook_secret' => $this->webhookSecret,
            'commerce.invoices.provider_hosts.stripe' => ['invoice.stripe.com'],
        ]);
    }

    public function test_verified_return_completes_payment_order_and_one_active_enrollment_and_refresh_is_safe(): void
    {
        $sessionId = 'cs_test_verified_return';
        Http::fake(function (ClientRequest $request) use ($sessionId) {
            if ($request->method() === 'POST') {
                return Http::response(['id' => $sessionId, 'url' => 'https://checkout.stripe.test/verified-return']);
            }

            $order = Order::query()->firstOrFail();

            return Http::response($this->paidSession($order, $sessionId));
        });

        $user = User::factory()->create();
        $course = $this->course();
        $order = $this->startCheckout($user, $course, $sessionId);

        $this->assertSame('awaiting_payment', $order->status);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'pending']);
        $this->assertDatabaseCount('enrollments', 0);

        $returnUrl = route('payments.stripe.return', $order).'?session_id='.$sessionId;
        $this->actingAs($user)->get($returnUrl)->assertRedirect(route('checkout.success', $order));
        $this->actingAs($user)->get($returnUrl)->assertRedirect(route('checkout.success', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_transaction_id' => $sessionId,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'product_id' => $course->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseHas('external_invoices', [
            'order_id' => $order->id,
            'provider' => 'stripe',
            'external_reference' => 'in_'.$sessionId,
            'status' => 'issued',
        ]);
        $invoice = ExternalInvoice::query()->firstOrFail();
        $this->actingAs($user)->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('View Invoice')
            ->assertSee(route('purchase-history.invoice', $invoice), false);
        $this->actingAs($user)->get(route('purchase-history.invoice', $invoice))
            ->assertRedirect('https://invoice.stripe.com/i/'.$sessionId);
        $this->actingAs($user)->get(route('my-courses'))->assertOk()->assertSee($course->name);
        $this->actingAs($user)->get(route('learn.show', $course))->assertOk();
    }

    public function test_verified_webhook_and_return_share_one_idempotent_completion_path(): void
    {
        $sessionId = 'cs_test_webhook_then_return';
        Http::fake(function (ClientRequest $request) use ($sessionId) {
            if ($request->method() === 'POST') {
                return Http::response(['id' => $sessionId, 'url' => 'https://checkout.stripe.test/webhook']);
            }

            return Http::response($this->paidSession(Order::query()->firstOrFail(), $sessionId));
        });

        $user = User::factory()->create();
        $course = $this->course();
        $order = $this->startCheckout($user, $course, $sessionId);
        $event = [
            'id' => 'evt_test_webhook_then_return',
            'type' => 'checkout.session.completed',
            'data' => ['object' => $this->paidSession($order, $sessionId)],
        ];

        $this->postSignedStripeWebhook($event)->assertOk()->assertJson(['received' => true]);
        $this->postSignedStripeWebhook($event)->assertOk()->assertJson(['received' => true]);
        $this->actingAs($user)
            ->get(route('payments.stripe.return', $order).'?session_id='.$sessionId)
            ->assertRedirect(route('checkout.success', $order));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseHas('enrollments', ['user_id' => $user->id, 'product_id' => $course->id, 'status' => 'active']);
    }

    public function test_mismatched_amount_or_currency_cannot_complete_or_grant_access(): void
    {
        $sessionId = 'cs_test_wrong_amount';
        Http::fake(fn () => Http::response(['id' => $sessionId, 'url' => 'https://checkout.stripe.test/wrong-amount']));

        $user = User::factory()->create();
        $course = $this->course();
        $order = $this->startCheckout($user, $course, $sessionId);
        $session = $this->paidSession($order, $sessionId);
        $session['amount_total'] = 1;
        $session['currency'] = 'myr';

        $this->postSignedStripeWebhook([
            'id' => 'evt_test_wrong_amount',
            'type' => 'checkout.session.completed',
            'data' => ['object' => $session],
        ])->assertBadRequest()->assertJson(['received' => false]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'awaiting_payment']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'pending']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_unknown_or_different_checkout_session_cannot_claim_a_pending_payment(): void
    {
        $expectedSessionId = 'cs_test_expected';
        Http::fake(fn () => Http::response(['id' => $expectedSessionId, 'url' => 'https://checkout.stripe.test/expected']));

        $user = User::factory()->create();
        $course = $this->course();
        $order = $this->startCheckout($user, $course, $expectedSessionId);
        $unexpectedSession = $this->paidSession($order, 'cs_test_unexpected');

        $this->postSignedStripeWebhook([
            'id' => 'evt_test_unexpected_session',
            'type' => 'checkout.session.completed',
            'data' => ['object' => $unexpectedSession],
        ])->assertBadRequest();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider_transaction_id' => $expectedSessionId,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('payments', ['provider_transaction_id' => 'cs_test_unexpected']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'awaiting_payment']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_unpaid_session_and_another_user_return_create_no_access(): void
    {
        $sessionId = 'cs_test_unpaid';
        Http::fake(fn () => Http::response(['id' => $sessionId, 'url' => 'https://checkout.stripe.test/unpaid']));

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $course = $this->course();
        $order = $this->startCheckout($owner, $course, $sessionId);
        $session = $this->paidSession($order, $sessionId);
        $session['payment_status'] = 'unpaid';

        $this->postSignedStripeWebhook([
            'id' => 'evt_test_unpaid',
            'type' => 'checkout.session.completed',
            'data' => ['object' => $session],
        ])->assertOk()->assertJson(['received' => true]);

        $this->actingAs($otherUser)
            ->get(route('payments.stripe.return', $order).'?session_id='.$sessionId)
            ->assertNotFound();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'awaiting_payment']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'pending']);
        $this->assertDatabaseCount('enrollments', 0);
    }

    /** @return array<string, mixed> */
    private function paidSession(Order $order, string $sessionId): array
    {
        return [
            'id' => $sessionId,
            'client_reference_id' => $order->order_number,
            'metadata' => ['order_number' => $order->order_number],
            'payment_status' => 'paid',
            'livemode' => false,
            'amount_total' => 1900,
            'currency' => 'usd',
            'invoice' => [
                'id' => 'in_'.$sessionId,
                'number' => 'TEST-'.$order->id,
                'hosted_invoice_url' => 'https://invoice.stripe.com/i/'.$sessionId,
            ],
        ];
    }

    /** @param array<string, mixed> $event */
    private function postSignedStripeWebhook(array $event)
    {
        $payload = json_encode($event, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $this->webhookSecret);

        return $this->call(
            'POST',
            route('payments.stripe.webhook'),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
            ],
            content: $payload,
        );
    }

    private function startCheckout(User $user, Product $course, string $sessionId): Order
    {
        $this->actingAs($user)->get(route('checkout.show', $course))->assertOk();
        $token = (string) session('checkout_tokens.'.$course->id);

        $this->actingAs($user)->post(route('checkout.store', $course), [
            'checkout_token' => $token,
            'payment_provider' => 'stripe',
        ])->assertRedirectContains('checkout.stripe.test');

        $order = Order::query()->firstOrFail();
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'stripe',
            'provider_transaction_id' => $sessionId,
            'status' => 'pending',
        ]);

        return $order;
    }

    private function course(): Product
    {
        return Product::query()->where('sku', 'SL-AI-001')->firstOrFail();
    }
}
