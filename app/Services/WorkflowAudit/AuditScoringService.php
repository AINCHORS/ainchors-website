<?php

namespace App\Services\WorkflowAudit;

use App\Models\WorkflowAudit;

class AuditScoringService
{
    public function scoreFor(WorkflowAudit $audit): ?float
    {
        return $audit->result?->automation_score === null
            ? null
            : (float) $audit->result->automation_score;
    }
}
