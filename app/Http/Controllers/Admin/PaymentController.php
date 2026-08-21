<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $payments = $this->safePaymentQuery()
            ->with($this->safeOrderRelation())
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('provider', 'like', "%{$search}%")
                        ->orWhere('provider_transaction_id', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($orderQuery) use ($search): void {
                            $orderQuery->where('order_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->when($request->filled('provider'), fn ($query) => $query->where('provider', $request->string('provider')->value()))
            ->when($request->filled('currency'), fn ($query) => $query->where('currency', strtoupper($request->string('currency')->value())))
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment): View
    {
        $payment = $this->safePaymentQuery()
            ->with($this->safeOrderRelation())
            ->findOrFail($payment->getKey());

        return view('admin.payments.show', compact('payment'));
    }

    private function safePaymentQuery()
    {
        // Provider payloads can contain gateway data and are never selected for
        // generic admin display. Financial records remain read-only routes.
        return Payment::query()->select([
            'id', 'order_id', 'provider', 'provider_transaction_id',
            'amount', 'currency', 'status', 'paid_at', 'failure_reason',
            'created_at', 'updated_at',
        ]);
    }

    /** @return array<string, \Closure> */
    private function safeOrderRelation(): array
    {
        return [
            'order' => fn ($query) => $query
                ->select([
                    'id', 'order_number', 'user_id', 'status', 'currency',
                    'total_amount', 'placed_at', 'created_at',
                ])
                ->with('user:id,full_name,email,role,status'),
        ];
    }
}
