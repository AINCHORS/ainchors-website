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

    public function test_contact_feedback_type_is_selectable_prefillable_and_stored(): void
    {
        $this->get(route('contact', ['type' => 'training']))
            ->assertOk()
            ->assertSee('Choose an enquiry type')
            ->assertSee('Training Enquiry')
            ->assertSee('value="training_enquiry" selected', false);

        $this->get(route('legacy.embedded', ['path' => 'success-story-of-angie']))
            ->assertOk()
            ->assertSee('/contact-us?type=training', false);

        $this->postJson(route('contact.submit'), [
            'source' => 'contact_page',
            'feedback_type' => 'training_enquiry',
            'full_name' => 'Training Enquirer',
            'email' => 'training@example.com',
            'phone' => '+60 12 345 6789',
            'message' => 'Please contact me about corporate training.',
        ])->assertCreated();

        $this->assertDatabaseHas('leads', [
            'source' => 'contact',
            'email' => 'training@example.com',
            'notes' => "Feedback type: Training Enquiry\nMessage: Please contact me about corporate training.",
        ]);

        $this->postJson(route('contact.submit'), [
            'source' => 'contact_page',
            'full_name' => 'Missing Fields',
            'email' => 'missing@example.com',
        ])->assertUnprocessable()->assertJsonValidationErrors(['feedback_type', 'message']);
    }

    public function test_welcome_modal_respects_exclusions_and_session_once_configuration(): void
    {
        $this->get(route('login'))->assertDontSee('Welcome to AINCHORS');

        SiteSetting::query()->create(['key' => 'welcome_modal_frequency', 'value' => 'session_once']);
        $this->get(route('home'))->assertSee('Welcome to AINCHORS');
        $this->get(route('courses.index'))->assertDontSee('Welcome to AINCHORS');
    }

    public function test_public_shell_uses_the_frozen_footer_and_one_fixed_ai_assistant_surface(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Locations')
            ->assertSee('Begin Your Journey Today!')
            ->assertDontSee('footer-full-name')
            ->assertSee('id="ai-assistant-panel"', false)
            ->assertSee('aria-label="Open AI Assistant"', false)
            ->assertSee('Ask AINCHORS something...');
    }

    public function test_legacy_pages_delegate_vertical_scrolling_to_the_parent_document(): void
    {
        $this->get(route('trainers'))
            ->assertOk()
            ->assertSee('scrolling="no"', false);

        $this->get(route('legacy.embedded', ['path' => 'trainers-profile']))
            ->assertOk()
            ->assertSee('overflow-y:hidden!important', false)
            ->assertSee('resizeObserver.observe(document.documentElement)', false)
            ->assertSee('resizeObserver.observe(document.body)', false)
            ->assertSee('Math.abs(height-lastHeight)<2', false)
            ->assertSee('mutationObserver.observe(document.body', false);
    }

    public function test_angie_foong_uses_a_dedicated_local_founder_background_page(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee(route('angie-foong'), false)
            ->assertDontSee('https://angiefoong.com/founders', false);

        $this->get(route('angie-foong'))
            ->assertOk()
            ->assertSee("Founder's Background")
            ->assertSee('Angie Foong')
            ->assertSee('HRD Corp Certified Trainer')
            ->assertSee('translateX(-${index * 100}%)', false)
            ->assertSee("window.scrollTo({ top: 0, behavior: 'smooth' })", false)
            ->assertDontSee('href="#angie-foong-top"', false)
            ->assertDontSee('href="#top"', false)
            ->assertDontSee('https://angiefoong.com/founders', false);

        $this->get(route('legacy.embedded', ['path' => 'trainers-profile']))
            ->assertOk()
            ->assertSee(route('angie-foong'), false)
            ->assertDontSee('https://angiefoong.com/founders', false);
    }

    public function test_fondy_foong_has_a_local_profile_and_matching_profile_actions(): void
    {
        $this->get(route('trainers'))
            ->assertOk();

        $this->get(route('legacy.embedded', ['path' => 'trainers-profile']))
            ->assertOk()
            ->assertSee(route('fondy-foong'), false)
            ->assertSee('button-1YxNJRczNr_btn', false)
            ->assertSee('background:#e8fff7!important', false)
            ->assertSee('button-IIjIZEpKRo_btn:hover', false)
            ->assertSee('window.parent.scrollTo({top:0,behavior:"smooth"})', false);

        $this->get(route('fondy-foong'))
            ->assertOk()
            ->assertSee(route('legacy.embedded', ['path' => 'fondy-foong', 'v' => 'profile-links-6']), false);

        $this->get(route('legacy.embedded', ['path' => 'fondy-foong']))
            ->assertOk()
            ->assertSee('Fondy Foong')
            ->assertSee('HRDCorp Certified Trainer')
            ->assertSee('Professional Life Coach')
            ->assertSee('button-xKpI38KBQ7_btn', false)
            ->assertSee('window.parent.scrollTo({top:0,behavior:"smooth"})', false);
    }
}
