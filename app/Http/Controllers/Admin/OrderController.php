<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $orders = $this->safeOrderQuery()
            ->with([
                'user:id,full_name,email,role,status',
                'payments' => fn ($query) => $query
                    ->select($this->safePaymentColumns())
                    ->latest(),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->when($request->filled('currency'), fn ($query) => $query->where('currency', strtoupper($request->string('currency')->value())))
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order = $this->safeOrderQuery()
            ->with([
                'user:id,full_name,email,role,status,created_at',
                'items' => fn ($query) => $query
                    ->select(['id', 'order_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'line_total', 'created_at'])
                    ->with('product:id,name,slug,type,status'),
                'payments' => fn ($query) => $query
                    ->select($this->safePaymentColumns())
                    ->latest(),
            ])
            ->findOrFail($order->getKey());

        return view('admin.orders.show', compact('order'));
    }

    private function safeOrderQuery()
    {
        return Order::query()->select([
            'id', 'order_number', 'user_id', 'status', 'currency',
            'subtotal', 'discount_total', 'tax_total', 'total_amount',
            'placed_at', 'created_at', 'updated_at',
        ]);
    }

    /** @return array<int, string> */
    private function safePaymentColumns(): array
    {
        return [
            'id', 'order_id', 'provider', 'provider_transaction_id',
            'amount', 'currency', 'status', 'paid_at', 'failure_reason',
            'created_at', 'updated_at',
        ];
    }
}
