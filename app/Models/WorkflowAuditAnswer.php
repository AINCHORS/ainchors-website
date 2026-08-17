<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAuditAnswer extends Model
{
    protected $fillable = [
        'workflow_audit_id', 'question_key', 'answer_text', 'answer_number', 'answer_json',
    ];

    protected function casts(): array
    {
        return ['answer_number' => 'decimal:4', 'answer_json' => 'array'];
    }

    public function workflowAudit(): BelongsTo { return $this->belongsTo(WorkflowAudit::class); }
}
