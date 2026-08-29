<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\CourseContent;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('Demo / test')
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
                'course_category' => 'self_training',
                'status' => 'active',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('products', ['sku' => 'ADMIN-COURSE-001']);
        $this->actingAs($admin)->delete('/admin/products/'.$service->slug)->assertStatus(405);
    }

    public function test_product_management_ui_separates_catalogue_content_package_and_status_actions(): void
    {
        $admin = $this->admin();
        $course = $this->product('managed-ui-course', 'Managed UI Course', 'course', 'draft');
        $package = $this->product('managed-ui-package', 'Managed UI Package', 'course_package', 'draft');
        $service = $this->product('managed-ui-service', 'Managed UI Service', 'service', 'draft');

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Readiness')
            ->assertSee('Manage')
            ->assertDontSee('View<span', false);

        CourseContent::query()->create([
            'product_id' => $course->id,
            'video_title' => 'Managed UI Course Video',
            'video_provider' => 'private',
            'video_url' => 'courses/managed-ui-course/video/course.mp4',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['readiness' => 'ready']))
            ->assertOk()
            ->assertSee('Managed UI Course')
            ->assertDontSee('Managed UI Package');

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['readiness' => 'incomplete']))
            ->assertOk()
            ->assertSee('Managed UI Package')
            ->assertDontSee('Managed UI Course');

        $this->actingAs($admin)
            ->get(route('admin.products.show', $course))
            ->assertOk()
            ->assertSee('Activation readiness')
            ->assertSee('Course content')
            ->assertSee('Enrollments')
            ->assertDontSee(route('admin.package-members.index', $course))
            ->assertSee('Catalogue status');

        $this->actingAs($admin)
            ->get(route('admin.products.show', $package))
            ->assertOk()
            ->assertSee('Package courses')
            ->assertDontSee(route('admin.course-content.create', ['product_id' => $package->id]))
            ->assertDontSee(route('admin.enrollments.index', ['q' => $package->name]))
            ->assertDontSee('name="course_id"', false);

        $this->actingAs($admin)
            ->get(route('admin.products.show', $service))
            ->assertOk()
            ->assertSee('Orders')
            ->assertSee('Audit history')
            ->assertDontSee(route('admin.course-content.create', ['product_id' => $service->id]))
            ->assertDontSee(route('admin.package-members.index', $service))
            ->assertDontSee(route('admin.enrollments.index', ['q' => $service->name]));

        $this->actingAs($admin)
            ->get(route('admin.package-members.index', $package))
            ->assertOk()
            ->assertSee('Included courses')
            ->assertSee('Add course');

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $course))
            ->assertOk()
            ->assertSee('Product type cannot be changed because course content is configured.')
            ->assertSee('<select id="product-currency"', false)
            ->assertSee('Australian Dollar (AUD)')
            ->assertSee('Malaysian Ringgit (MYR)')
            ->assertSee('Chinese Yuan (CNY)')
            ->assertDontSee('Catalogue availability');

        $this->actingAs($admin)
            ->put(route('admin.products.update', $course), $this->productPayload([
                'type' => 'service',
                'sku' => $course->sku,
                'name' => $course->name,
                'slug' => $course->slug,
                'status' => $course->status,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('type');

        $this->assertSame('course', $course->fresh()->type);
    }

    public function test_admin_can_assign_read_filter_and_modify_a_course_category(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload([
                'type' => 'course',
                'sku' => 'ADMIN-CATEGORY-001',
                'name' => 'Categorised Course',
                'slug' => 'categorised-course',
                'course_category' => 'self_training',
            ]))
            ->assertRedirect();

        $course = Product::query()->where('sku', 'ADMIN-CATEGORY-001')->firstOrFail();
        $this->assertSame('self_training', $course->course_category);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['course_category' => 'self_training']))
            ->assertOk()
            ->assertSee('Categorised Course')
            ->assertSee('Self Training Courses');

        $this->actingAs($admin)
            ->put(route('admin.products.update', $course), $this->productPayload([
                'type' => 'course',
                'sku' => $course->sku,
                'name' => $course->name,
                'slug' => $course->slug,
                'course_category' => 'career_advancement',
            ]))
            ->assertRedirect(route('admin.products.show', $course));

        $this->assertSame('career_advancement', $course->fresh()->course_category);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'PRODUCT_UPDATED',
            'entity_id' => (string) $course->id,
        ]);
    }

    public function test_admin_product_billing_rules_are_enforced_by_product_type(): void
    {
        $admin = $this->admin();

        foreach ([
            ['type' => 'course', 'sku' => 'BILL-COURSE-001', 'slug' => 'billing-course'],
            ['type' => 'course_package', 'sku' => 'BILL-PACKAGE-001', 'slug' => 'billing-package'],
        ] as $productData) {
            $this->actingAs($admin)
                ->post(route('admin.products.store'), $this->productPayload([
                    ...$productData,
                    'name' => str($productData['type'])->replace('_', ' ')->headline().' Billing Test',
                    'billing_type' => 'monthly',
                ]))
                ->assertRedirect()
                ->assertSessionHasErrors('billing_type');

            $this->assertDatabaseMissing('products', ['sku' => $productData['sku']]);
        }

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload([
                'type' => 'service',
                'sku' => 'BILL-SERVICE-001',
                'name' => 'Monthly Managed Service',
                'slug' => 'monthly-managed-service',
                'currency' => 'myr',
                'price' => '2000',
                'billing_type' => 'monthly',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'sku' => 'BILL-SERVICE-001',
            'currency' => 'MYR',
            'billing_type' => 'monthly',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload([
                'sku' => 'BILL-CUSTOM-001',
                'slug' => 'unsupported-custom-billing',
                'billing_type' => 'custom',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('billing_type');

        $this->assertDatabaseMissing('products', ['sku' => 'BILL-CUSTOM-001']);
    }

    public function test_admin_product_form_uses_safe_image_paths_and_admin_only_branding(): void
    {
        $admin = $this->admin();
        $course = $this->product('billing-ui-course', 'Billing UI Course', 'course', 'draft');

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $course))
            ->assertOk()
            ->assertSee('Courses and course packages always use one-time billing.')
            ->assertDontSee('>Custom<', false)
            ->assertDontSee('sticky bottom-4', false)
            ->assertSee('bg-[#123f3a]', false)
            ->assertDontSee('brightness-0 invert', false);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload([
                'sku' => 'IMAGE-UNSAFE-001',
                'slug' => 'unsafe-image-path',
                'image' => '../.env',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('image');

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload([
                'sku' => 'IMAGE-SAFE-001',
                'slug' => 'safe-image-path',
                'image' => '/assets/logo.webp',
            ]))
            ->assertRedirect();

        $saved = Product::query()->where('sku', 'IMAGE-SAFE-001')->firstOrFail();
        $this->assertSame('assets/logo.webp', $saved->image);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $saved))
            ->assertOk()
            ->assertSee('Current image preview')
            ->assertSee(asset('assets/logo.webp'));
    }

    public function test_product_type_can_change_only_for_a_draft_product_without_dependencies(): void
    {
        $admin = $this->admin();
        $editable = $this->product('editable-draft-product', 'Editable Draft Product', 'service', 'draft');

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $editable))
            ->assertOk()
            ->assertSee('Product type can be changed while this Draft product has no business dependencies.')
            ->assertSee('name="type"', false);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $editable), $this->productPayload([
                'type' => 'consulting',
                'sku' => $editable->sku,
                'name' => $editable->name,
                'slug' => $editable->slug,
                'status' => 'draft',
            ]))
            ->assertRedirect(route('admin.products.show', $editable));

        $this->assertSame('consulting', $editable->fresh()->type);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'PRODUCT_UPDATED',
            'entity_id' => (string) $editable->id,
        ]);
    }

    public function test_product_type_is_locked_by_orders_content_and_package_relations(): void
    {
        $admin = $this->admin();
        $learner = User::factory()->create();

        $ordered = $this->product('ordered-draft-product', 'Ordered Draft Product', 'service', 'draft');
        $this->orderFor($learner, $ordered, 'AIN-TYPE-LOCK-ORDER');

        $enrolled = $this->product('enrolled-draft-product', 'Enrolled Draft Product', 'course', 'draft');
        Enrollment::query()->create([
            'user_id' => $learner->id,
            'product_id' => $enrolled->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $course = $this->product('content-locked-course', 'Content Locked Course', 'course', 'draft');
        CourseContent::query()->create([
            'product_id' => $course->id,
            'video_title' => 'Content Locked Course Video',
            'video_provider' => 'private',
            'video_url' => 'courses/content-locked-course/video/course.mp4',
        ]);

        $package = $this->product('related-draft-package', 'Related Draft Package', 'course_package', 'draft');
        $member = $this->product('related-package-course', 'Related Package Course', 'course', 'draft');
        ProductRelation::query()->create([
            'parent_product_id' => $package->id,
            'child_product_id' => $member->id,
            'relation_type' => 'bundle_item',
            'sort_order' => 1,
        ]);

        foreach ([$ordered, $enrolled, $course, $package] as $lockedProduct) {
            $this->actingAs($admin)
                ->put(route('admin.products.update', $lockedProduct), $this->productPayload([
                    'type' => 'consulting',
                    'sku' => $lockedProduct->sku,
                    'name' => $lockedProduct->name,
                    'slug' => $lockedProduct->slug,
                    'status' => 'draft',
                ]))
                ->assertRedirect()
                ->assertSessionHasErrors('type');
        }

        $this->assertSame('service', $ordered->fresh()->type);
        $this->assertSame('course', $enrolled->fresh()->type);
        $this->assertSame('course', $course->fresh()->type);
        $this->assertSame('course_package', $package->fresh()->type);
    }

    public function test_activation_readiness_is_specific_to_course_package_and_service_products(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $videoPath = 'courses/readiness-course/video/course.mp4';
        Storage::disk('local')->put($videoPath, 'private-video');

        $course = $this->product('readiness-course', 'Readiness Course', 'course', 'active');
        CourseContent::query()->create([
            'product_id' => $course->id,
            'video_title' => 'Readiness Course Video',
            'video_provider' => 'private',
            'video_url' => $videoPath,
        ]);
        $package = $this->product('readiness-package', 'Readiness Package', 'course_package', 'draft');
        ProductRelation::query()->create([
            'parent_product_id' => $package->id,
            'child_product_id' => $course->id,
            'relation_type' => 'bundle_item',
            'sort_order' => 1,
        ]);
        $service = $this->product('readiness-service', 'Readiness Service', 'service', 'draft');
        $missingVideo = $this->product('missing-video-course', 'Missing Video Course', 'course', 'draft');
        CourseContent::query()->create([
            'product_id' => $missingVideo->id,
            'video_title' => 'Missing Video Course Video',
            'video_provider' => 'private',
            'video_url' => 'courses/missing-video-course/video/missing.mp4',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.show', $course))
            ->assertOk()
            ->assertSee('Protected video file')
            ->assertSee('Course content')
            ->assertSee('Ready to activate');

        $this->actingAs($admin)
            ->get(route('admin.products.show', $package))
            ->assertOk()
            ->assertSee('Included courses')
            ->assertSee('Included course readiness')
            ->assertSee('Ready to activate')
            ->assertDontSee('Protected video file');

        $this->actingAs($admin)
            ->get(route('admin.products.show', $service))
            ->assertOk()
            ->assertSee('Product information')
            ->assertSee('Selling price')
            ->assertSee('Ready to activate')
            ->assertDontSee('Protected video file')
            ->assertDontSee('Included courses');

        $this->actingAs($admin)
            ->patch(route('admin.products.status', $package), ['status' => 'active'])
            ->assertRedirect(route('admin.products.show', $package));
        $this->assertSame('active', $package->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.products.status', $service), ['status' => 'active'])
            ->assertRedirect(route('admin.products.show', $service));
        $this->assertSame('active', $service->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.products.status', $missingVideo), ['status' => 'active'])
            ->assertRedirect()
            ->assertSessionHasErrors('status');
        $this->assertSame('draft', $missingVideo->fresh()->status);
    }

    public function test_course_content_ui_hides_private_paths_and_a_course_can_be_activated_after_metadata_is_configured(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $course = $this->product('protected-course', 'Protected Course', 'course', 'draft');
        Storage::disk('local')->put('courses/protected-course/video/course.mp4', 'private-video');
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

    public function test_admin_can_search_and_filter_the_course_content_list_by_category(): void
    {
        $admin = $this->admin();
        $selfTraining = $this->product('prompt-engineering', 'Prompt Engineering Essentials');
        $selfTraining->update(['course_category' => 'self_training']);
        $digitalMoney = $this->product('payment-systems', 'Modern Payment Systems');
        $digitalMoney->update(['course_category' => 'digital_money_mastery']);

        $this->actingAs($admin)
            ->get(route('admin.course-content.index'))
            ->assertOk()
            ->assertSee('Search courses')
            ->assertSee('Course category')
            ->assertSee('Self Training Courses')
            ->assertSee('Digital Money Mastery');

        $this->actingAs($admin)
            ->get(route('admin.course-content.index', ['q' => 'Prompt', 'course_category' => 'self_training']))
            ->assertOk()
            ->assertSee('Prompt Engineering Essentials')
            ->assertDontSee('Modern Payment Systems')
            ->assertSee('value="Prompt"', false)
            ->assertSee('value="self_training" selected', false);

        $this->actingAs($admin)
            ->get(route('admin.course-content.index', ['course_category' => 'digital_money_mastery']))
            ->assertOk()
            ->assertSee('Modern Payment Systems')
            ->assertDontSee('Prompt Engineering Essentials');
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
            ->post(route('admin.enrollments.store'), [
                'user_id' => $learner->id,
                'product_id' => $course->id,
                'reason' => 'Corporate training entitlement.',
            ])
            ->assertRedirect(route('admin.enrollments.index'));

        $enrollment = Enrollment::query()->where('user_id', $learner->id)->where('product_id', $course->id)->firstOrFail();
        $this->assertSame('active', $enrollment->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'ENROLLMENT_GRANTED', 'entity_id' => (string) $enrollment->id]);

        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), [
                'user_id' => $learner->id,
                'product_id' => $course->id,
                'reason' => 'Duplicate grant safety check.',
            ])
            ->assertRedirect(route('admin.enrollments.index'));
        $this->assertDatabaseCount('enrollments', 1);

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.revoke', $enrollment), ['reason' => 'Access entitlement ended.'])
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
