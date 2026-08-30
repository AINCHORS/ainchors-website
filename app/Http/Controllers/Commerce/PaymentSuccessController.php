<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Commerce\ExternalInvoiceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentSuccessController extends Controller
{
    public function __invoke(Request $request, Order $order, ExternalInvoiceService $invoices): View
    {
        abort_unless($order->user_id === $request->user()->id && $order->status === 'completed', 404);
        $order->load(['items.product', 'payments', 'externalInvoices']);
        $invoice = $invoices->customerFacingInvoiceFor($order);

        return view('checkout.success', compact('order', 'invoice'));
    }
}
