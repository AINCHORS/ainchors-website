<?php

namespace App\Http\Controllers\Commerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentSuccessController extends Controller
{
    public function __invoke(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id && $order->status === 'completed', 404);
        $order->load(['items.product', 'payments']);

        return view('checkout.success', compact('order'));
    }
}
