<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\ConsultationRequest;
use App\Models\CourseContent;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneAdminPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_shared_login_routes_the_configured_admin_to_dashboard_and_preserves_admin_intention(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->get(route('admin.payments.index'))->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.payments.index'));
    }

    public function test_role_admin_with_wrong_email_is_not_an_authorized_administrator(): void
    {
        $wrongAdmin = User::query()->create([
            'role' => 'admin',
            'full_name' => 'Wrong Administrator',
            'email' => 'wrong-admin@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);

        $this->actingAs($wrongAdmin)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        auth()->logout();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $wrongAdmin->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_manage_a_consultation_and_changes_are_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = Lead::query()->create([
            'source' => 'consulting_booking',
            'full_name' => 'Government Client',
            'email' => 'client@example.test',
            'company_name' => 'Public Agency',
            'status' => 'consultation_requested',
        ]);
        $consultation = ConsultationRequest::query()->create([
            'lead_id' => $lead->id,
            'status' => 'requested',
            'requested_at' => now(),
            'source_page' => '/consulting-gov/booking',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.consultations.index'))
            ->assertOk()
            ->assertSee('Government Client');

        $scheduledAt = now()->addDay()->startOfHour();

        $this->actingAs($admin)
            ->put(route('admin.consultations.update', $consultation), [
                'status' => 'booked',
                'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
                'notes' => 'Prepare a public-sector AI consultation agenda.',
                'assigned_to_me' => '1',
            ])
            ->assertRedirect(route('admin.consultations.show', $consultation));

        $consultation->refresh();
        $lead->refresh();

        $this->assertSame('booked', $consultation->status);
        $this->assertSame($admin->id, $consultation->assigned_admin_id);
        $this->assertNotNull($consultation->scheduled_at);
        $this->assertSame('consultation_booked', $lead->status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'CONSULTATION_UPDATED',
            'entity_id' => (string) $consultation->id,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'LEAD_STATUS_CHANGED',
            'entity_id' => (string) $lead->id,
        ]);
    }

    public function test_audit_viewer_re_redacts_sensitive_historical_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $audit = AdminAuditLog::query()->create([
            'admin_user_id' => $admin->id,
            'action' => 'LEGACY_TEST',
            'entity_type' => User::class,
            'entity_id' => '99',
            'before_values' => [],
            'after_values' => [
                'password' => 'historical-secret-value',
                'status' => 'active',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-log.show', $audit))
            ->assertOk()
            ->assertSee('[redacted]')
            ->assertSee('active')
            ->assertDontSee('historical-secret-value');
    }

    public function test_admin_can_add_reorder_and_remove_package_courses_with_audit_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $package = $this->product('phase-one-package', 'Phase One Package', 'course_package', 'draft');
        $first = $this->configuredCourse('phase-one-course-a', 'Phase One Course A');
        $second = $this->configuredCourse('phase-one-course-b', 'Phase One Course B');

        $this->actingAs($admin)
            ->post(route('admin.package-members.store', $package), ['course_id' => $first->id])
            ->assertRedirect(route('admin.products.show', $package));
        $this->actingAs($admin)
            ->post(route('admin.package-members.store', $package), ['course_id' => $second->id])
            ->assertRedirect(route('admin.products.show', $package));

        $this->assertDatabaseCount('product_relations', 2);

        $this->actingAs($admin)
            ->from(route('admin.products.show', $package))
            ->post(route('admin.package-members.store', $package), ['course_id' => $first->id])
            ->assertRedirect(route('admin.products.show', $package))
            ->assertSessionHasErrors('course_id');

        $this->actingAs($admin)
            ->patch(route('admin.package-members.reorder', $package), [
                'positions' => [
                    $first->id => 2,
                    $second->id => 1,
                ],
            ])
            ->assertRedirect(route('admin.products.show', $package));

        $orderedIds = ProductRelation::query()
            ->where('parent_product_id', $package->id)
            ->where('relation_type', 'bundle_item')
            ->orderBy('sort_order')
            ->pluck('child_product_id')
            ->all();
        $this->assertSame([$second->id, $first->id], $orderedIds);

        $this->actingAs($admin)
            ->delete(route('admin.package-members.destroy', [$package, $first]))
            ->assertRedirect(route('admin.products.show', $package));

        $this->assertDatabaseMissing('product_relations', [
            'parent_product_id' => $package->id,
            'child_product_id' => $first->id,
            'relation_type' => 'bundle_item',
        ]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'PACKAGE_COURSE_ADDED']);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'PACKAGE_COURSES_REORDERED']);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'PACKAGE_COURSE_REMOVED']);
    }

    public function test_manual_enrollment_requires_and_audits_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create();
        $course = $this->configuredCourse('reason-course', 'Reason Course');

        $this->actingAs($admin)
            ->from(route('admin.enrollments.index'))
            ->post(route('admin.enrollments.store'), [
                'user_id' => $learner->id,
                'product_id' => $course->id,
            ])
            ->assertRedirect(route('admin.enrollments.index'))
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('enrollments', 0);

        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), [
                'user_id' => $learner->id,
                'product_id' => $course->id,
                'reason' => 'Manual corporate entitlement.',
            ])
            ->assertRedirect(route('admin.enrollments.index'));

        $enrollment = Enrollment::query()->firstOrFail();
        $audit = AdminAuditLog::query()
            ->where('action', 'ENROLLMENT_GRANTED')
            ->where('entity_id', (string) $enrollment->id)
            ->firstOrFail();

        $this->assertSame('Manual corporate entitlement.', data_get($audit->after_values, 'manual_reason'));
    }

    private function configuredCourse(string $slug, string $name): Product
    {
        $course = $this->product($slug, $name, 'course', 'active');
        CourseContent::query()->create([
            'product_id' => $course->id,
            'video_title' => $name.' Video',
            'video_provider' => 'private',
            'video_url' => 'courses/'.$slug.'/video/course.mp4',
            'slide_name' => $name.' Slides',
            'slide_url' => 'courses/'.$slug.'/slides/course-slides.pptx',
        ]);

        return $course;
    }

    private function product(string $slug, string $name, string $type, string $status): Product
    {
        return Product::query()->create([
            'type' => $type,
            'sku' => 'PHASE1-'.strtoupper(str_replace('-', '_', $slug)),
            'name' => $name,
            'slug' => $slug,
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => $status,
        ]);
    }
}
