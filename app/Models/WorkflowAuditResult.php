<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAuditResult extends Model
{
    protected $fillable = [
        'workflow_audit_id', 'automation_score', 'potential_level',
        'current_monthly_labour_cost', 'estimated_reduction_low', 'estimated_reduction_high',
        'estimated_hours_saved_low', 'estimated_hours_saved_high', 'monthly_value_low',
        'monthly_value_high', 'annual_value_low', 'annual_value_high', 'bottlenecks',
        'ai_operator_opportunities', 'future_state_workflow', 'recommended_next_step',
        'implementation_direction', 'verified_workflow_map', 'system_integration_review',
        'risk_analysis', 'data_assessment', 'detailed_roi', 'solution_design',
        'implementation_scope', 'assumptions', 'ai_analysis', 'ai_provider', 'ai_model',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'automation_score' => 'decimal:2', 'current_monthly_labour_cost' => 'decimal:2',
            'estimated_reduction_low' => 'decimal:2', 'estimated_reduction_high' => 'decimal:2',
            'estimated_hours_saved_low' => 'decimal:2', 'estimated_hours_saved_high' => 'decimal:2',
            'monthly_value_low' => 'decimal:2', 'monthly_value_high' => 'decimal:2',
            'annual_value_low' => 'decimal:2', 'annual_value_high' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function workflowAudit(): BelongsTo { return $this->belongsTo(WorkflowAudit::class); }
}
