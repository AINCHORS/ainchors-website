<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Services\CRM\ConsultationBookingService;
use App\Http\Controllers\Modules\Consulting\GovernmentBookingController;
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

    public function test_direct_booking_access_without_a_selection_redirects_to_consulting(): void
    {
        $this->get(route('consulting.booking'))
            ->assertRedirect(route('consulting.main'));

        $this->get(route('consulting.booking', ['consulting_type' => 'government']))
            ->assertRedirect(route('consulting.main'))
            ->assertSessionMissing(GovernmentBookingController::SESSION_KEY);
    }

    public function test_legacy_booking_urls_redirect_to_the_clean_booking_route(): void
    {
        $this->get('/boooking-page')
            ->assertStatus(301)
            ->assertRedirect(route('consulting.main'));

        $this->get('/booking-page')
            ->assertStatus(301)
            ->assertRedirect(route('consulting.main'));

        $this->get('/consulting-gov/booking')
            ->assertStatus(301)
            ->assertRedirect(route('consulting.government'));

        $this->get('/consulting-private/booking')
            ->assertStatus(301)
            ->assertRedirect(route('consulting.private'));
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

    public function test_government_and_private_consulting_ctas_submit_a_validated_selection(): void
    {
        foreach (['consulting-gov' => 'government', 'consulting-private' => 'private'] as $path => $type) {
            $buttonId = $path === 'consulting-gov' ? 'button-wnCqCxJipE_btn' : 'button-q_7paJEOWF_btn';
            $this->get(route('legacy.embedded', ['path' => $path]))
                ->assertOk()
                ->assertSee(route('consulting.booking.select'), false)
                ->assertSee('href="'.route('consulting.booking').'"', false)
                ->assertSee('form.method="POST"', false)
                ->assertSee('input.name=entry[0]', false)
                ->assertSee('consultingType="'.$type.'"', false)
                ->assertSee('buttonId="'.$buttonId.'"', false)
                ->assertSee('isBookingCta', false)
                ->assertSee('if(link.getAttribute("href")!==bookingUrl)', false)
                ->assertSee('if(submitting){return}', false)
                ->assertDontSee('consulting-booking?consulting_type=', false)
                ->assertDontSee('location.hash', false);
        }
    }

    public function test_selection_posts_store_the_type_in_session_and_redirect_to_the_clean_url(): void
    {
        foreach (['government', 'private'] as $type) {
            $this->post(route('consulting.booking.select'), ['consulting_type' => $type])
                ->assertRedirect(route('consulting.booking'))
                ->assertSessionHas(GovernmentBookingController::SESSION_KEY, $type);

            $this->get(route('consulting.booking'))
                ->assertOk()
                ->assertSee($type === 'government' ? 'Government Consulting' : 'Private Consulting')
                ->assertSee('How can we help? *')
                ->assertSee("'Malaysia': { dial: '+60'", false)
                ->assertSee("phone.addEventListener('input'", false)
                ->assertDontSee('name="consulting_type"', false)
                ->assertDontSee('New Request');

            session()->forget(GovernmentBookingController::SESSION_KEY);
        }
    }

    public function test_booking_creates_a_lead_and_consultation_request(): void
    {
        $this->withSession([GovernmentBookingController::SESSION_KEY => 'government'])
            ->post(route('consulting.booking.store'), [
            'full_name' => 'Government Contact',
            'email' => 'government@example.com',
            'phone' => '+60 12 345 6789',
            'country' => 'Malaysia',
            'company_name' => 'Example Regulator',
            'requirements' => 'We need guidance for a public-sector AI programme.',
        ])->assertRedirect(route('consulting.booking'))
            ->assertSessionMissing(GovernmentBookingController::SESSION_KEY);

        $lead = Lead::query()->where('email', 'government@example.com')->firstOrFail();

        $this->assertSame('consulting_booking', $lead->source);
        $this->assertSame('new_request', $lead->status);
        $this->assertSame('Example Regulator', $lead->company_name);
        $this->assertSame('Malaysia', $lead->country);
        $this->assertSame('We need guidance for a public-sector AI programme.', $lead->notes);

        $this->assertDatabaseHas('consultation_requests', [
            'lead_id' => $lead->id,
            'status' => 'requested',
            'source_page' => ConsultationBookingService::BOOKING_SOURCE_PAGE,
            'consulting_type' => 'government',
        ]);
    }

    public function test_selection_rejects_an_unknown_consulting_type(): void
    {
        $this->from(route('consulting.government'))
            ->post(route('consulting.booking.select'), [
                'consulting_type' => 'legacy',
            ])
            ->assertRedirect(route('consulting.government'))
            ->assertSessionHasErrors(['consulting_type'])
            ->assertSessionMissing(GovernmentBookingController::SESSION_KEY);

        $this->assertDatabaseCount('consultation_requests', 0);
    }

    public function test_booking_rejects_a_missing_consulting_type(): void
    {
        $this->from(route('consulting.booking'))
            ->post(route('consulting.booking.store'), [
                'full_name' => 'Unclassified Contact',
                'email' => 'unclassified@example.com',
                'phone' => '+60 12 000 0001',
            ])
            ->assertRedirect(route('consulting.booking'))
            ->assertSessionHasErrorsIn('booking', ['consulting_type']);

        $this->assertDatabaseCount('consultation_requests', 0);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_booking_validation_preserves_the_selected_type(): void
    {
        $this->withSession([GovernmentBookingController::SESSION_KEY => 'private'])
            ->from(route('consulting.booking'))
            ->post(route('consulting.booking.store'), [
                'full_name' => '',
                'email' => 'not-an-email',
                'phone' => '',
                'country' => '',
                'requirements' => '',
            ])
            ->assertRedirect(route('consulting.booking'))
            ->assertSessionHasErrorsIn('booking', ['full_name', 'email', 'phone', 'country', 'requirements'])
            ->assertSessionHas(GovernmentBookingController::SESSION_KEY, 'private');

        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('consultation_requests', 0);
    }

    public function test_private_request_persists_private_consulting_type(): void
    {
        $this->withSession([GovernmentBookingController::SESSION_KEY => 'private'])
            ->post(route('consulting.booking.store'), [
                'full_name' => 'Private Client',
                'email' => 'private@example.com',
                'phone' => '+60 12 111 2222',
                'country' => 'Malaysia',
                'company_name' => 'Private Company',
                'requirements' => 'We need a private-sector strategy review.',
            ])
            ->assertRedirect(route('consulting.booking'));

        $lead = Lead::query()->where('email', 'private@example.com')->firstOrFail();
        $this->assertSame('new_request', $lead->status);
        $this->assertDatabaseHas('consultation_requests', [
            'lead_id' => $lead->id,
            'consulting_type' => 'private',
        ]);
    }

    public function test_booking_validates_email_and_phone_for_the_selected_country(): void
    {
        $this->withSession([GovernmentBookingController::SESSION_KEY => 'government'])
            ->from(route('consulting.booking'))
            ->post(route('consulting.booking.store'), [
                'full_name' => 'Invalid Contact',
                'email' => 'invalid-email',
                'phone' => '+61 123',
                'country' => 'Australia',
                'requirements' => 'Validation test.',
            ])
            ->assertRedirect(route('consulting.booking'))
            ->assertSessionHasErrorsIn('booking', ['email', 'phone']);

        $this->assertDatabaseCount('leads', 0);
    }
}
