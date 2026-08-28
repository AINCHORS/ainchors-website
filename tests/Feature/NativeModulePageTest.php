<?php

namespace Tests\Feature;

use Tests\TestCase;

class NativeModulePageTest extends TestCase
{
    public function test_five_approved_pages_render_as_direct_modules_without_legacy_iframes(): void
    {
        foreach ([
            '/about-us' => 'About Us',
            '/faqs' => 'Frequently Ask Questions',
            '/join-us' => 'Apply Now!',
            '/terms--conditions' => 'Terms and Conditions',
            '/privacy--policy' => 'Privacy Policy',
        ] as $url => $expectedContent) {
            $this->get($url)
                ->assertOk()
                ->assertSee($expectedContent)
                ->assertSee('native-page-module')
                ->assertSee('attachShadow')
                ->assertDontSee('legacy-page-frame')
                ->assertDontSee('/_legacy/');
        }
    }

    public function test_contact_page_is_native_and_does_not_render_legacy_markup(): void
    {
        $this->get('/contact-us')->assertOk()
            ->assertSee('data-contact-page', false)
            ->assertSee('Get in touch')
            ->assertSee('Send us a message')
            ->assertSee('Enquiry Type')
            ->assertSee('Send Message')
            ->assertDontSee('native-page-module')
            ->assertDontSee('attachShadow')
            ->assertDontSee('legacy-page-frame')
            ->assertDontSee('/_legacy/');
    }
}
