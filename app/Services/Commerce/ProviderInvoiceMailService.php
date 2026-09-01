<?php

namespace App\Services\Commerce;

use App\Mail\ProviderInvoiceAvailable;
use App\Models\ExternalInvoice;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class ProviderInvoiceMailService
{
    public function sendOnce(ExternalInvoice $invoice): bool
    {
        if ($invoice->provider !== 'stripe' || $invoice->status !== 'issued') {
            return false;
        }

        $claimedAt = now();
        $claimed = ExternalInvoice::query()
            ->whereKey($invoice->getKey())
            ->whereNull('email_sent_at')
            ->where(function ($query): void {
                $query->whereNull('email_claimed_at')
                    ->orWhere('email_claimed_at', '<=', now()->subMinutes(10));
            })
            ->update(['email_claimed_at' => $claimedAt]);

        if ($claimed !== 1) {
            return false;
        }

        try {
            $invoice->refresh()->loadMissing(['order.user', 'order.items.product', 'order.payments']);
            $recipient = trim((string) $invoice->order->user?->email);
            if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('The paid order does not have a valid recipient email address.');
            }

            Mail::to($recipient)->send(new ProviderInvoiceAvailable($invoice->order, $invoice));

            ExternalInvoice::query()
                ->whereKey($invoice->getKey())
                ->whereNull('email_sent_at')
                ->update([
                    'email_sent_at' => now(),
                    'email_claimed_at' => null,
                ]);

            return true;
        } catch (Throwable $exception) {
            ExternalInvoice::query()
                ->whereKey($invoice->getKey())
                ->where('email_claimed_at', $claimedAt)
                ->whereNull('email_sent_at')
                ->update(['email_claimed_at' => null]);
            report($exception);

            return false;
        }
    }
}
