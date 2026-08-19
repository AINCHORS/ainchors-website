<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Commerce\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseHistoryController extends Controller
{
    public function __invoke(Request $request, OrderService $orders): View
    {
        return view('account.purchase-history', [
            'orders' => $orders->historyFor($request->user()),
        ]);
    }
}
