<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use App\Models\JobPosition;
use App\Services\Careers\JobApplicationService;
use Database\Seeders\JobPositionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobApplicationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_approved_job_positions_are_seeded(): void
    {
        $this->seed(JobPositionSeeder::class);

        $this->assertSame([
            'Digital Marketer',
            'Video Editor',
            'AI Software Engineer',
        ], JobPosition::query()->active()->pluck('title')->all());
    }

    public function test_an_application_and_its_resume_are_stored_privately(): void
    {
        $this->seed(JobPositionSeeder::class);
        Storage::fake(JobApplicationService::RESUME_DISK);

        $application = app(JobApplicationService::class)->submit([
            'job_position_id' => JobPosition::query()->where('slug', 'digital-marketer')->valueOrFail('id'),
            'full_name' => 'Applicant Name',
            'email' => 'applicant@example.com',
            'phone' => '+60 12 345 6789',
            'interview_available_on' => '2026-09-15',
            'interview_availability_notes' => 'Tuesday, 10:00 AM',
            'short_note' => 'A short application note.',
        ], UploadedFile::fake()->create('applicant-resume.pdf', 320, 'application/pdf'));

        $this->assertSame('new', $application->status);
        $this->assertSame(JobApplicationService::RESUME_DISK, $application->resume_disk);
        Storage::disk(JobApplicationService::RESUME_DISK)->assertExists($application->resume_path);
        $this->assertDatabaseHas('job_application_status_histories', [
            'job_application_id' => $application->id,
            'status' => 'new',
            'changed_by' => null,
        ]);
        $this->assertDatabaseCount(JobApplication::class, 1);
    }
}
