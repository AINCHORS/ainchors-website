<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Services\CRM\ConsultationBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GovernmentBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_clean_booking_route_uses_the_shared_public_shell(): void
    {
        $this->get(route('consulting.government.booking'))
            ->assertOk()
            ->assertSee('Fill out your details below so we can confirm your booking!')
            ->assertSee('Full Name *')
            ->assertSee('Company Name (If applicable)')
            ->assertSee('AINCHORS Training &amp; Consulting', false)
            ->assertDontSee('boooking-page');
    }

    public function test_legacy_booking_urls_redirect_to_the_clean_booking_route(): void
    {
        $this->get('/boooking-page')
            ->assertStatus(301)
            ->assertRedirect(route('consulting.government.booking'));

        $this->get('/booking-page')
            ->assertStatus(301)
            ->assertRedirect(route('consulting.government.booking'));
    }

    public function test_consulting_main_book_now_cta_uses_the_local_booking_page(): void
    {
        $this->get(route('legacy.embedded', ['path' => 'consulting-main']))
            ->assertOk()
            ->assertSee(route('consulting.government.booking'), false)
            ->assertSee('aria-label="Book Now " target="_parent"', false)
            ->assertSee('window.parent.location.assign(bookingUrl)', false);
    }

    public function test_booking_creates_a_lead_and_consultation_request(): void
    {
        $this->post(route('consulting.government.booking.store'), [
            'full_name' => 'Government Contact',
            'email' => 'government@example.com',
            'phone' => '+60 12 345 6789',
            'company_name' => 'Example Regulator',
        ])->assertRedirect(route('consulting.government.booking'));

        $lead = Lead::query()->where('email', 'government@example.com')->firstOrFail();

        $this->assertSame('consulting_booking', $lead->source);
        $this->assertSame('consultation_requested', $lead->status);
        $this->assertSame('Example Regulator', $lead->company_name);

        $this->assertDatabaseHas('consultation_requests', [
            'lead_id' => $lead->id,
            'status' => 'requested',
            'source_page' => ConsultationBookingService::GOVERNMENT_SOURCE_PAGE,
        ]);
    }

    public function test_booking_requires_the_legacy_required_fields(): void
    {
        $this->from(route('consulting.government.booking'))
            ->post(route('consulting.government.booking.store'), [
                'full_name' => '',
                'email' => 'not-an-email',
                'phone' => '',
            ])
            ->assertRedirect(route('consulting.government.booking'))
            ->assertSessionHasErrorsIn('booking', ['full_name', 'email', 'phone']);

        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('consultation_requests', 0);
    }
}
