<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Commerce\ExternalInvoiceService;
use App\Services\Commerce\OrderService;
use App\Services\Courses\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseHistoryController extends Controller
{
    public function __invoke(
        Request $request,
        OrderService $orders,
        EnrollmentService $enrollments,
        ExternalInvoiceService $invoices,
    ): View
    {
        $history = $orders->historyFor($request->user());
        $activeEnrollments = $enrollments->activeFor($request->user())->keyBy('product_id');

        return view('account.purchase-history', [
            'orders' => $history,
            'activeEnrollments' => $activeEnrollments,
            'customerInvoices' => $history->mapWithKeys(fn ($order): array => [
                $order->id => $invoices->customerFacingInvoiceFor($order),
            ])->filter(),
        ]);
    }
}
