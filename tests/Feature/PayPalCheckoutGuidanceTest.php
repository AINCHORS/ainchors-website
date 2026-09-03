<?php

namespace Tests\Feature;

use App\Models\Order;
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

    public function test_continue_with_paypal_preopens_the_named_window_from_the_button_click_gesture(): void
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

        $this->assertStringContainsString('@click="prepareHostedPayment()"', $html);
        $this->assertStringContainsString("window.open(\n                          '',\n                          'ainchors-paypal-payment'", $html);
        $this->assertStringContainsString('Preparing secure PayPal payment…', $html);
        $this->assertStringNotContainsString('@submit="prepareHostedPayment()"', $html);
    }

    public function test_paypal_waiting_card_matches_success_card_width_and_uses_equal_action_layout(): void
    {
        $order = new Order(['order_number' => 'AIN-PAYPAL-LAYOUT-TEST']);
        $invoiceUrl = 'https://www.sandbox.paypal.com/invoice/p/#INV2-LAYOUT-TEST';

        $html = view('checkout.paypal-waiting', compact('order', 'invoiceUrl'))->render();

        $this->assertStringContainsString('class="success-card success-card-compact payment-waiting-card"', $html);
        $this->assertStringContainsString('class="success-actions payment-waiting-actions"', $html);
        $this->assertStringContainsString('>Reopen PayPal</a>', $html);
        $this->assertStringNotContainsString('>Reopen PayPal Payment</a>', $html);
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
