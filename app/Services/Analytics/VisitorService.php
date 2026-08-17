<?php

namespace App\Services\Analytics;

use App\Models\Visitor;

class VisitorService
{
    public function find(string $visitorUuid): ?Visitor
    {
        return Visitor::query()->where('visitor_uuid', $visitorUuid)->first();
    }
}
