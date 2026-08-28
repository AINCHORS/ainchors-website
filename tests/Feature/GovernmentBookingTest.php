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
        $this->get(route('consulting.booking'))
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
            ->assertRedirect(route('consulting.booking'));

        $this->get('/booking-page')
            ->assertStatus(301)
            ->assertRedirect(route('consulting.booking'));

        $this->get('/consulting-gov/booking')
            ->assertStatus(301)
            ->assertRedirect(route('consulting.booking'));

        $this->get('/consulting-private/booking')
            ->assertStatus(301)
            ->assertRedirect(route('consulting.booking'));
    }

    public function test_consulting_main_book_now_cta_opens_whatsapp_in_a_new_tab(): void
    {
        $this->get(route('legacy.embedded', ['path' => 'consulting-main']))
            ->assertOk()
            ->assertSee('https://wa.me/61418802086', false)
            ->assertSee('target="_blank" rel="noopener noreferrer"', false)
            ->assertSee('if(link.getAttribute("href")!==whatsappUrl)', false)
            ->assertSee('if(link.getAttribute("target")!=="_blank")', false)
            ->assertSee('if(link.getAttribute("rel")!=="noopener noreferrer")', false)
            ->assertSee('window.open(whatsappUrl,"_blank","noopener,noreferrer")', false)
            ->assertDontSee('window.parent.location.assign(bookingUrl)', false);
    }

    public function test_government_and_private_consulting_ctas_share_the_booking_page(): void
    {
        foreach (['consulting-gov', 'consulting-private'] as $path) {
            $this->get(route('legacy.embedded', ['path' => $path]))
                ->assertOk()
                ->assertSee(route('consulting.booking'), false)
                ->assertSee('window.parent.location.assign(bookingUrl)', false);
        }
    }

    public function test_booking_creates_a_lead_and_consultation_request(): void
    {
        $this->post(route('consulting.booking.store'), [
            'full_name' => 'Government Contact',
            'email' => 'government@example.com',
            'phone' => '+60 12 345 6789',
            'company_name' => 'Example Regulator',
        ])->assertRedirect(route('consulting.booking'));

        $lead = Lead::query()->where('email', 'government@example.com')->firstOrFail();

        $this->assertSame('consulting_booking', $lead->source);
        $this->assertSame('consultation_requested', $lead->status);
        $this->assertSame('Example Regulator', $lead->company_name);

        $this->assertDatabaseHas('consultation_requests', [
            'lead_id' => $lead->id,
            'status' => 'requested',
            'source_page' => ConsultationBookingService::BOOKING_SOURCE_PAGE,
        ]);
    }

    public function test_booking_requires_the_legacy_required_fields(): void
    {
        $this->from(route('consulting.booking'))
            ->post(route('consulting.booking.store'), [
                'full_name' => '',
                'email' => 'not-an-email',
                'phone' => '',
            ])
            ->assertRedirect(route('consulting.booking'))
            ->assertSessionHasErrorsIn('booking', ['full_name', 'email', 'phone']);

        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('consultation_requests', 0);
    }
}
