<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkflowAudit extends Model
{
    protected $fillable = [
        'audit_uuid', 'user_id', 'visitor_id', 'audit_type', 'parent_audit_id',
        'order_id', 'company_name', 'industry', 'company_size', 'workflow_name',
        'department', 'workflow_description', 'status', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function visitor(): BelongsTo { return $this->belongsTo(Visitor::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function parentAudit(): BelongsTo { return $this->belongsTo(self::class, 'parent_audit_id'); }
    public function childAudits(): HasMany { return $this->hasMany(self::class, 'parent_audit_id'); }
    public function answers(): HasMany { return $this->hasMany(WorkflowAuditAnswer::class); }
    public function result(): HasOne { return $this->hasOne(WorkflowAuditResult::class); }
    public function leads(): HasMany { return $this->hasMany(Lead::class); }
    public function consultationRequests(): HasMany { return $this->hasMany(ConsultationRequest::class); }
    public function serviceEngagements(): HasMany { return $this->hasMany(ServiceEngagement::class); }
}
