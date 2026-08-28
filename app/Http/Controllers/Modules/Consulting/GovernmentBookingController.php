<?php

namespace App\Http\Controllers\Modules\Consulting;

use App\Http\Controllers\Controller;
use App\Services\CRM\ConsultationBookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GovernmentBookingController extends Controller
{
    public function create(): View
    {
        return view('modules.consulting.government.booking');
    }

    public function store(Request $request, ConsultationBookingService $bookings): RedirectResponse
    {
        $validated = $request->validateWithBag('booking', [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $bookings->storeGovernmentBooking($validated, $request->user());

        return to_route('consulting.booking')
            ->with('booking_success', 'Thank you. Your booking request has been received.');
    }
}
