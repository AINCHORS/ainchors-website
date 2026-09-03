<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Commerce\CheckoutService;
use App\Services\Commerce\ExternalInvoiceService;
use App\Services\Commerce\Gateways\PayPalGateway;
use App\Services\Commerce\HostedPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class HostedPaymentController extends Controller
{
    public function __construct(
        private readonly HostedPaymentService $hostedPayments,
        private readonly CheckoutService $checkout,
        private readonly ExternalInvoiceService $externalInvoices,
        private readonly PayPalGateway $paypal,
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

    public function cancel(Request $request, string $provider, Order $order): RedirectResponse
    {
        $this->assertOwned($request, $order);
        abort_unless(in_array($provider, ['stripe', 'paypal'], true), 404);
        $order->loadMissing(['items.product', 'payments', 'externalInvoices']);
        $product = $order->items->firstOrFail()->product;

        if ($provider === 'paypal' && $order->status !== 'completed') {
            $invoice = $order->externalInvoices
                ->where('provider', 'paypal')
                ->where('status', 'unpaid')
                ->sortByDesc('issued_at')
                ->first();

            if ($invoice) {
                try {
                    $this->paypal->cancelInvoice($invoice->external_reference);
                } catch (RuntimeException $exception) {
                    report($exception);

                    return redirect()->route('payments.paypal.waiting', $order)
                        ->with('payment_cancel_error', 'PayPal could not cancel this payment yet. If you already paid, keep this page open while AINCHORS verifies the payment.');
                }

                $invoice->update(['status' => 'void']);
            }
        }

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
        $latestPayment = $order->payments->sortByDesc('id')->first();
        $state = $order->status === 'cancelled'
            ? 'cancelled'
            : (in_array(($context['state'] ?? null), ['cancelled', 'failed'], true)
                ? $context['state']
                : 'failed');
        $provider = in_array(($context['provider'] ?? null), ['stripe', 'paypal'], true)
            ? $context['provider']
            : ($state === 'cancelled' && in_array($latestPayment?->provider, ['stripe', 'paypal'], true)
                ? $latestPayment->provider
                : null);

        return view('checkout.failed', [
            'order' => $order,
            'item' => $order->items->firstOrFail(),
            'state' => $state,
            'provider' => $provider,
        ]);
    }

    public function paypalWaiting(Request $request, Order $order): View|RedirectResponse
    {
        $this->assertOwned($request, $order);
        $order->loadMissing(['payments', 'externalInvoices']);

        if ($this->paypalPaymentIsComplete($order)) {
            return redirect()->route('checkout.success', $order);
        }

        if ($order->status === 'cancelled') {
            return redirect()->route('checkout.failed', $order);
        }

        $invoice = $order->externalInvoices
            ->where('provider', 'paypal')
            ->where('status', 'unpaid')
            ->sortByDesc('issued_at')
            ->first();
        $invoiceUrl = $invoice ? $this->externalInvoices->pendingPaymentUrl($invoice) : null;
        abort_unless($invoiceUrl, 404);

        return view('checkout.paypal-waiting', compact('order', 'invoiceUrl'));
    }

    public function paypalStatus(Request $request, Order $order): JsonResponse
    {
        $this->assertOwned($request, $order);
        $order->refresh()->loadMissing('payments');

        if ($this->paypalPaymentIsComplete($order)) {
            return response()->json([
                'state' => 'completed',
                'redirect_url' => route('checkout.success', $order),
            ]);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'state' => 'cancelled',
                'redirect_url' => route('checkout.failed', $order),
            ]);
        }

        if ($order->status === 'failed') {
            return response()->json([
                'state' => 'failed',
                'redirect_url' => route('checkout.failed', $order),
            ]);
        }

        return response()->json(['state' => 'pending']);
    }

    private function paypalPaymentIsComplete(Order $order): bool
    {
        return $order->status === 'completed'
            && $order->payments->contains(fn ($payment): bool =>
                $payment->provider === 'paypal' && $payment->status === 'paid'
            );
    }

    private function assertOwned(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }
}
