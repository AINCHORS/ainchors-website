<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\Enrollment;
use App\Models\JobApplication;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $paidPayments = Payment::query()->liveRevenue()->count();

        $metrics = [
            'total_users' => User::query()->count(),
            'new_users' => User::query()->where('created_at', '>=', now()->subDays(30))->count(),
            'active_products' => Product::query()->where('status', 'active')->count(),
            'active_courses' => Product::query()->where('type', 'course')->where('status', 'active')->count(),
            'active_packages' => Product::query()->where('type', 'course_package')->where('status', 'active')->count(),
            'total_orders' => Order::query()->where(function ($query): void {
                $query->whereIn('status', ['completed', 'cancelled'])
                    ->orWhereHas('payments', fn ($payments) => $payments->where('status', 'failed'));
            })->count(),
            'completed_orders' => Order::query()->where('status', 'completed')->count(),
            'cancelled_orders' => Order::query()->where('status', 'cancelled')->count(),
            'failed_orders' => Order::query()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereHas('payments', fn ($query) => $query->where('status', 'failed'))
                ->count(),
            'paid_payments' => $paidPayments,
            // Keep the original metric key during Phase 1 so existing admin
            // regression tests and any internal references remain compatible.
            'completed_payments' => $paidPayments,
            'failed_payments' => Payment::query()->where('payment_environment', 'live')->where('status', 'failed')->count(),
            'test_payments' => Payment::query()->where(function ($query): void {
                $query->where('provider', 'demo')->orWhere('payment_environment', 'test');
            })->count(),
            'live_provider_payments' => Payment::query()->where('payment_environment', 'live')->where('provider', '!=', 'demo')->count(),
            'enrollments' => Enrollment::query()->count(),
            'job_applications_new' => JobApplication::query()->where('status', 'new')->count(),
            'job_applications_reviewing' => JobApplication::query()->where('status', 'reviewing')->count(),
            'job_applications_shortlisted' => JobApplication::query()->where('status', 'shortlisted')->count(),
            'contact_leads_new' => Lead::query()->where('source', 'contact')->where('status', 'new')->count(),
            'consultations_requested' => ConsultationRequest::query()->where('status', 'requested')->count(),
            'consultations_booked' => ConsultationRequest::query()->where('status', 'booked')->count(),
        ];

        $revenueByCurrency = Payment::query()
            ->liveRevenue()
            ->select('currency', 'provider')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('COUNT(*) as payment_count')
            ->groupBy('currency', 'provider')
            ->orderBy('currency')
            ->orderBy('provider')
            ->get();

        $recentOrders = Order::query()
            ->where(function ($query): void {
                $query->whereIn('status', ['completed', 'cancelled'])
                    ->orWhereHas('payments', fn ($payments) => $payments->where('status', 'failed'));
            })
            ->select([
                'id', 'order_number', 'user_id', 'status', 'currency',
                'total_amount', 'placed_at', 'created_at',
            ])
            ->with('user:id,full_name,email,role,status')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $recentPayments = Payment::query()
            ->whereIn('status', ['paid', 'failed'])
            ->select([
                'id', 'order_id', 'provider', 'payment_environment', 'provider_transaction_id', 'amount',
                'currency', 'status', 'paid_at', 'created_at',
            ])
            ->with([
                'order:id,order_number,user_id,status',
                'order.user:id,full_name,email,status',
            ])
            ->latest('id')
            ->limit(6)
            ->get();

        $recentApplications = JobApplication::query()
            ->select(['id', 'job_position_id', 'full_name', 'status', 'created_at'])
            ->with('jobPosition:id,title')
            ->latest('id')
            ->limit(6)
            ->get();

        $recentConsultations = ConsultationRequest::query()
            ->select(['id', 'lead_id', 'status', 'consulting_type', 'requested_at', 'scheduled_at', 'created_at'])
            ->with('lead:id,full_name,company_name')
            ->latest('id')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact(
            'metrics',
            'revenueByCurrency',
            'recentOrders',
            'recentPayments',
            'recentApplications',
            'recentConsultations',
        ));
    }
}
