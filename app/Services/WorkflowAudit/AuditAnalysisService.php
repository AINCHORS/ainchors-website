<?php

namespace App\Services\WorkflowAudit;

use App\Models\WorkflowAudit;

class AuditAnalysisService
{
    public function detailed(WorkflowAudit $audit): WorkflowAudit
    {
        return $audit->load(['answers', 'result', 'visitor', 'user', 'order']);
    }
}
