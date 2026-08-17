<?php

namespace App\Services\Analytics;

use App\Models\ActivityEvent;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Collection;

class ActivityTrackingService
{
    /** @return Collection<int, ActivityEvent> */
    public function recentFor(Visitor $visitor, int $limit = 50): Collection
    {
        return $visitor->activityEvents()
            ->with('visitorSession')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
