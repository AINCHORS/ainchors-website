<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['ainchors.admin.email' => 'admin@example.test']);
    }

    public function test_admin_can_set_temporary_password_for_regular_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.password.reset', $user), [
                'password' => 'Temporary123!',
                'password_confirmation' => 'Temporary123!',
            ]);

        $response
            ->assertRedirect(route('admin.users.show', $user))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertTrue(Hash::check('Temporary123!', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertNotNull($user->remember_token);

        $audit = AdminAuditLog::query()->latest('id')->firstOrFail();
        $this->assertSame('USER_PASSWORD_RESET_BY_ADMIN', $audit->action);
        $this->assertSame($admin->id, $audit->admin_user_id);
        $this->assertSame((string) $user->id, $audit->entity_id);
        $this->assertArrayNotHasKey('password', $audit->before_values ?? []);
        $this->assertArrayNotHasKey('password', $audit->after_values ?? []);
    }

    public function test_normal_user_cannot_reset_another_users_password(): void
    {
        $actor = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $target = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $originalPassword = $target->password;

        $this->actingAs($actor)
            ->post('/admin/users/'.$target->id.'/reset-password', [
                'password' => 'Temporary123!',
                'password_confirmation' => 'Temporary123!',
            ])
            ->assertForbidden();

        $this->assertSame($originalPassword, $target->fresh()->password);
    }

    public function test_admin_cannot_use_user_management_to_reset_an_administrator_password(): void
    {
        $admin = $this->admin();
        $originalPassword = $admin->password;

        $this->actingAs($admin)
            ->from(route('admin.users.show', $admin))
            ->post(route('admin.users.password.reset', $admin), [
                'password' => 'Temporary123!',
                'password_confirmation' => 'Temporary123!',
            ])
            ->assertRedirect(route('admin.users.show', $admin))
            ->assertSessionHasErrors('password');

        $this->assertSame($originalPassword, $admin->fresh()->password);
    }

    public function test_user_with_temporary_password_is_forced_to_profile_until_password_is_changed(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'password' => 'Temporary123!',
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get(route('my-courses'))
            ->assertRedirect(route('profile'));

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Password change required');

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'Temporary123!',
                'password' => 'MyOwnPassword123!',
                'password_confirmation' => 'MyOwnPassword123!',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('MyOwnPassword123!', $user->password));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'admin@example.test',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
}
