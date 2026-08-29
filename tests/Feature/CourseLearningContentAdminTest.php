<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\CourseContent;
use App\Models\Enrollment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseLearningContentAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_existing_lesson_content_populates_the_admin_editor(): void
    {
        [$course, $content] = $this->configuredCourse();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.course-content.edit', $content))
            ->assertOk()
            ->assertSee('Learning Page Content')
            ->assertSee('value="Welcome to the Course"', false)
            ->assertSee('Start body already stored')
            ->assertSee('value="Existing objective"', false)
            ->assertSee('value="Existing roadmap topic"', false)
            ->assertSee('value="Existing takeaway"', false)
            ->assertSee('value="Existing next step"', false)
            ->assertDontSee('courses/editable-course/video/original.mp4')
            ->assertDontSee('courses/editable-course/slides/original.pdf');
    }

    public function test_admin_can_update_all_learning_sections_and_blank_rows_are_removed_without_replacing_media(): void
    {
        [$course, $content] = $this->configuredCourse();
        $admin = User::factory()->create(['role' => 'admin']);
        $videoPath = $content->video_url;
        $slidePath = $content->slide_url;

        $this->actingAs($admin)
            ->put(route('admin.course-content.update', $content), [
                'video_title' => $content->video_title,
                'video_duration_seconds' => 120,
                'slide_name' => $content->slide_name,
                'lesson_content' => [
                    'start' => [
                        'title' => 'Getting Ready',
                        'body' => 'Updated start introduction.',
                        'objectives' => ['First objective', ' ', '0', 'Second objective'],
                    ],
                    'full' => [
                        'title' => 'Complete Programme',
                        'body' => 'Updated full-course introduction.',
                        'topics' => ['Roadmap one', '', 'Roadmap two'],
                    ],
                    'recap' => [
                        'title' => 'Review and Continue',
                        'body' => 'Updated recap introduction.',
                        'takeaways' => ['Takeaway one', '   ', 'Takeaway two'],
                        'next_steps' => ['', 'Next action'],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.course-content.edit', $content));

        $content->refresh();

        $this->assertSame($videoPath, $content->video_url);
        $this->assertSame($slidePath, $content->slide_url);
        $this->assertSame(['First objective', '0', 'Second objective'], data_get($content->lesson_content, 'start.objectives'));
        $this->assertSame(['Roadmap one', 'Roadmap two'], data_get($content->lesson_content, 'full.topics'));
        $this->assertSame(['Takeaway one', 'Takeaway two'], data_get($content->lesson_content, 'recap.takeaways'));
        $this->assertSame(['Next action'], data_get($content->lesson_content, 'recap.next_steps'));

        $audit = AdminAuditLog::query()->where('action', 'COURSE_CONTENT_UPDATED')->latest('id')->firstOrFail();
        $this->assertSame('Getting Ready', data_get($audit->after_values, 'lesson_content.start.title'));
        $this->assertSame(3, data_get($audit->after_values, 'lesson_content.start.objectives_count'));
        $this->assertArrayNotHasKey('video_url', $audit->after_values);
    }

    public function test_replacing_only_video_preserves_lesson_content_and_slides(): void
    {
        [$course, $content] = $this->configuredCourse();
        $admin = User::factory()->create(['role' => 'admin']);
        $lesson = $content->lesson_content;
        $slides = $content->slide_url;

        $this->actingAs($admin)
            ->put(route('admin.course-content.update', $content), [
                'video_title' => 'Replacement video',
                'slide_name' => $content->slide_name,
                'video_file' => UploadedFile::fake()->create('replacement.mp4', 100, 'video/mp4'),
            ])
            ->assertRedirect(route('admin.course-content.edit', $content));

        $content->refresh();
        $this->assertSame($lesson, $content->lesson_content);
        $this->assertSame($slides, $content->slide_url);
        $this->assertNotSame('courses/editable-course/video/original.mp4', $content->video_url);
        Storage::disk('local')->assertExists($content->video_url);
        Storage::disk('local')->assertExists($slides);
    }

    public function test_admin_can_replace_an_octet_stream_pptx_without_erasing_video_or_lesson_content(): void
    {
        [, $content] = $this->configuredCourse();
        $admin = User::factory()->create(['role' => 'admin']);
        $lesson = $content->lesson_content;
        $video = $content->video_url;
        $oldSlides = $content->slide_url;

        $this->actingAs($admin)
            ->put(route('admin.course-content.update', $content), [
                'video_title' => $content->video_title,
                'slide_name' => 'Replacement PowerPoint',
                'slide_file' => UploadedFile::fake()->create('replacement.pptx', 1024, 'application/octet-stream'),
            ])
            ->assertRedirect(route('admin.course-content.edit', $content));

        $content->refresh();
        $this->assertSame($lesson, $content->lesson_content);
        $this->assertSame($video, $content->video_url);
        $this->assertNotSame($oldSlides, $content->slide_url);
        $this->assertSame('replacement.pptx', $content->slide_original_name);
        Storage::disk('local')->assertExists($video);
        Storage::disk('local')->assertExists($content->slide_url);
        Storage::disk('local')->assertMissing($oldSlides);
    }

    public function test_non_admin_cannot_edit_course_learning_content(): void
    {
        [, $content] = $this->configuredCourse();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.course-content.edit', $content))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.course-content.update', $content), [
                'video_title' => 'Unauthorized change',
                'lesson_content' => $this->lessonContent(),
            ])
            ->assertForbidden();
    }

    public function test_enrolled_user_sees_updated_escaped_content_and_unenrolled_user_cannot_access_it(): void
    {
        [$course, $content] = $this->configuredCourse();
        $admin = User::factory()->create(['role' => 'admin']);
        $lesson = $this->lessonContent();
        $lesson['start']['title'] = 'Custom Orientation';
        $lesson['start']['body'] = '<script>window.courseAttack=true</script>Safe lesson text';

        $this->actingAs($admin)->put(route('admin.course-content.update', $content), [
            'video_title' => $content->video_title,
            'slide_name' => $content->slide_name,
            'lesson_content' => $lesson,
        ])->assertRedirect();

        $unenrolled = User::factory()->create();
        $this->actingAs($unenrolled)
            ->get(route('learn.show', $course))
            ->assertRedirect(route('courses.show', $course));

        $learner = User::factory()->create();
        Enrollment::query()->create([
            'user_id' => $learner->id,
            'product_id' => $course->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learn.show', $course))
            ->assertOk()
            ->assertSee('01 Custom Orientation')
            ->assertSee('02 Complete Programme')
            ->assertSee('03 Review and Continue')
            ->assertSee('Editable Course Slides')
            ->assertSee('Download Course Slides')
            ->assertSee('href="#start"', false)
            ->assertSee('href="#full-course"', false)
            ->assertSee('href="#recap"', false)
            ->assertSee(e('<script>window.courseAttack=true</script>Safe lesson text'), false)
            ->assertDontSee('<script>window.courseAttack=true</script>', false);
    }

    /** @return array{Product, CourseContent} */
    private function configuredCourse(): array
    {
        $course = Product::query()->create([
            'type' => 'course',
            'sku' => 'EDITABLE-COURSE',
            'name' => 'Editable Course',
            'slug' => 'editable-course',
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
        ]);

        $videoPath = 'courses/editable-course/video/original.mp4';
        $slidePath = 'courses/editable-course/slides/original.pdf';
        Storage::disk('local')->put($videoPath, 'private video');
        Storage::disk('local')->put($slidePath, 'private slides');

        $content = CourseContent::query()->create([
            'product_id' => $course->id,
            'video_title' => 'Editable Course Video',
            'video_provider' => 'private',
            'video_url' => $videoPath,
            'video_original_name' => 'original.mp4',
            'video_file_size' => 13,
            'slide_name' => 'Editable Course Slides',
            'slide_url' => $slidePath,
            'slide_original_name' => 'original.pdf',
            'slide_file_size' => 14,
            'lesson_content' => $this->lessonContent(),
        ]);

        return [$course, $content];
    }

    /** @return array<string, mixed> */
    private function lessonContent(): array
    {
        return [
            'start' => [
                'title' => 'Welcome to the Course',
                'body' => 'Start body already stored',
                'objectives' => ['Existing objective'],
            ],
            'full' => [
                'title' => 'Complete Programme',
                'body' => 'Full course body already stored',
                'topics' => ['Existing roadmap topic'],
            ],
            'recap' => [
                'title' => 'Review and Continue',
                'body' => 'Recap body already stored',
                'takeaways' => ['Existing takeaway'],
                'next_steps' => ['Existing next step'],
            ],
        ];
    }
}
