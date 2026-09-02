<?php

namespace App\Services\Commerce;

use App\Models\ExternalInvoice;
use App\Models\Order;
use InvalidArgumentException;

class ExternalInvoiceService
{
    public function record(
        Order $order,
        string $provider,
        string $externalReference,
        string $invoiceUrl,
        ?string $invoiceNumber = null,
        ?string $status = 'issued',
    ): ExternalInvoice {
        $provider = strtolower(trim($provider));
        $externalReference = trim($externalReference);
        $status = strtolower(trim((string) $status));

        $isPayPalPendingInvoice = $provider === 'paypal' && $status === 'unpaid' && $order->status === 'awaiting_payment';
        $isCompletedProviderInvoice = $order->status === 'completed'
            && (in_array($status, ['issued', 'void'], true) || ($provider === 'paypal' && $status === 'paid'));
        if (! $isPayPalPendingInvoice && ! $isCompletedProviderInvoice) {
            throw new InvalidArgumentException('An external invoice can only be attached to a completed order.');
        }

        if (! preg_match('/^[a-z0-9][a-z0-9_-]{1,49}$/', $provider)
            || $externalReference === ''
            || ! in_array($status, ['issued', 'paid', 'unpaid', 'void'], true)
            || ! $this->isTrustedProviderUrl($provider, $invoiceUrl)) {
            throw new InvalidArgumentException('The external invoice data is not valid or trusted.');
        }

        $existing = ExternalInvoice::query()
            ->where('provider', $provider)
            ->where('external_reference', $externalReference)
            ->first();

        if ($existing && $existing->order_id !== $order->id) {
            throw new InvalidArgumentException('A provider invoice cannot be reassigned to a different order.');
        }

        return ExternalInvoice::query()->updateOrCreate(
            ['provider' => $provider, 'external_reference' => $externalReference],
            [
                'order_id' => $order->id,
                'invoice_number' => filled($invoiceNumber) ? trim((string) $invoiceNumber) : null,
                'invoice_url' => $invoiceUrl,
                'status' => $status,
                'issued_at' => now(),
            ],
        );
    }

    public function customerFacingInvoiceFor(Order $order): ?ExternalInvoice
    {
        $payment = $order->payments
            ->where('status', 'paid')
            ->sortByDesc('paid_at')
            ->first();

        return $payment
            ? $this->customerFacingInvoiceForProvider($order, $payment->provider)
            : null;
    }

    public function customerFacingInvoiceForProvider(Order $order, string $provider): ?ExternalInvoice
    {
        $provider = strtolower(trim($provider));

        if ($provider === '') {
            return null;
        }

        $visibleStatus = $provider === 'paypal' ? 'paid' : 'issued';

        return $order->externalInvoices
            ->where('provider', $provider)
            ->where('status', $visibleStatus)
            ->sortByDesc('issued_at')
            ->first(fn (ExternalInvoice $invoice): bool => $this->isTrustedProviderUrl(
                $invoice->provider,
                (string) $invoice->getRawOriginal('invoice_url'),
            ));
    }

    public function providerInvoiceFor(Order $order, string $provider): ?ExternalInvoice
    {
        $provider = strtolower(trim($provider));

        $visibleStatus = $provider === 'paypal' ? 'paid' : 'issued';

        return $order->externalInvoices
            ->where('provider', $provider)
            ->where('status', $visibleStatus)
            ->sortByDesc('issued_at')
            ->first(fn (ExternalInvoice $invoice): bool => $this->isTrustedProviderUrl(
                $provider,
                (string) $invoice->getRawOriginal('invoice_url'),
            ));
    }

    public function customerUrl(ExternalInvoice $invoice): ?string
    {
        if (! in_array($invoice->status, ['issued', 'paid'], true)) {
            return null;
        }

        $url = (string) $invoice->getRawOriginal('invoice_url');

        return $this->isTrustedProviderUrl($invoice->provider, $url) ? $url : null;
    }

    public function pendingPaymentUrl(ExternalInvoice $invoice): ?string
    {
        if ($invoice->provider !== 'paypal' || $invoice->status !== 'unpaid') {
            return null;
        }

        $url = (string) $invoice->getRawOriginal('invoice_url');

        return $this->isTrustedProviderUrl('paypal', $url) ? $url : null;
    }

    public function isTrustedProviderUrl(string $provider, string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $provider = strtolower(trim($provider));
        $trustedHosts = config('commerce.invoices.provider_hosts.'.$provider, []);

        return ($parts['scheme'] ?? null) === 'https'
            && $host !== ''
            && ! isset($parts['user'], $parts['pass'])
            && is_array($trustedHosts)
            && in_array($host, $trustedHosts, true);
    }
}
