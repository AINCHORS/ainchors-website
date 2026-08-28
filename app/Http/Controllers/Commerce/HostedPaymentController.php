<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Commerce\CheckoutService;
use App\Services\Commerce\HostedPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class HostedPaymentController extends Controller
{
    public function __construct(
        private readonly HostedPaymentService $hostedPayments,
        private readonly CheckoutService $checkout,
    ) {}

    public function stripeReturn(Request $request, Order $order): RedirectResponse
    {
        $this->assertOwned($request, $order);
        $sessionId = (string) $request->query('session_id');
        abort_if($sessionId === '', 400);

        try {
            $order = $this->hostedPayments->completeStripeReturn($order, $sessionId);
        } catch (RuntimeException $exception) {
            report($exception);
            $product = $order->items()->firstOrFail()->product;

            return redirect()->route('checkout.show', $product)
                ->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('checkout.success', $order);
    }

    public function paypalReturn(Request $request, Order $order): RedirectResponse
    {
        $this->assertOwned($request, $order);
        $payPalOrderId = (string) $request->query('token');
        abort_if($payPalOrderId === '', 400);

        try {
            $order = $this->hostedPayments->completePayPalReturn($order, $payPalOrderId);
        } catch (RuntimeException $exception) {
            report($exception);
            $product = $order->items()->firstOrFail()->product;

            return redirect()->route('checkout.show', $product)
                ->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('checkout.success', $order);
    }

    public function cancel(Request $request, Order $order, string $provider): RedirectResponse
    {
        $this->assertOwned($request, $order);
        abort_unless(in_array($provider, ['stripe', 'paypal'], true), 404);
        $product = $order->items()->firstOrFail()->product;

        $this->checkout->cancelPendingPayment($order, $provider);
        $request->session()->forget('checkout_tokens.'.$product->id);

        return redirect()->route('checkout.show', $product)->with('payment_cancelled', 'Payment was cancelled. You have not been charged.');
    }

    private function assertOwned(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }
}
