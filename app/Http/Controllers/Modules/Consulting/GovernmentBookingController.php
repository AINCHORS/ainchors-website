<?php

namespace App\Http\Controllers\Modules\Consulting;

use App\Http\Controllers\Controller;
use App\Rules\PhoneForCountry;
use App\Services\CRM\ConsultationBookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GovernmentBookingController extends Controller
{
    public const SESSION_KEY = 'consulting_type';

    private const TYPES = ['government', 'private'];

    private const COUNTRIES = [
        'Australia', 'Canada', 'China', 'Hong Kong', 'Japan', 'Malaysia',
        'New Zealand', 'Singapore', 'United Kingdom', 'United States', 'Other',
    ];

    public function select(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'consulting_type' => ['required', Rule::in(self::TYPES)],
        ]);

        $request->session()->put(self::SESSION_KEY, $validated['consulting_type']);

        return to_route('consulting.booking');
    }

    public function create(Request $request): View|RedirectResponse
    {
        $consultingType = $request->session()->get(self::SESSION_KEY);

        if (! in_array($consultingType, self::TYPES, true)) {
            if ($request->session()->has('booking_success')) {
                return view('modules.consulting.government.booking', [
                    'consultingType' => null,
                    'bookingComplete' => true,
                    'countries' => self::COUNTRIES,
                ]);
            }

            $request->session()->forget(self::SESSION_KEY);

            return to_route('consulting.main');
        }

        return view('modules.consulting.government.booking', [
            'consultingType' => $consultingType,
            'bookingComplete' => false,
            'countries' => self::COUNTRIES,
        ]);
    }

    public function store(Request $request, ConsultationBookingService $bookings): RedirectResponse
    {
        $consultingType = $request->session()->get(self::SESSION_KEY);

        if (! in_array($consultingType, self::TYPES, true)) {
            $request->session()->forget(self::SESSION_KEY);

            throw ValidationException::withMessages([
                'consulting_type' => 'Please choose Government or Private Consulting before submitting a request.',
            ])->errorBag('booking');
        }

        $validated = $request->validateWithBag('booking', [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'country' => ['required', 'string', Rule::in(self::COUNTRIES)],
            'phone' => ['required', 'string', 'max:50', new PhoneForCountry((string) $request->input('country'))],
            'company_name' => ['nullable', 'string', 'max:255'],
            'requirements' => ['required', 'string', 'max:5000'],
        ]);

        $bookings->storeGovernmentBooking([
            ...$validated,
            'consulting_type' => $consultingType,
        ], $request->user());

        $request->session()->forget(self::SESSION_KEY);

        return to_route('consulting.booking')
            ->with('booking_success', 'Thank you. Your consultation request has been received.');
    }
}
