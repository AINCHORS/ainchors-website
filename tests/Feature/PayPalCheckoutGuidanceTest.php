<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayPalCheckoutGuidanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_continue_with_paypal_uses_the_direct_provider_tab_without_popup_or_handoff(): void
    {
        $this->enablePayPal();
        $user = User::factory()->create([
            'full_name' => 'PayPal Buyer',
            'email' => 'buyer@example.test',
        ]);
        $product = Product::query()->create([
            'type' => 'service',
            'sku' => 'PAYPAL-CLICK-GUIDANCE',
            'name' => 'PayPal Click Guidance',
            'slug' => 'paypal-click-guidance',
            'short_description' => 'PayPal click guidance test.',
            'description' => 'PayPal click guidance test.',
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
            'metadata' => [],
        ]);

        $html = (string) $this->actingAs($user)
            ->get(route('checkout.show', $product))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('formtarget="ainchors-paypal-payment"', $html);
        $this->assertStringContainsString('data-paypal-waiting-target-url=', $html);
        $this->assertStringContainsString('@submit="handleCheckoutSubmit()"', $html);
        $this->assertStringContainsString('Continue with PayPal', $html);
        $this->assertStringNotContainsString('/payments/paypal/handoff', $html);
        $this->assertStringNotContainsString('window.open(', $html);
        $this->assertStringNotContainsString('postMessage(', $html);
        $this->assertStringNotContainsString('Connecting to PayPal', $html);
    }

    public function test_original_checkout_tab_polls_for_the_waiting_target_while_provider_tab_owns_the_request(): void
    {
        $this->enablePayPal();
        $user = User::factory()->create();
        $product = Product::query()->create([
            'type' => 'service',
            'sku' => 'PAYPAL-WAITING-TARGET-GUIDANCE',
            'name' => 'PayPal Waiting Target Guidance',
            'slug' => 'paypal-waiting-target-guidance',
            'short_description' => 'PayPal waiting target guidance test.',
            'description' => 'PayPal waiting target guidance test.',
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
            'metadata' => [],
        ]);

        $html = (string) $this->actingAs($user)
            ->get(route('checkout.show', $product))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('startPayPalWaitingTargetPolling()', $html);
        $this->assertStringContainsString('fetch(targetUrl', $html);
        $this->assertStringContainsString('window.location.assign(result.redirect_url)', $html);
        $this->assertStringContainsString('formtarget="ainchors-paypal-payment"', $html);
        $this->assertStringNotContainsString('window.opener', $html);
        $this->assertStringNotContainsString('postMessage(', $html);
        $this->assertStringNotContainsString('/payments/paypal/handoff', $html);
    }

    public function test_paypal_waiting_card_uses_the_shared_payment_state_layout_without_handoff_coordination(): void
    {
        $template = file_get_contents(resource_path('views/checkout/paypal-waiting.blade.php'));

        $this->assertIsString($template);
        $this->assertStringContainsString('success-card payment-state-card payment-waiting-card', $template);
        $this->assertStringContainsString('success-actions payment-state-actions', $template);
        $this->assertStringContainsString('Awaiting Payment', $template);
        $this->assertStringContainsString('Reopen PayPal', $template);
        $this->assertStringContainsString('Cancel Payment', $template);
        $this->assertStringContainsString('fetch(statusUrl', $template);
        $this->assertStringNotContainsString('ainchors-paypal-handoff-ready', $template);
        $this->assertStringNotContainsString('ainchors-paypal-handoff-invoice', $template);
        $this->assertStringNotContainsString('postMessage(', $template);
        $this->assertStringNotContainsString('window.open(', $template);
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
