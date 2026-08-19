<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->artisan('ainchors:populate-legacy-course-catalogue')->assertExitCode(0);
    }

    public function test_consulting_and_training_navigation_expose_the_required_hierarchy(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="consulting-menu"', false)
            ->assertSee('Consulting Introduction')
            ->assertSee('Public / Government Sector')
            ->assertSee('Private Sector')
            ->assertSee('id="mobile-consulting-menu"', false)
            ->assertSee('id="mobile-training-menu"', false)
            ->assertSee(route('register'));
    }

    public function test_contact_submission_is_validated_and_stored_as_a_real_lead(): void
    {
        $this->post(route('contact.submit'), [
            'source' => 'footer',
            'full_name' => 'Ain Chors',
            'email' => 'contact@example.com',
            'phone' => '+60 12 345 6789',
            'country' => 'MY',
            'message' => 'Please contact me about training.',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'source' => 'contact',
            'full_name' => 'Ain Chors',
            'email' => 'contact@example.com',
            'phone' => '+60 12 345 6789',
            'status' => 'new',
        ]);

        $this->from(route('home'))->post(route('contact.submit'), [
            'source' => 'footer',
            'full_name' => '',
            'email' => 'not-an-email',
        ])->assertRedirect(route('home'))->assertSessionHasErrorsIn('contact', ['full_name', 'email']);
    }

    public function test_welcome_modal_respects_exclusions_and_session_once_configuration(): void
    {
        $this->get(route('login'))->assertDontSee('Welcome to AINCHORS');

        SiteSetting::query()->create(['key' => 'welcome_modal_frequency', 'value' => 'session_once']);
        $this->get(route('home'))->assertSee('Welcome to AINCHORS');
        $this->get(route('courses.index'))->assertDontSee('Welcome to AINCHORS');
    }
}
