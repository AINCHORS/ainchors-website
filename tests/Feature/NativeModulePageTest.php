<?php

namespace Tests\Feature;

use Tests\TestCase;

class NativeModulePageTest extends TestCase
{
    public function test_six_approved_pages_render_as_direct_modules_without_legacy_iframes(): void
    {
        foreach ([
            '/about-us' => 'About Us',
            '/faqs' => 'Frequently Ask Questions',
            '/join-us' => 'Apply Now!',
            '/contact-us' => 'Feedback Form',
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
}
