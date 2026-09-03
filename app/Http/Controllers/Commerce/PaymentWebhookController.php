<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Services\Commerce\Gateways\PayPalGateway;
use App\Services\Commerce\Gateways\StripeGateway;
use App\Services\Commerce\HostedPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly HostedPaymentService $hostedPayments,
        private readonly StripeGateway $stripe,
        private readonly PayPalGateway $paypal,
    ) {}

    public function stripe(Request $request): JsonResponse
    {
        try {
            $event = $this->stripe->verifiedWebhook($request->getContent(), (string) $request->header('Stripe-Signature'));
            if (in_array($event['type'] ?? null, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
                $this->hostedPayments->completeStripeWebhook((array) data_get($event, 'data.object', []));
            }
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['received' => false], 400);
        }

        return response()->json(['received' => true]);
    }

    public function paypal(Request $request): JsonResponse
    {
        $headers = [
            'paypal-auth-algo' => $request->header('PayPal-Auth-Algo'),
            'paypal-cert-url' => $request->header('PayPal-Cert-Url'),
            'paypal-transmission-id' => $request->header('PayPal-Transmission-Id'),
            'paypal-transmission-sig' => $request->header('PayPal-Transmission-Sig'),
            'paypal-transmission-time' => $request->header('PayPal-Transmission-Time'),
        ];

        try {
            // Preserve empty objects and untrimmed strings in the signed provider payload.
            $event = json_decode($request->getContent());
            if (! $event instanceof \stdClass) {
                throw new RuntimeException('PayPal webhook payload is not valid JSON.');
            }

            if (! $this->paypal->webhookIsVerified($headers, $event)) {
                return response()->json(['received' => false], 400);
            }
            if (data_get($event, 'event_type') === 'INVOICING.INVOICE.PAID') {
                $invoiceId = (string) data_get(
                    $event,
                    'resource.invoice.id',
                    data_get($event, 'resource.id', ''),
                );
                if ($invoiceId === '') {
                    $self = collect(data_get(
                        $event,
                        'resource.invoice.links',
                        data_get($event, 'resource.links', []),
                    ))->first(
                        fn ($link): bool => data_get($link, 'rel') === 'self',
                    );
                    $invoiceId = basename((string) data_get($self, 'href', ''));
                }
                $this->hostedPayments->completePayPalInvoiceWebhook($invoiceId);
                Log::info('PayPal paid invoice webhook processed.', [
                    'event_id' => data_get($event, 'id'),
                    'invoice_id' => $invoiceId,
                ]);
            }
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['received' => false], 400);
        }

        return response()->json(['received' => true]);
    }
}
