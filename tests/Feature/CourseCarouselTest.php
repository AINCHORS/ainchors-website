<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCarouselTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->artisan('ainchors:populate-legacy-course-catalogue')->assertExitCode(0);
    }

    public function test_course_categories_render_independent_accessible_carousels_without_hash_navigation(): void
    {
        $response = $this->get(route('courses.index'))->assertOk();
        $html = $response->getContent();

        $activeCourses = Product::query()
            ->where('type', 'course')
            ->where('status', 'active');
        $expectedCarousels = (clone $activeCourses)
            ->whereNotNull('course_category')
            ->distinct()
            ->count('course_category')
            + ((clone $activeCourses)->whereNull('course_category')->exists() ? 1 : 0);

        $this->assertGreaterThan(0, $expectedCarousels);
        $this->assertSame($expectedCarousels, substr_count($html, 'data-course-carousel'));
        $response
            ->assertSee('data-carousel-viewport', false)
            ->assertSee('data-carousel-track', false)
            ->assertSee('aria-label="Previous courses"', false)
            ->assertSee('aria-label="Next courses"', false)
            ->assertSee('data-carousel-pagination', false)
            ->assertSee('min-h-[3.25rem]', false);

        $this->assertStringNotContainsString('href="#slide', $html);
        $this->assertStringNotContainsString('href="#course', $html);
        $this->assertStringNotContainsString('window.location.hash', $html);
        $this->assertStringNotContainsString('location.hash', $html);
        $this->assertStringNotContainsString('hashchange', $html);
    }
}
