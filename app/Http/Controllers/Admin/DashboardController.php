<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $metrics = [
            'total_users' => User::query()->count(),
            'new_users' => User::query()->where('created_at', '>=', now()->subDays(30))->count(),
            'active_products' => Product::query()->where('status', 'active')->count(),
            'total_orders' => Order::query()->count(),
            'completed_payments' => Payment::query()->where('status', 'paid')->count(),
            'enrollments' => Enrollment::query()->count(),
        ];

        // Revenue is deliberately kept separate for each currency and payment
        // provider. A `demo` provider row is test-mode data, not real revenue.
        $revenueByCurrency = Payment::query()
            ->where('status', 'paid')
            ->select('currency', 'provider')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('COUNT(*) as payment_count')
            ->groupBy('currency', 'provider')
            ->orderBy('currency')
            ->orderBy('provider')
            ->get();

        $recentOrders = Order::query()
            ->select([
                'id', 'order_number', 'user_id', 'status', 'currency',
                'total_amount', 'placed_at', 'created_at',
            ])
            ->with([
                'user:id,full_name,email,role,status',
                'payments' => fn ($query) => $query
                    ->select(['id', 'order_id', 'provider', 'amount', 'currency', 'status', 'paid_at'])
                    ->latest(),
            ])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('metrics', 'revenueByCurrency', 'recentOrders'));
    }
}
