<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobApplicationStatusHistory;
use App\Models\JobPosition;
use App\Models\User;
use App\Services\Admin\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobApplicationController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $applications = JobApplication::query()
            ->with(['jobPosition:id,title', 'reviewedBy:id,full_name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('jobPosition', fn ($positionQuery) => $positionQuery->where('title', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->when($request->filled('position_id'), fn ($query) => $query->where('job_position_id', (int) $request->input('position_id')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $positions = JobPosition::query()->orderBy('sort_order')->orderBy('title')->get(['id', 'title']);

        return view('admin.job-applications.index', compact('applications', 'positions'));
    }

    public function show(JobApplication $jobApplication): View
    {
        $jobApplication->load([
            'jobPosition:id,title',
            'reviewedBy:id,full_name',
            'statusHistory' => fn ($query) => $query->with('changedBy:id,full_name')->latest(),
        ]);

        return view('admin.job-applications.show', compact('jobApplication'));
    }

    public function update(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'reviewing', 'shortlisted', 'rejected'])],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        /** @var User $admin */
        $admin = $request->user();
        $before = $this->auditData($jobApplication);
        $statusChanged = $jobApplication->status !== $data['status'];

        DB::transaction(function () use ($admin, $jobApplication, $data, $statusChanged, $before): void {
            $jobApplication->forceFill([
                'status' => $data['status'],
                'internal_notes' => $data['internal_notes'] ?? null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'status_changed_at' => $statusChanged ? now() : $jobApplication->status_changed_at,
            ])->save();

            if ($statusChanged) {
                JobApplicationStatusHistory::query()->create([
                    'job_application_id' => $jobApplication->id,
                    'status' => $jobApplication->status,
                    'changed_by' => $admin->id,
                ]);
            }

            $this->audit->record(
                $admin,
                $statusChanged ? 'JOB_APPLICATION_STATUS_UPDATED' : 'JOB_APPLICATION_REVIEWED',
                $jobApplication,
                $before,
                $this->auditData($jobApplication),
            );
        });

        return to_route('admin.job-applications.show', $jobApplication)
            ->with('success', $statusChanged ? 'Application status updated.' : 'Application review notes saved.');
    }

    public function resume(JobApplication $jobApplication): StreamedResponse
    {
        $disk = Storage::disk($jobApplication->resume_disk);

        abort_unless($disk->exists($jobApplication->resume_path), 404);

        return $disk->download($jobApplication->resume_path, $jobApplication->resume_original_name);
    }

    /** @return array<string, int|string|null> */
    private function auditData(JobApplication $application): array
    {
        return [
            'id' => $application->id,
            'job_position_id' => $application->job_position_id,
            'status' => $application->status,
            'reviewed_by' => $application->reviewed_by,
            'reviewed_at' => $application->reviewed_at?->toAtomString(),
            'status_changed_at' => $application->status_changed_at?->toAtomString(),
        ];
    }
}
