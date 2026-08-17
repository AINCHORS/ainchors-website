<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationRequest extends Model
{
    protected $fillable = [
        'lead_id', 'workflow_audit_id', 'user_id', 'assigned_admin_id', 'status',
        'requested_at', 'scheduled_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'scheduled_at' => 'datetime'];
    }

    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function workflowAudit(): BelongsTo { return $this->belongsTo(WorkflowAudit::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function assignedAdmin(): BelongsTo { return $this->belongsTo(User::class, 'assigned_admin_id'); }
}
