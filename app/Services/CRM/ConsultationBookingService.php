<?php

namespace App\Services\CRM;

use App\Models\ConsultationRequest;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConsultationBookingService
{
    public const BOOKING_SOURCE_PAGE = '/consulting-booking';

    /**
     * @param array{full_name: string, email: string, phone: string, company_name?: string|null} $submission
     */
    public function storeGovernmentBooking(array $submission, ?User $user = null): ConsultationRequest
    {
        return DB::transaction(function () use ($submission, $user): ConsultationRequest {
            $lead = Lead::query()->create([
                'user_id' => $user?->id,
                'source' => 'consulting_booking',
                'full_name' => $submission['full_name'],
                'email' => $submission['email'],
                'phone' => $submission['phone'],
                'company_name' => $submission['company_name'] ?? null,
                'status' => 'consultation_requested',
            ]);

            return ConsultationRequest::query()->create([
                'lead_id' => $lead->id,
                'user_id' => $user?->id,
                'status' => 'requested',
                'requested_at' => now(),
                'source_page' => self::BOOKING_SOURCE_PAGE,
            ]);
        });
    }
}
