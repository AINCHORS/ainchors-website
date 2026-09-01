<?php

namespace App\Mail;

use App\Models\ExternalInvoice;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProviderInvoiceAvailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ExternalInvoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        $productName = (string) ($this->order->items->first()?->product_name ?? 'AINCHORS Purchase');

        return new Envelope(
            from: new Address(
                (string) config('commerce.payment_email.from_address', 'info@ainchors.com'),
                (string) config('commerce.payment_email.from_name', 'AINCHORS Training & Consulting'),
            ),
            subject: 'Payment Confirmation & Stripe Invoice — '.$productName,
        );
    }

    public function content(): Content
    {
        $item = $this->order->items->first();
        $product = $item?->product;
        $accessUrl = match (true) {
            $product?->isCourse() => route('learn.show', $product),
            $product?->isPackage() => route('my-courses'),
            default => route('purchase-history'),
        };
        $accessLabel = match (true) {
            $product?->isCourse() => 'Access Your Course',
            $product?->isPackage() => 'View Your Courses',
            default => 'View Purchase History',
        };
        $purchaseLabel = match (true) {
            $product?->isCourse() => 'Course',
            $product?->isPackage() => 'Course Package',
            default => 'Product',
        };
        $stripePayment = $this->order->payments
            ->where('provider', 'stripe')
            ->where('status', 'paid')
            ->sortByDesc('paid_at')
            ->first();

        return new Content(
            view: 'mail.provider-invoice-available',
            with: [
                'item' => $item,
                'accessUrl' => $accessUrl,
                'accessLabel' => $accessLabel,
                'purchaseLabel' => $purchaseLabel,
                'stripePayment' => $stripePayment,
                'invoiceUrl' => (string) $this->invoice->getRawOriginal('invoice_url'),
            ],
        );
    }

    /** @return array<int, mixed> */
    public function attachments(): array
    {
        return [];
    }
}
