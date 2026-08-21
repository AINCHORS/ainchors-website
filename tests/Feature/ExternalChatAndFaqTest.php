<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExternalChatAndFaqTest extends TestCase
{
    public function test_legacy_pages_do_not_serve_the_external_tidio_widget(): void
    {
        foreach ([
            '/_legacy/success-story-of-angie',
            '/_legacy/testimonials',
            '/_legacy/trainers-profile',
        ] as $url) {
            $response = $this->get($url)
                ->assertOk()
                ->assertDontSee('<script src="//code.tidio.co');

            $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        }
    }

    public function test_faq_module_contains_accessible_accordion_behaviour(): void
    {
        $this->get('/faqs')
            ->assertOk()
            ->assertSee('data-faq-accordion')
            ->assertSee('aria-expanded')
            ->assertSee('border-right: 2px solid currentColor')
            ->assertSee('opacity: 1 !important')
            ->assertSee('hl-faq-child-panel[hidden]')
            ->assertDontSee('code.tidio.co');
    }
}
