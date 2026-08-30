<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PaymentService
{
    /** @return Collection<int, Payment> */
    public function historyFor(Order $order): Collection
    {
        return $order->payments()->latest()->get();
    }

    public function createDemoPayment(Order $order): Payment
    {
        return $order->payments()->create([
            'provider' => 'demo',
            'payment_environment' => 'test',
            'provider_transaction_id' => 'DEMO-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => 'paid',
            'paid_at' => now(),
            'provider_data' => ['mode' => 'test'],
        ]);
    }

    public function beginStripePayment(Order $order): Payment
    {
        return $this->beginProviderPayment($order, 'stripe');
    }

    public function beginProviderPayment(Order $order, string $provider): Payment
    {
        if (! in_array($provider, ['stripe', 'paypal'], true)) {
            throw new \RuntimeException('The selected hosted payment provider is not supported.');
        }

        $existing = $order->payments()
            ->where('provider', $provider)
            ->whereIn('status', ['pending', 'processing'])
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        return $order->payments()->create([
            'provider' => $provider,
            'payment_environment' => Payment::inferEnvironment($provider, null, [
                'environment' => config('commerce.payment.environment'),
            ]),
            'provider_transaction_id' => null,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => 'pending',
            'paid_at' => null,
            'failure_reason' => null,
            'provider_data' => [
                'environment' => config('commerce.payment.environment'),
                'stage' => 'hosted_checkout_initialization',
            ],
        ]);
    }

    /** @param array<string, mixed> $providerData */
    public function attachProviderReference(Payment $payment, string $transactionId, array $providerData = []): Payment
    {
        if (! in_array($payment->provider, ['stripe', 'paypal'], true)
            || ! in_array($payment->status, ['pending', 'processing'], true)) {
            throw new \RuntimeException('The hosted payment attempt is no longer pending.');
        }

        $payment->update([
            'provider_transaction_id' => $transactionId,
            'payment_environment' => Payment::inferEnvironment($payment->provider, $transactionId, $providerData),
            'amount' => $payment->order->total_amount,
            'currency' => $payment->order->currency,
            'provider_data' => array_merge($payment->provider_data ?? [], $providerData),
        ]);

        return $payment->fresh();
    }

    public function markAttemptFailed(Payment $payment, string $safeReason): Payment
    {
        if (in_array($payment->status, ['pending', 'processing'], true)) {
            $payment->update([
                'status' => 'failed',
                'paid_at' => null,
                'failure_reason' => $safeReason,
                'provider_data' => array_merge($payment->provider_data ?? [], ['stage' => 'hosted_checkout_failed']),
            ]);
        }

        return $payment->fresh();
    }

    /** @param array<string, mixed> $providerData */
    public function createPendingPayment(Order $order, string $provider, string $transactionId, array $providerData = []): Payment
    {
        return $order->payments()->updateOrCreate(
            ['provider' => $provider, 'provider_transaction_id' => $transactionId],
            [
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'payment_environment' => Payment::inferEnvironment($provider, $transactionId, $providerData),
                'status' => 'pending',
                'paid_at' => null,
                'failure_reason' => null,
                'provider_data' => $providerData,
            ],
        );
    }

    public function markPendingPaymentFailed(Order $order, string $provider, string $reason): void
    {
        $order->payments()
            ->where('provider', $provider)
            ->whereIn('status', ['pending', 'processing'])
            ->update(['status' => 'failed', 'failure_reason' => $reason]);
    }
}
