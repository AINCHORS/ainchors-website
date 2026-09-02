<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Services\Commerce\Gateways\PayPalGateway;
use App\Services\Commerce\Gateways\StripeGateway;
use App\Services\Commerce\HostedPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $event = $request->json()->all();
        $headers = [
            'paypal-auth-algo' => $request->header('PayPal-Auth-Algo'),
            'paypal-cert-url' => $request->header('PayPal-Cert-Url'),
            'paypal-transmission-id' => $request->header('PayPal-Transmission-Id'),
            'paypal-transmission-sig' => $request->header('PayPal-Transmission-Sig'),
            'paypal-transmission-time' => $request->header('PayPal-Transmission-Time'),
        ];

        try {
            if (! $this->paypal->webhookIsVerified($headers, $event)) {
                return response()->json(['received' => false], 400);
            }
            if (($event['event_type'] ?? null) === 'INVOICING.INVOICE.PAID') {
                $invoiceId = (string) data_get($event, 'resource.id', '');
                if ($invoiceId === '') {
                    $self = collect(data_get($event, 'resource.links', []))->first(
                        fn ($link): bool => is_array($link) && ($link['rel'] ?? null) === 'self',
                    );
                    $invoiceId = basename((string) data_get($self, 'href', ''));
                }
                $this->hostedPayments->completePayPalInvoiceWebhook($invoiceId);
            }
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['received' => false], 400);
        }

        return response()->json(['received' => true]);
    }
}
