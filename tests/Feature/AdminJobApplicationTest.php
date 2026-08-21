<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\JobApplication;
use App\Models\JobPosition;
use App\Models\User;
use App\Services\Careers\JobApplicationService;
use Database\Seeders\JobPositionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminJobApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(JobPositionSeeder::class);
        Storage::fake(JobApplicationService::RESUME_DISK);
    }

    public function test_only_administrators_can_review_job_applications(): void
    {
        $application = $this->application();
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('admin.job-applications.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.job-applications.show', $application))->assertForbidden();
    }

    public function test_administration_platform_cannot_create_a_second_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'full_name' => 'Second Administrator',
                'email' => 'second-admin@example.test',
                'role' => 'admin',
                'status' => 'active',
                'password' => 'temporary-password-123',
                'password_confirmation' => 'temporary-password-123',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame(1, User::query()->where('role', 'admin')->count());
    }

    public function test_administrator_can_review_an_application_without_exposing_its_private_path(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $application = $this->application();

        $this->actingAs($admin)
            ->get(route('admin.job-applications.show', $application))
            ->assertOk()
            ->assertSee('Download resume')
            ->assertDontSee($application->resume_path);

        $this->actingAs($admin)
            ->put(route('admin.job-applications.update', $application), [
                'status' => 'reviewing',
                'internal_notes' => 'Initial screening complete.',
            ])
            ->assertRedirect(route('admin.job-applications.show', $application));

        $this->assertSame('reviewing', $application->fresh()->status);
        $this->assertDatabaseHas('job_application_status_histories', [
            'job_application_id' => $application->id,
            'status' => 'reviewing',
            'changed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'JOB_APPLICATION_STATUS_UPDATED',
            'entity_id' => (string) $application->id,
        ]);
        $this->assertDatabaseMissing('admin_audit_logs', ['after_values' => json_encode(['resume_path' => $application->resume_path])]);

        $this->actingAs($admin)
            ->get(route('admin.job-applications.resume', $application))
            ->assertOk()
            ->assertDownload('candidate-resume.pdf');
    }

    private function application(): JobApplication
    {
        return app(JobApplicationService::class)->submit([
            'job_position_id' => JobPosition::query()->where('slug', 'digital-marketer')->valueOrFail('id'),
            'full_name' => 'Candidate Person',
            'email' => 'candidate@example.test',
            'phone' => '+60 12 345 6789',
            'short_note' => 'Candidate introduction.',
        ], UploadedFile::fake()->create('candidate-resume.pdf', 120, 'application/pdf'));
    }
}
