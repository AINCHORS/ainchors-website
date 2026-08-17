<?php

namespace App\Services\WorkflowAudit;

use App\Models\WorkflowAudit;

class RoiCalculatorService
{
    /** @return array{monthly_low: ?string, monthly_high: ?string, annual_low: ?string, annual_high: ?string} */
    public function valuesFor(WorkflowAudit $audit): array
    {
        $result = $audit->result;

        return [
            'monthly_low' => $result?->monthly_value_low,
            'monthly_high' => $result?->monthly_value_high,
            'annual_low' => $result?->annual_value_low,
            'annual_high' => $result?->annual_value_high,
        ];
    }
}
