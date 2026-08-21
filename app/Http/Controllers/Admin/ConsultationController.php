<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\Admin\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    private const STATUSES = ['requested', 'booked', 'completed', 'cancelled', 'no_show'];

    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $consultations = ConsultationRequest::query()
            ->select([
                'id', 'lead_id', 'user_id', 'assigned_admin_id', 'status',
                'requested_at', 'scheduled_at', 'source_page', 'created_at', 'updated_at',
            ])
            ->with([
                'lead:id,full_name,email,phone,company_name,status,source',
                'assignedAdmin:id,full_name,email,status',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('source_page', 'like', "%{$search}%")
                        ->orWhereHas('lead', function ($leadQuery) use ($search): void {
                            $leadQuery
                                ->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->orderByRaw('scheduled_at IS NULL')
            ->orderBy('scheduled_at')
            ->orderByDesc('requested_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.consultations.index', [
            'consultations' => $consultations,
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(ConsultationRequest $consultation): View
    {
        $consultation = ConsultationRequest::query()
            ->select([
                'id', 'lead_id', 'workflow_audit_id', 'user_id', 'assigned_admin_id',
                'status', 'requested_at', 'scheduled_at', 'source_page', 'notes',
                'created_at', 'updated_at',
            ])
            ->with([
                'lead:id,full_name,email,phone,company_name,status,source,assigned_admin_id',
                'user:id,full_name,email,status',
                'assignedAdmin:id,full_name,email,status',
            ])
            ->findOrFail($consultation->getKey());

        return view('admin.consultations.show', [
            'consultation' => $consultation,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, ConsultationRequest $consultation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'assigned_to_me' => ['nullable', 'boolean'],
        ]);

        if ($data['status'] === 'booked' && blank($data['scheduled_at'] ?? null)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'A booked consultation must have a scheduled date and time.',
            ]);
        }

        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($admin, $consultation, $data, $request): void {
            $before = $this->auditData($consultation);

            $consultation->fill([
                'status' => $data['status'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'assigned_admin_id' => $request->boolean('assigned_to_me') ? $admin->id : null,
            ]);

            if ($consultation->isDirty()) {
                $consultation->save();
                $this->audit->record(
                    $admin,
                    'CONSULTATION_UPDATED',
                    $consultation,
                    $before,
                    $this->auditData($consultation),
                );
            }

            $this->syncLeadStage($admin, $consultation->lead, $data['status']);
        });

        return redirect()->route('admin.consultations.show', $consultation)
            ->with('success', 'Consultation updated.');
    }

    private function syncLeadStage(User $admin, ?Lead $lead, string $consultationStatus): void
    {
        if (! $lead) {
            return;
        }

        $target = match ($consultationStatus) {
            'requested' => 'consultation_requested',
            'booked' => 'consultation_booked',
            default => null,
        };

        if ($target === null || $lead->status === $target) {
            return;
        }

        $before = [
            'id' => $lead->id,
            'source' => $lead->source,
            'status' => $lead->status,
            'assigned_admin_id' => $lead->assigned_admin_id,
        ];

        $lead->forceFill(['status' => $target])->save();

        $this->audit->record($admin, 'LEAD_STATUS_CHANGED', $lead, $before, [
            'id' => $lead->id,
            'source' => $lead->source,
            'status' => $lead->status,
            'assigned_admin_id' => $lead->assigned_admin_id,
        ]);
    }

    /** @return array<string, int|string|bool|null> */
    private function auditData(ConsultationRequest $consultation): array
    {
        return [
            'id' => $consultation->id,
            'lead_id' => $consultation->lead_id,
            'user_id' => $consultation->user_id,
            'assigned_admin_id' => $consultation->assigned_admin_id,
            'status' => $consultation->status,
            'requested_at' => $consultation->requested_at?->toAtomString(),
            'scheduled_at' => $consultation->scheduled_at?->toAtomString(),
            'source_page' => $consultation->source_page,
            'notes_present' => filled($consultation->notes),
        ];
    }
}
