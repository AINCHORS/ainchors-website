<?php

namespace App\Services\CRM;

use App\Models\ConsultationRequest;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Collection;

class ConsultationService
{
    /** @return Collection<int, ConsultationRequest> */
    public function requestsFor(Lead $lead): Collection
    {
        return $lead->consultationRequests()
            ->with(['workflowAudit', 'assignedAdmin', 'user'])
            ->latest('requested_at')
            ->get();
    }
}
