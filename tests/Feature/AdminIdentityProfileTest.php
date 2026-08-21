<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIdentityProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_configured_administrator_email_cannot_drift_through_admin_or_public_profile_forms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $configuredEmail = (string) config('ainchors.admin.email');

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'full_name' => $admin->full_name,
                'email' => 'changed-admin@example.test',
            ])
            ->assertRedirect(route('admin.users.edit', $admin))
            ->assertSessionHasErrors('email');

        $this->assertSame($configuredEmail, $admin->fresh()->email);

        $this->actingAs($admin)
            ->from(route('profile'))
            ->patch(route('profile.update'), [
                'full_name' => $admin->full_name,
                'email' => 'changed-from-profile@example.test',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors('email');

        $this->assertSame($configuredEmail, $admin->fresh()->email);
        $this->assertTrue($admin->fresh()->isAuthorizedAdmin());
    }
}
