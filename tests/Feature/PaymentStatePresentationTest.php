<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentStatePresentationTest extends TestCase
{
    public function test_payment_state_views_share_one_vertical_card_and_action_system(): void
    {
        $success = file_get_contents(resource_path('views/checkout/success.blade.php'));
        $failed = file_get_contents(resource_path('views/checkout/failed.blade.php'));
        $waiting = file_get_contents(resource_path('views/checkout/paypal-waiting.blade.php'));

        foreach ([$success, $failed, $waiting] as $template) {
            $this->assertIsString($template);
            $this->assertStringContainsString('payment-state-card', $template);
            $this->assertStringContainsString('payment-state-actions', $template);
            $this->assertStringContainsString('payment-state-button', $template);
        }
    }

    public function test_payment_state_css_is_vertical_and_keeps_three_equal_buttons_side_by_side_on_desktop(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.payment-state-card { width: min(100%, 520px); min-height: 560px;', $css);
        $this->assertStringContainsString('.payment-state-actions { display: grid; grid-template-columns: repeat(3,minmax(0,1fr));', $css);
        $this->assertStringContainsString('.payment-state-button {', $css);
        $this->assertStringContainsString('background: #37ad82;', $css);
        $this->assertStringContainsString('color: #fff;', $css);
        $this->assertStringContainsString('.payment-state-button:hover,.payment-state-button:focus-visible { background: #e8fff7; color: #37ad82;', $css);
        $this->assertStringContainsString('@media (max-width: 640px)', $css);
        $this->assertStringContainsString('.payment-state-actions { grid-template-columns: 1fr; }', $css);
    }

    public function test_terminal_payment_titles_stay_on_one_line_without_forcing_the_waiting_title(): void
    {
        $success = file_get_contents(resource_path('views/checkout/success.blade.php'));
        $failed = file_get_contents(resource_path('views/checkout/failed.blade.php'));
        $waiting = file_get_contents(resource_path('views/checkout/paypal-waiting.blade.php'));
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($success);
        $this->assertIsString($failed);
        $this->assertIsString($waiting);
        $this->assertIsString($css);
        $this->assertStringContainsString('<h1 class="payment-result-title">Payment Successful</h1>', $success);
        $this->assertStringContainsString('<h1 class="payment-result-title">{{ $pageTitle }}</h1>', $failed);
        $this->assertStringNotContainsString('payment-result-title', $waiting);
        $this->assertStringContainsString('.payment-result-title { font-size: clamp(30px,4vw,42px); white-space: nowrap;', $css);
    }

    public function test_failed_and_cancelled_states_do_not_mix_primary_and_secondary_button_variants(): void
    {
        $failed = file_get_contents(resource_path('views/checkout/failed.blade.php'));

        $this->assertIsString($failed);
        $this->assertStringNotContainsString('class="primary-button"', $failed);
        $this->assertStringNotContainsString('class="secondary-button"', $failed);
        $this->assertSame(3, substr_count($failed, 'payment-state-button'));
    }
}
