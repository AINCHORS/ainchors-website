<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_registration_requires_unchecked_terms_consent(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'full_name' => 'No Consent',
                'email' => 'no-consent@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('terms');

        $this->assertDatabaseMissing('users', ['email' => 'no-consent@example.test']);
    }

    public function test_registration_hashes_the_password_assigns_a_normal_user_and_authenticates_them(): void
    {
        $this->post(route('register.store'), [
            'full_name' => 'New Learner',
            'email' => 'new-learner@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ])->assertRedirect(route('my-courses'))
            ->assertSessionHas('show_profile_completion', true);

        $user = User::query()->where('email', 'new-learner@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('New Learner', $user->full_name);
        $this->assertSame('New', $user->first_name);
        $this->assertSame('Learner', $user->last_name);
        $this->assertNull($user->country);
        $this->assertNull($user->phone);
        $this->assertNull($user->postal_code);
        $this->assertSame('user', $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotSame('password123', $user->password);
    }

    public function test_active_user_can_log_in_and_log_out_without_exposing_their_password(): void
    {
        $user = User::factory()->create(['email' => 'learner@example.test']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ])->assertRedirect(route('my-courses'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_incomplete_user_sees_profile_completion_prompt_after_login_and_can_complete_it(): void
    {
        $user = User::factory()->create([
            'first_name' => 'New',
            'last_name' => 'Learner',
            'date_of_birth' => null,
            'phone' => null,
            'country' => null,
            'address_line_1' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHas('show_profile_completion', true);

        $this->get(route('my-courses'))
            ->assertOk()
            ->assertSee('Complete your profile')
            ->assertSee('Maybe Later')
            ->assertDontSee('Date of Birth')
            ->assertDontSee('Home Address')
            ->assertDontSee('Postal Code');

        $this->get(route('my-courses'))
            ->assertOk()
            ->assertDontSee('Complete your profile');

        $this->patch(route('profile.complete'), $this->personalDetails('New', 'Learner'))
            ->assertSessionHas('profile_completion_success');

        $this->assertTrue($user->fresh()->hasBasicProfile());
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.test',
            'status' => 'inactive',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_failed_login_never_flashes_the_submitted_password_to_the_session(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'unknown@example.test',
                'password' => 'do-not-flash-this-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertArrayNotHasKey('password', session()->getOldInput());
    }

    public function test_login_and_registration_submissions_are_rate_limited(): void
    {
        $loginEmail = 'limited-login@example.test';

        foreach (range(1, 5) as $attempt) {
            $this->from(route('login'))
                ->post(route('login.store'), ['email' => $loginEmail, 'password' => 'incorrect-password'])
                ->assertRedirect(route('login'));
        }

        $this->post(route('login.store'), ['email' => $loginEmail, 'password' => 'incorrect-password'])
            ->assertStatus(429);

        foreach (range(1, 5) as $attempt) {
            $this->from(route('register'))
                ->post(route('register.store'), [])
                ->assertRedirect(route('register'));
        }

        $this->post(route('register.store'), [])->assertStatus(429);
    }

    public function test_password_reset_uses_laravels_broker_and_changes_the_password_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.test']);

        $this->get(route('password.request'))->assertOk();
        $this->get(route('password.reset', ['token' => 'example-reset-token']))->assertOk();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        $email = 'limited-reset@example.test';

        foreach (range(1, 3) as $attempt) {
            $this->from(route('password.request'))
                ->post(route('password.email'), ['email' => $email])
                ->assertRedirect(route('password.request'));
        }

        $this->post(route('password.email'), ['email' => $email])->assertStatus(429);
    }

    public function test_profile_and_purchase_history_require_authentication(): void
    {
        $this->get(route('profile'))->assertRedirect(route('login'));
        $this->get(route('purchase-history'))->assertRedirect(route('login'));
    }

    public function test_user_can_update_only_their_supported_profile_fields(): void
    {
        $user = User::factory()->create(['email' => 'before@example.test']);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                ...$this->personalDetails('Updated', 'Learner'),
                'email' => 'after@example.test',
            ])
            ->assertSessionHas('profile_success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'full_name' => 'Updated Learner',
            'email' => 'after@example.test',
            'first_name' => 'Updated',
            'last_name' => 'Learner',
            'country' => 'Malaysia',
            'address_line_1' => 'Level 13A, Wisma Mont Kiara',
        ]);
    }

    public function test_password_update_requires_the_current_password_and_hashes_the_replacement(): void
    {
        $user = User::factory()->create();
        $originalHash = $user->password;

        $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.password.update'), [
                'current_password' => 'not-the-current-password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors('current_password');

        $this->assertSame($originalHash, $user->fresh()->password);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertSessionHas('password_success');

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_my_courses_and_purchase_history_are_isolated_to_the_authenticated_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $courseA = $this->product('user-a-course', 'User A Course');
        $courseB = $this->product('user-b-course', 'User B Course');

        Enrollment::query()->create([
            'user_id' => $userA->id,
            'product_id' => $courseA->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        Enrollment::query()->create([
            'user_id' => $userB->id,
            'product_id' => $courseB->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $orderA = $this->completedOrderFor($userA, $courseA, 'AIN-TEST-USER-A');
        $orderB = $this->completedOrderFor($userB, $courseB, 'AIN-TEST-USER-B');

        $this->actingAs($userA)->get(route('my-courses'))
            ->assertOk()
            ->assertSee($courseA->name)
            ->assertDontSee($courseB->name);

        $this->actingAs($userA)->get(route('purchase-history'))
            ->assertOk()
            ->assertSee($orderA->order_number)
            ->assertSee($courseA->name)
            ->assertDontSee($orderB->order_number)
            ->assertDontSee($courseB->name);

        $this->actingAs($userA)
            ->get(route('checkout.success', $orderB))
            ->assertNotFound();
    }

    private function product(string $slug, string $name): Product
    {
        return Product::query()->create([
            'type' => 'course',
            'sku' => 'AUTH-'.strtoupper(str_replace('-', '_', $slug)),
            'name' => $name,
            'slug' => $slug,
            'price' => 19,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'status' => 'active',
        ]);
    }

    /** @return array<string, string> */
    private function personalDetails(string $firstName, string $lastName): array
    {
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => '1990-05-15',
            'phone' => '+60 12 345 6789',
            'country' => 'Malaysia',
            'address_line_1' => 'Level 13A, Wisma Mont Kiara',
            'address_line_2' => '',
            'city' => 'Kuala Lumpur',
            'state' => 'Wilayah Persekutuan Kuala Lumpur',
            'postal_code' => '50480',
        ];
    }

    private function completedOrderFor(User $user, Product $product, string $orderNumber): Order
    {
        $order = Order::query()->create([
            'order_number' => $orderNumber,
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
        $order->payments()->create([
            'provider' => 'demo',
            'amount' => 19,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $order;
    }
}
