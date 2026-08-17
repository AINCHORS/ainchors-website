<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'user_id', 'visitor_id', 'workflow_audit_id', 'source', 'full_name',
        'email', 'phone', 'company_name', 'status', 'assigned_admin_id', 'notes',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function visitor(): BelongsTo { return $this->belongsTo(Visitor::class); }
    public function workflowAudit(): BelongsTo { return $this->belongsTo(WorkflowAudit::class); }
    public function assignedAdmin(): BelongsTo { return $this->belongsTo(User::class, 'assigned_admin_id'); }
    public function consultationRequests(): HasMany { return $this->hasMany(ConsultationRequest::class); }
    public function serviceEngagements(): HasMany { return $this->hasMany(ServiceEngagement::class); }
}
