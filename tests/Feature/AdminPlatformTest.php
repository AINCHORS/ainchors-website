<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\CourseContent;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_normal_users_receive_403_for_the_administration_platform(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_access_dashboard_with_real_database_metrics(): void
    {
        $admin = $this->admin();
        $learner = User::factory()->create();
        $product = $this->product('dashboard-service', 'Dashboard Service', 'service');
        $order = $this->orderFor($learner, $product, 'AIN-ADMIN-DASHBOARD');
        $order->payments()->create([
            'provider' => 'demo',
            'provider_transaction_id' => 'DEMO-DASHBOARD-001',
            'amount' => 19,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
            'provider_data' => ['mode' => 'test'],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Demo/test payment')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['total_users'] === 2
                && $metrics['total_orders'] === 1
                && $metrics['completed_payments'] === 1);
    }

    public function test_secure_admin_provisioning_hashes_a_runtime_password_and_never_echoes_it(): void
    {
        $email = 'info@ainchors.com';
        $password = 'A!'.Str::random(30).'9';

        $this->artisan('ainchors:create-admin', ['--email' => $email])
            ->expectsQuestion('Administrator full name', 'Provisioned Administrator')
            ->expectsQuestion('Administrator password', $password)
            ->expectsQuestion('Confirm administrator password', $password)
            ->doesntExpectOutputToContain($password)
            ->assertExitCode(0);

        $admin = User::query()->where('email', $email)->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check($password, $admin->password));
        $this->assertNotSame($password, $admin->password);
    }

    public function test_admin_can_create_a_user_without_storing_or_rendering_password_data_in_audit_history(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'full_name' => 'Managed Learner',
                'email' => 'managed-learner@example.test',
                'status' => 'active',
                'password' => 'temporary-password-123',
                'password_confirmation' => 'temporary-password-123',
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'managed-learner@example.test')->firstOrFail();
        $audit = AdminAuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('USER_CREATED', $audit->action);
        $this->assertSame($admin->id, $audit->admin_user_id);
        $this->assertArrayNotHasKey('password', $audit->after_values ?? []);
        $this->assertNotSame('temporary-password-123', $user->password);
    }

    public function test_admin_cannot_deactivate_or_demote_their_own_account_and_no_user_delete_route_exists(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $admin), ['status' => 'inactive'])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'full_name' => $admin->full_name,
                'email' => $admin->email,
                'role' => 'user',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('role');

        $this->actingAs($admin)
            ->delete('/admin/users/'.$admin->id)
            ->assertStatus(405);

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_admin_product_management_is_non_destructive_and_course_activation_requires_course_content(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload([
                'type' => 'service',
                'sku' => 'ADMIN-SERVICE-001',
                'name' => 'Managed Service',
                'slug' => 'managed-service',
                'status' => 'active',
            ]))
            ->assertRedirect();

        $service = Product::query()->where('sku', 'ADMIN-SERVICE-001')->firstOrFail();
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'PRODUCT_CREATED', 'entity_id' => (string) $service->id]);

        $this->actingAs($admin)
            ->patch(route('admin.products.status', $service), ['status' => 'inactive'])
            ->assertRedirect();
        $this->assertSame('inactive', $service->fresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'PRODUCT_DISABLED', 'entity_id' => (string) $service->id]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload([
                'type' => 'course',
                'sku' => 'ADMIN-COURSE-001',
                'name' => 'Unconfigured Course',
                'slug' => 'unconfigured-course',
                'status' => 'active',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('products', ['sku' => 'ADMIN-COURSE-001']);
        $this->actingAs($admin)->delete('/admin/products/'.$service->slug)->assertStatus(405);
    }

    public function test_course_content_ui_hides_private_paths_and_a_course_can_be_activated_after_metadata_is_configured(): void
    {
        $admin = $this->admin();
        $course = $this->product('protected-course', 'Protected Course', 'course', 'draft');
        $content = CourseContent::query()->create([
            'product_id' => $course->id,
            'video_title' => 'Protected Course Video',
            'video_provider' => 'private',
            'video_url' => 'courses/protected-course/video/course.mp4',
            'slide_name' => 'Protected Course Slides',
            'slide_url' => 'courses/protected-course/slides/course-slides.pptx',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.course-content.edit', $content))
            ->assertOk()
            ->assertDontSee('courses/protected-course/video/course.mp4')
            ->assertDontSee('courses/protected-course/slides/course-slides.pptx');

        $this->actingAs($admin)
            ->patch(route('admin.products.status', $course), ['status' => 'active'])
            ->assertRedirect();

        $this->assertSame('active', $course->fresh()->status);
    }

    public function test_orders_and_payments_are_inspectable_but_have_no_mutation_routes(): void
    {
        $admin = $this->admin();
        $learner = User::factory()->create();
        $product = $this->product('financial-service', 'Financial Service', 'service');
        $order = $this->orderFor($learner, $product, 'AIN-READ-ONLY');
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'demo',
            'provider_transaction_id' => 'DEMO-READ-ONLY-001',
            'amount' => 99,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
            'provider_data' => ['token' => 'never-render-or-change'],
        ]);

        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertDontSee('never-render-or-change');
        $this->actingAs($admin)->get(route('admin.payments.show', $payment))->assertOk()->assertDontSee('never-render-or-change');
        $this->actingAs($admin)->put(route('admin.orders.show', $order), ['status' => 'cancelled'])->assertStatus(405);
        $this->actingAs($admin)->put(route('admin.payments.show', $payment), ['status' => 'refunded'])->assertStatus(405);

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_admin_can_grant_and_revoke_an_enrollment_without_duplicates_and_with_audit_history(): void
    {
        $admin = $this->admin();
        $learner = User::factory()->create(['status' => 'active']);
        $course = $this->configuredCourse('admin-enrollment-course', 'Admin Enrollment Course');

        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), ['user_id' => $learner->id, 'product_id' => $course->id])
            ->assertRedirect(route('admin.enrollments.index'));

        $enrollment = Enrollment::query()->where('user_id', $learner->id)->where('product_id', $course->id)->firstOrFail();
        $this->assertSame('active', $enrollment->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'ENROLLMENT_GRANTED', 'entity_id' => (string) $enrollment->id]);

        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), ['user_id' => $learner->id, 'product_id' => $course->id])
            ->assertRedirect(route('admin.enrollments.index'));
        $this->assertDatabaseCount('enrollments', 1);

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.revoke', $enrollment))
            ->assertRedirect(route('admin.enrollments.index'));

        $this->assertSame('revoked', $enrollment->fresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'ENROLLMENT_REVOKED', 'entity_id' => (string) $enrollment->id]);
    }

    public function test_admin_can_update_lead_status_and_the_validated_welcome_modal_setting(): void
    {
        $admin = $this->admin();
        $lead = Lead::query()->create([
            'source' => 'contact',
            'full_name' => 'Contact Person',
            'email' => 'contact-person@example.test',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.leads.update', $lead), ['status' => 'contacted'])
            ->assertRedirect(route('admin.leads.show', $lead));
        $this->assertSame('contacted', $lead->fresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'LEAD_STATUS_CHANGED', 'entity_id' => (string) $lead->id]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), ['welcome_modal_frequency' => 'session_once'])
            ->assertRedirect(route('admin.settings.index'));
        $this->assertDatabaseHas('site_settings', ['key' => 'welcome_modal_frequency', 'value' => 'session_once']);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'WELCOME_MODAL_FREQUENCY_CHANGED']);

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->put(route('admin.settings.update'), ['welcome_modal_frequency' => 'arbitrary_value'])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHasErrors('welcome_modal_frequency');

        $this->assertSame('session_once', SiteSetting::query()->where('key', 'welcome_modal_frequency')->value('value'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function product(string $slug, string $name, string $type = 'course', string $status = 'active'): Product
    {
        return Product::query()->create([
            'type' => $type,
            'sku' => 'TEST-'.strtoupper(str_replace('-', '_', $slug)),
            'name' => $name,
            'slug' => $slug,
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => $status,
        ]);
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

    private function orderFor(User $user, Product $product, string $number): Order
    {
        $order = Order::query()->create([
            'order_number' => $number,
            'user_id' => $user->id,
            'status' => 'completed',
            'currency' => 'USD',
            'subtotal' => 19,
            'discount_total' => 0,
            'tax_total' => 0,
            'total_amount' => 19,
            'placed_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 19,
            'line_total' => 19,
        ]);

        return $order;
    }

    /** @param array<string, mixed> $overrides */
    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'service',
            'sku' => 'ADMIN-DEFAULT-001',
            'name' => 'Administrative Product',
            'slug' => 'administrative-product',
            'short_description' => 'Short description',
            'description' => 'Full description',
            'price' => '49.00',
            'currency' => 'usd',
            'billing_type' => 'one_time',
            'status' => 'draft',
        ], $overrides);
    }
}
