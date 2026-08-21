<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Services\Admin\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $logs = AdminAuditLog::query()
            ->select([
                'id', 'admin_user_id', 'action', 'entity_type', 'entity_id',
                'created_at', 'updated_at',
            ])
            ->with('admin:id,full_name,email,status')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('action', 'like', "%{$search}%")
                        ->orWhere('entity_type', 'like', "%{$search}%")
                        ->orWhere('entity_id', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->value()))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $actions = AdminAuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.audit-log.index', compact('logs', 'actions'));
    }

    public function show(AdminAuditLog $auditLog): View
    {
        $auditLog->load('admin:id,full_name,email,status');

        $beforeValues = $this->audit->sanitizeForDisplay($auditLog->before_values ?? []);
        $afterValues = $this->audit->sanitizeForDisplay($auditLog->after_values ?? []);

        return view('admin.audit-log.show', compact('auditLog', 'beforeValues', 'afterValues'));
    }
}
