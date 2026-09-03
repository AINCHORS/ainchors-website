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
            $this->assertStringContainsString("@include('checkout.partials.payment-state-styles')", $template);
        }
    }

    public function test_payment_state_css_is_vertical_and_keeps_three_equal_buttons_side_by_side_on_desktop(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $stateStyles = file_get_contents(resource_path('views/checkout/partials/payment-state-styles.blade.php'));
        $paymentCss = $css."\n".$stateStyles;

        $this->assertIsString($css);
        $this->assertIsString($stateStyles);
        $this->assertStringContainsString('.payment-state-card { width: min(100%, 620px); min-height: 700px;', $paymentCss);
        $this->assertStringContainsString('.payment-state-actions { display: grid; grid-template-columns: repeat(3,minmax(0,1fr));', $paymentCss);
        $this->assertStringContainsString('.payment-state-button {', $paymentCss);
        $this->assertStringContainsString('background: #37ad82;', $paymentCss);
        $this->assertStringContainsString('color: #fff;', $paymentCss);
        $this->assertStringContainsString('.payment-state-button:hover,.payment-state-button:focus-visible { background: #e8fff7; color: #37ad82;', $stateStyles);
        $this->assertStringContainsString('@media (max-width: 640px)', $paymentCss);
        $this->assertStringContainsString('.payment-state-actions { grid-template-columns: 1fr; }', $paymentCss);
    }

    public function test_terminal_payment_titles_stay_on_one_line_and_inside_the_card_without_forcing_the_waiting_title(): void
    {
        $success = file_get_contents(resource_path('views/checkout/success.blade.php'));
        $failed = file_get_contents(resource_path('views/checkout/failed.blade.php'));
        $waiting = file_get_contents(resource_path('views/checkout/paypal-waiting.blade.php'));
        $stateStyles = file_get_contents(resource_path('views/checkout/partials/payment-state-styles.blade.php'));

        $this->assertIsString($success);
        $this->assertIsString($failed);
        $this->assertIsString($waiting);
        $this->assertIsString($stateStyles);
        $this->assertStringContainsString('<h1 class="payment-result-title">Payment Successful</h1>', $success);
        $this->assertStringContainsString('<h1 class="payment-result-title">{{ $pageTitle }}</h1>', $failed);
        $this->assertStringNotContainsString('payment-result-title', $waiting);
        $this->assertStringContainsString('.payment-result-title { font-size: clamp(22px,7vw,40px); max-width: 100%; white-space: nowrap;', $stateStyles);
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
