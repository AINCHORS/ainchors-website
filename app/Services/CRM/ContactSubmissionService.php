<?php

namespace App\Services\CRM;

use App\Models\Lead;
use App\Models\User;

class ContactSubmissionService
{
    /** @param array{full_name: string, email: string, phone?: string|null, country?: string|null, message?: string|null, source?: string|null} $submission */
    public function store(array $submission, ?User $user = null): Lead
    {
        $notes = collect([
            filled($submission['country'] ?? null) ? 'Country: '.$submission['country'] : null,
            filled($submission['message'] ?? null) ? 'Message: '.$submission['message'] : null,
        ])->filter()->implode("\n");

        return Lead::query()->create([
            'user_id' => $user?->id,
            'source' => 'contact',
            'full_name' => $submission['full_name'],
            'email' => $submission['email'],
            'phone' => $submission['phone'] ?? null,
            'status' => 'new',
            'notes' => $notes ?: null,
        ]);
    }
}
