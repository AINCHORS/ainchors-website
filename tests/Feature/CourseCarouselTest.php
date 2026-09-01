<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
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
            ->assertSee('Training Courses | AINCHORS')
            ->assertSee('Explore AINCHORS video courses')
            ->assertDontSee('Explore the ten AINCHORS video courses')
            ->assertSee('Artificial Intelligence Courses')
            ->assertSee('Data Analysis Courses')
            ->assertSee('Digital Financial Mastery')
            ->assertSee('Career Advancement Courses')
            ->assertSee('data-carousel-viewport', false)
            ->assertSee('data-carousel-track', false)
            ->assertSee('aria-label="Previous courses"', false)
            ->assertSee('aria-label="Next courses"', false)
            ->assertSee('data-carousel-pagination', false)
            ->assertSee('min-h-[3.25rem]', false);

        $categoryLabels = [
            'Artificial Intelligence Courses',
            'Data Analysis Courses',
            'Digital Financial Mastery',
            'Career Advancement Courses',
        ];
        $categoryPositions = array_map(
            static fn (string $label): int|false => strpos($html, $label),
            $categoryLabels,
        );

        $this->assertNotContains(false, $categoryPositions);
        $this->assertSame($categoryPositions, collect($categoryPositions)->sort()->values()->all());
        $this->assertDatabaseHas('products', [
            'sku' => 'SL-DA-003',
            'course_category' => 'data_analysis',
        ]);
        $this->assertDatabaseHas('products', [
            'sku' => 'SL-SQL-004',
            'course_category' => 'data_analysis',
        ]);

        $this->assertStringNotContainsString('href="#slide', $html);
        $this->assertStringNotContainsString('href="#course', $html);
        $this->assertStringNotContainsString('window.location.hash', $html);
        $this->assertStringNotContainsString('location.hash', $html);
        $this->assertStringNotContainsString('hashchange', $html);
    }

    public function test_my_courses_filter_uses_the_same_ordered_course_categories(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('my-courses'))
            ->assertOk();

        $html = $response->getContent();
        $categoryPositions = array_values(array_map(
            static fn (string $label): int|false => strpos($html, $label),
            Product::COURSE_CATEGORIES,
        ));

        $this->assertNotContains(false, $categoryPositions);
        $this->assertSame($categoryPositions, collect($categoryPositions)->sort()->values()->all());
        $this->assertStringNotContainsString('name="q"', $html);
    }
}
