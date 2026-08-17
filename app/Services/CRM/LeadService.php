<?php

namespace App\Services\CRM;

use App\Models\Lead;

class LeadService
{
    public function byEmail(string $email): ?Lead
    {
        return Lead::query()->where('email', $email)->latest()->first();
    }
}
