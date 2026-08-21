<?php

namespace Tests\Feature;

use App\Models\JobPosition;
use App\Services\Careers\JobApplicationService;
use Database\Seeders\JobPositionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(JobPositionSeeder::class);
    }

    public function test_public_application_form_uses_local_terms_and_privacy_links(): void
    {
        $this->get(route('job-applications.create'))
            ->assertOk()
            ->assertSee('Digital Marketer')
            ->assertSee('Video Editor')
            ->assertSee('AI Software Engineer')
            ->assertSee('href="'.route('terms').'"', false)
            ->assertSee('href="'.route('privacy').'"', false)
            ->assertSee('recruitment_consent')
            ->assertDontSee('link.funnelfreedom.io');
    }

    public function test_valid_application_is_stored_and_redirects_to_the_approved_success_message(): void
    {
        Storage::fake(JobApplicationService::RESUME_DISK);
        $position = JobPosition::query()->where('slug', 'video-editor')->firstOrFail();

        $this->post(route('job-applications.store'), [
            'full_name' => 'Applicant Name',
            'email' => 'applicant@example.com',
            'phone' => '+60 12 345 6789',
            'interview_available_on' => '2026-09-15',
            'job_position_id' => $position->id,
            'resume' => UploadedFile::fake()->create('applicant-resume.pdf', 320, 'application/pdf'),
            'short_note' => 'A short application note.',
            'recruitment_consent' => '1',
        ])->assertRedirect(route('job-applications.success'));

        $this->assertDatabaseHas('job_applications', [
            'email' => 'applicant@example.com',
            'job_position_id' => $position->id,
            'status' => 'new',
        ]);

        $this->get(route('job-applications.success'))
            ->assertOk()
            ->assertSee('Thank you for applying to AINCHORS! We have received your application and our team will review it shortly.');
    }

    public function test_resume_and_recruitment_consent_are_required(): void
    {
        $this->from(route('job-applications.create'))
            ->post(route('job-applications.store'), [
                'full_name' => 'Applicant Name',
                'email' => 'applicant@example.com',
                'phone' => '+60 12 345 6789',
                'job_position_id' => JobPosition::query()->where('slug', 'ai-software-engineer')->valueOrFail('id'),
            ])
            ->assertRedirect(route('job-applications.create'))
            ->assertSessionHasErrorsIn('application', ['resume', 'recruitment_consent']);
    }

    public function test_join_us_legacy_apply_now_cta_is_replaced_with_the_local_application_url(): void
    {
        $this->get(route('legacy.embedded', ['path' => 'join-us']))
            ->assertOk()
            ->assertSee(route('job-applications.create'), false)
            ->assertSee('replace(/[^a-z]/g,"")', false);
    }
}
