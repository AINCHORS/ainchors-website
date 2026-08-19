<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->artisan('ainchors:populate-legacy-course-catalogue')->assertExitCode(0);
    }

    public function test_canonical_homepage_uses_the_native_global_shell_and_authoritative_content(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Empowering Talent to Shape The Future')
            ->assertSee('is a global fintech firm in learning and strategy')
            ->assertSee('Corporate training')
            ->assertSee('Self learning Courses')
            ->assertSee('Mentorship and Coaching')
            ->assertSee('What our Customers are Saying')
            ->assertSee('AINCHORS Sdn Bhd')
            ->assertSee('Consulting Introduction')
            ->assertSee('Public / Government Sector')
            ->assertSee('Private Sector')
            ->assertSee('Welcome to AINCHORS')
            ->assertDontSee('legacy-responsive.css')
            ->assertDontSee('/src/main.tsx')
            ->assertDontSee('/src/main.jsx');

        $this->get('/home')->assertRedirect(route('home'));
    }

    public function test_public_navigation_pages_have_explicit_routes_and_the_legacy_fallback_remains_available(): void
    {
        foreach (['about-us-814253', 'trainers-profile', 'testimonials', 'courses', 'success-story-of-angie', 'consulting-main', 'consulting-gov', 'consulting-private', 'faqs', 'hiring-page', 'contact-us', 'terms--conditions', 'privacy--policy'] as $path) {
            $this->get('/'.$path)->assertOk();
        }

        $this->get('/product-details/product/6a55cb4d03821e4f56e9e11f')->assertOk();
    }
}
