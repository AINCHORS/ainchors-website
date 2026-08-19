<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseCommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->artisan('ainchors:populate-legacy-course-catalogue')->assertExitCode(0);
        $this->artisan('ainchors:populate-course-learning-content')->assertExitCode(0);
    }

    public function test_guest_checkout_redirects_to_login_and_preserves_intention(): void
    {
        $course = $this->course();
        $response = $this->get(route('checkout.show', $course));

        $response->assertRedirect(route('login'));
        $this->assertSame(route('checkout.show', $course), session('url.intended'));
    }

    public function test_registration_returns_to_original_checkout(): void
    {
        $course = $this->course();
        $this->get(route('checkout.show', $course));

        $response = $this->post(route('register.store'), [
            'name' => 'New Learner', 'email' => 'new@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('checkout.show', $course));
        $this->assertAuthenticated();
    }

    public function test_login_returns_to_original_checkout(): void
    {
        $user = User::factory()->create(['email' => 'learner@example.com']);
        $course = $this->course();
        $this->get(route('checkout.show', $course));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('checkout.show', $course));
    }

    public function test_checkout_uses_readonly_account_identity_and_canonical_prices(): void
    {
        $user = User::factory()->create();
        $course = $this->course();

        $this->actingAs($user)->get(route('checkout.show', $course))
            ->assertOk()
            ->assertSee('value="'.$user->full_name.'" readonly', false)
            ->assertSee('value="'.$user->email.'" readonly', false)
            ->assertSee('USD 50')
            ->assertSee('USD 19');

        $package = $this->package();
        $this->actingAs($user)->get(route('checkout.show', $package))
            ->assertOk()->assertSee('USD 190')->assertSee('USD 150');
    }

    public function test_individual_demo_payment_creates_one_order_payment_and_enrollment_without_card_data(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $token = $this->checkoutToken($user, $course);

        $response = $this->actingAs($user)->post(route('checkout.store', $course), $this->demoPayment($token));
        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $response->assertRedirect(route('checkout.success', $order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['provider' => 'demo', 'status' => 'paid', 'amount' => 19, 'currency' => 'USD']);
        $this->assertDatabaseHas('enrollments', ['user_id' => $user->id, 'product_id' => $course->id]);
        $safePersistence = json_encode([$order->toArray(), $payment->toArray()]);
        $this->assertStringNotContainsString('4242424242424242', $safePersistence);
        $this->assertStringNotContainsString('12/30', $safePersistence);
        $this->assertStringNotContainsString('123', $safePersistence);
    }

    public function test_invalid_demo_card_values_are_not_flushed_to_session_or_persisted(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $token = $this->checkoutToken($user, $course);

        $this->actingAs($user)->from(route('checkout.show', $course))->post(route('checkout.store', $course), [
            'checkout_token' => $token,
            'card_number' => '5555 5555 5555 4444',
            'expiry' => '01/40',
            'cvv' => '999',
        ])->assertRedirect(route('checkout.show', $course))->assertSessionHasErrors(['card_number', 'expiry', 'cvv']);

        $oldInput = session()->getOldInput();
        $this->assertArrayNotHasKey('card_number', $oldInput);
        $this->assertArrayNotHasKey('expiry', $oldInput);
        $this->assertArrayNotHasKey('cvv', $oldInput);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_individual_checkout_is_idempotent_and_owned_course_cta_changes(): void
    {
        $user = User::factory()->create();
        $course = $this->course();
        $token = $this->checkoutToken($user, $course);
        $payload = $this->demoPayment($token);

        $this->actingAs($user)->post(route('checkout.store', $course), $payload);
        $this->actingAs($user)->post(route('checkout.store', $course), $payload);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('enrollments', 1);
        $this->actingAs($user)->get(route('courses.show', $course))->assertSee('ACCESS COURSE');
        $this->actingAs($user)->get(route('checkout.show', $course))->assertRedirect(route('learn.show', $course));
    }

    public function test_package_purchase_creates_one_order_one_payment_and_all_ten_enrollments(): void
    {
        $user = User::factory()->create();
        $package = $this->package();
        $token = $this->checkoutToken($user, $package);

        $response = $this->actingAs($user)->post(route('checkout.store', $package), $this->demoPayment($token));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['amount' => 150, 'currency' => 'USD', 'status' => 'paid']);
        $this->assertDatabaseCount('enrollments', 10);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('checkout.success', $order));
        $this->actingAs($user)->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('10 Courses Unlocked')
            ->assertSee('Go to My Courses');
    }

    public function test_package_creates_only_missing_enrollments_when_two_courses_are_owned(): void
    {
        $user = User::factory()->create();
        $courses = Product::query()->where('type', 'course')->orderBy('id')->get();
        foreach ($courses->take(2) as $course) {
            Enrollment::query()->create(['user_id' => $user->id, 'product_id' => $course->id, 'status' => 'active', 'progress_percent' => 0, 'enrolled_at' => now()]);
        }

        $package = $this->package();
        $token = $this->checkoutToken($user, $package);
        $this->actingAs($user)->post(route('checkout.store', $package), $this->demoPayment($token));

        $this->assertDatabaseCount('enrollments', 10);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_fully_owned_package_shows_access_my_courses_and_cannot_be_bought_again(): void
    {
        $user = User::factory()->create();
        foreach (Product::query()->where('type', 'course')->get() as $course) {
            Enrollment::query()->create(['user_id' => $user->id, 'product_id' => $course->id, 'status' => 'active', 'progress_percent' => 0, 'enrolled_at' => now()]);
        }

        $package = $this->package();
        $this->actingAs($user)->get(route('packages.show', $package))->assertSee('ACCESS MY COURSES');
        $this->actingAs($user)->get(route('checkout.show', $package))->assertRedirect(route('my-courses'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_learning_access_requires_authentication_and_enrollment(): void
    {
        $course = $this->course();
        $this->get(route('learn.show', $course))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('learn.show', $course))->assertRedirect(route('courses.show', $course));

        Enrollment::query()->create(['user_id' => $user->id, 'product_id' => $course->id, 'status' => 'active', 'progress_percent' => 0, 'enrolled_at' => now()]);
        $this->actingAs($user)->get(route('learn.show', $course))->assertOk()->assertSee('01 Start Here')->assertSee('02 Full Course')->assertSee('03 Course Recap');
    }

    public function test_protected_video_and_slides_require_enrollment_and_video_supports_ranges(): void
    {
        Storage::fake('local');
        $course = $this->course();
        Storage::disk('local')->put('courses/'.$course->slug.'/video/course.mp4', '0123456789');
        Storage::disk('local')->put('courses/'.$course->slug.'/slides/course-slides.pptx', 'pptx-test');
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('course-media.video', $course))->assertForbidden();
        $this->actingAs($user)->get(route('course-media.slides', $course))->assertForbidden();

        Enrollment::query()->create(['user_id' => $user->id, 'product_id' => $course->id, 'status' => 'active', 'progress_percent' => 0, 'enrolled_at' => now()]);
        $this->actingAs($user)->call('GET', route('course-media.video', $course), server: ['HTTP_RANGE' => 'bytes=0-3'])
            ->assertStatus(206)->assertHeader('Content-Range', 'bytes 0-3/10');
        $this->actingAs($user)->get(route('course-media.slides', $course))->assertOk();
        $this->assertFalse(file_exists(public_path('storage/courses/'.$course->slug.'/video/course.mp4')));
    }

    public function test_my_courses_only_lists_enrolled_course_products(): void
    {
        $user = User::factory()->create();
        $owned = $this->course();
        $other = Product::query()->where('type', 'course')->whereKeyNot($owned->id)->firstOrFail();
        Enrollment::query()->create(['user_id' => $user->id, 'product_id' => $owned->id, 'status' => 'active', 'progress_percent' => 0, 'enrolled_at' => now()]);

        $this->actingAs($user)->get(route('my-courses'))
            ->assertOk()->assertSee($owned->name)->assertDontSee($other->name)->assertDontSee($this->package()->name);
    }

    public function test_catalogue_has_only_canonical_active_names_slugs_and_package_relations(): void
    {
        $this->assertDatabaseHas('products', ['sku' => 'SL-AI-001', 'name' => 'AI Prompt Engineering 101', 'slug' => 'ai-prompt-engineering-101', 'status' => 'active']);
        $this->assertDatabaseHas('products', ['sku' => 'SL-EP-006', 'name' => 'E-Payment Fundamentals', 'slug' => 'e-payment-fundamentals', 'status' => 'active']);
        $this->assertDatabaseMissing('products', ['type' => 'course', 'name' => 'Artificial Intelligence (AI)', 'status' => 'active']);
        $this->assertDatabaseMissing('products', ['type' => 'course', 'name' => 'E-Payment Systems', 'status' => 'active']);
        $this->assertSame(10, $this->package()->bundleProducts()->count());
        $this->assertDatabaseCount('course_contents', 10);
    }

    /** @return array<string, string> */
    private function demoPayment(string $token): array
    {
        return ['checkout_token' => $token, 'card_number' => '4242 4242 4242 4242', 'expiry' => '12/30', 'cvv' => '123'];
    }

    private function checkoutToken(User $user, Product $product): string
    {
        $this->actingAs($user)->get(route('checkout.show', $product))->assertOk();

        return (string) session('checkout_tokens.'.$product->id);
    }

    private function course(): Product
    {
        return Product::query()->where('sku', 'SL-AI-001')->firstOrFail();
    }

    private function package(): Product
    {
        return Product::query()->where('sku', 'SL-PACKAGE-ALL-10')->firstOrFail();
    }
}
