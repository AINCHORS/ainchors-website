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

        if ($order->status !== 'completed') {
            throw new InvalidArgumentException('An external invoice can only be attached to a completed order.');
        }

        if (! preg_match('/^[a-z0-9][a-z0-9_-]{1,49}$/', $provider)
            || $externalReference === ''
            || ! in_array($status, ['issued', 'void'], true)
            || ! $this->isTrustedUrl($invoiceUrl)) {
            throw new InvalidArgumentException('The external invoice data is not valid or trusted.');
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
        return $order->externalInvoices
            ->where('status', 'issued')
            ->sortByDesc('issued_at')
            ->first(fn (ExternalInvoice $invoice): bool => $this->isTrustedUrl($invoice->getRawOriginal('invoice_url')));
    }

    public function customerUrl(ExternalInvoice $invoice): ?string
    {
        if ($invoice->status !== 'issued') {
            return null;
        }

        $url = (string) $invoice->getRawOriginal('invoice_url');

        return $this->isTrustedUrl($url) ? $url : null;
    }

    public function isTrustedUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $trustedHosts = config('commerce.invoices.trusted_hosts', []);

        return ($parts['scheme'] ?? null) === 'https'
            && $host !== ''
            && ! isset($parts['user'], $parts['pass'])
            && in_array($host, $trustedHosts, true);
    }
}
