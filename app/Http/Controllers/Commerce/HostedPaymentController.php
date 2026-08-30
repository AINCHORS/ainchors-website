<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Commerce\CheckoutService;
use App\Services\Commerce\HostedPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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

            return redirect()->route('checkout.failed', $order)
                ->with('payment_failure_context', [
                    'state' => 'failed',
                    'provider' => 'stripe',
                ]);
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

            return redirect()->route('checkout.failed', $order)
                ->with('payment_failure_context', [
                    'state' => 'failed',
                    'provider' => 'paypal',
                ]);
        }

        return redirect()->route('checkout.success', $order);
    }

    public function cancel(Request $request, string $provider, Order $order): RedirectResponse
    {
        $this->assertOwned($request, $order);
        abort_unless(in_array($provider, ['stripe', 'paypal'], true), 404);
        $product = $order->items()->firstOrFail()->product;

        $this->checkout->cancelPendingPayment($order, $provider);
        $request->session()->forget('checkout_tokens.'.$product->id);

        return redirect()->route('checkout.failed', $order)
            ->with('payment_failure_context', [
                'state' => 'cancelled',
                'provider' => $provider,
            ]);
    }

    public function failed(Request $request, Order $order): View|RedirectResponse
    {
        $this->assertOwned($request, $order);
        $order->loadMissing(['items.product', 'payments']);

        if ($order->status === 'completed' || $order->payments->contains('status', 'paid')) {
            return redirect()->route('checkout.success', $order);
        }

        $context = (array) $request->session()->get('payment_failure_context', []);

        return view('checkout.failed', [
            'order' => $order,
            'item' => $order->items->firstOrFail(),
            'state' => in_array(($context['state'] ?? null), ['cancelled', 'failed'], true)
                ? $context['state']
                : 'failed',
            'provider' => in_array(($context['provider'] ?? null), ['stripe', 'paypal'], true)
                ? $context['provider']
                : null,
        ]);
    }

    private function assertOwned(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }
}
