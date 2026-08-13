<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_homepage_uses_authoritative_legacy_content(): void
    {
        $this->get('/home')
            ->assertOk()
            ->assertSee('Empowering Talent to Shape The Future')
            ->assertSee('AINCHORS is a global fintech firm in learning and strategy')
            ->assertSee('Corporate training')
            ->assertSee('Self learning Courses')
            ->assertSee('Mentorship and Coaching')
            ->assertSee('What our Customers are Saying')
            ->assertSee('AINCHORS Sdn Bhd')
            ->assertSee('legacy-responsive.css')
            ->assertDontSee('React');
    }

    public function test_legacy_navigation_pages_are_available_locally(): void
    {
        foreach (['about-us-814253', 'trainers-profile', 'testimonials', 'courses', 'success-story-of-angie', 'consulting-main', 'consulting-gov', 'consulting-private', 'faqs', 'hiring-page', 'contact-us'] as $path) {
            $this->get('/'.$path)->assertOk();
        }

        $this->get('/product-details/product/6a55cb4d03821e4f56e9e11f')->assertOk();
    }
}
