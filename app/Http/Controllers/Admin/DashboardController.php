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
        $paidPayments = Payment::query()->where('status', 'paid')->count();

        $metrics = [
            'total_users' => User::query()->count(),
            'new_users' => User::query()->where('created_at', '>=', now()->subDays(30))->count(),
            'active_products' => Product::query()->where('status', 'active')->count(),
            'active_courses' => Product::query()->where('type', 'course')->where('status', 'active')->count(),
            'active_packages' => Product::query()->where('type', 'course_package')->where('status', 'active')->count(),
            'total_orders' => Order::query()->count(),
            'awaiting_payment_orders' => Order::query()->whereIn('status', ['pending', 'awaiting_payment'])->count(),
            'completed_orders' => Order::query()->where('status', 'completed')->count(),
            'paid_payments' => $paidPayments,
            // Keep the original metric key during Phase 1 so existing admin
            // regression tests and any internal references remain compatible.
            'completed_payments' => $paidPayments,
            'pending_payments' => Payment::query()->whereIn('status', ['pending', 'processing'])->count(),
            'failed_payments' => Payment::query()->where('status', 'failed')->count(),
            'test_payments' => Payment::query()->where('provider', 'demo')->count(),
            'non_demo_payments' => Payment::query()->where('provider', '!=', 'demo')->count(),
            'enrollments' => Enrollment::query()->count(),
            'job_applications_new' => JobApplication::query()->where('status', 'new')->count(),
            'job_applications_reviewing' => JobApplication::query()->where('status', 'reviewing')->count(),
            'job_applications_shortlisted' => JobApplication::query()->where('status', 'shortlisted')->count(),
            'contact_leads_new' => Lead::query()->where('source', 'contact')->where('status', 'new')->count(),
            'consultations_requested' => ConsultationRequest::query()->where('status', 'requested')->count(),
            'consultations_booked' => ConsultationRequest::query()->where('status', 'booked')->count(),
        ];

        // Do not combine currencies. Demo rows are explicitly test data. For
        // non-demo providers Phase 1 does not claim a live/test environment,
        // because first-class payment environments are added in Phase 2.
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
            ->with('user:id,full_name,email,role,status')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $recentPayments = Payment::query()
            ->select([
                'id', 'order_id', 'provider', 'provider_transaction_id', 'amount',
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
            ->select(['id', 'lead_id', 'status', 'requested_at', 'scheduled_at', 'created_at'])
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
