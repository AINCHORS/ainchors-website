<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Services\Admin\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    private const CONSULTING_STATUSES = ['new_request', 'contacted', 'consultation_scheduled', 'closed'];

    private const CONTACT_STATUSES = ['new', 'contacted', 'qualified', 'consultation_requested', 'consultation_booked', 'proposal', 'won', 'lost'];

    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $leads = Lead::query()
            ->select([
                'id', 'user_id', 'source', 'full_name', 'email', 'phone',
                'company_name', 'status', 'assigned_admin_id', 'created_at',
                'updated_at',
            ])
            ->with([
                'user:id,full_name,email,status',
                'assignedAdmin:id,full_name,email,status',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->when($request->filled('source'), fn ($query) => $query->where('source', $request->string('source')->value()))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.leads.index-refined', [
            'leads' => $leads,
            'consultingStatuses' => self::CONSULTING_STATUSES,
            'contactStatuses' => self::CONTACT_STATUSES,
        ]);
    }

    public function show(Lead $lead): View
    {
        $lead = Lead::query()
            ->select([
                'id', 'user_id', 'visitor_id', 'workflow_audit_id', 'source',
                'full_name', 'email', 'phone', 'company_name', 'status',
                'assigned_admin_id', 'notes', 'created_at', 'updated_at',
            ])
            ->with([
                'user:id,full_name,email,status',
                'assignedAdmin:id,full_name,email,status',
                'consultationRequests' => fn ($query) => $query
                    ->select(['id', 'lead_id', 'consulting_type', 'status', 'requested_at'])
                    ->latest('id'),
            ])
            ->findOrFail($lead->getKey());

        return view($lead->source === 'consulting_booking' ? 'admin.leads.show-consulting' : 'admin.leads.show', [
            'lead' => $lead,
            'statuses' => $this->statusesFor($lead),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in($this->statusesFor($lead)),
            ],
        ]);

        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $lead, $data): void {
            $before = $this->auditData($lead);

            if ($lead->status === $data['status']) {
                return;
            }

            $lead->forceFill(['status' => $data['status']])->save();
            $this->audit->record($admin, 'LEAD_STATUS_CHANGED', $lead, $before, $this->auditData($lead));
        });

        return redirect()->route('admin.leads.show', $lead)
            ->with('success', 'Contact submission status updated.');
    }

    /** @return array<string, int|string|null> */
    private function auditData(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'source' => $lead->source,
            'status' => $lead->status,
            'assigned_admin_id' => $lead->assigned_admin_id,
        ];
    }

    /** @return list<string> */
    private function statusesFor(Lead $lead): array
    {
        return $lead->source === 'consulting_booking'
            ? self::CONSULTING_STATUSES
            : self::CONTACT_STATUSES;
    }
}
