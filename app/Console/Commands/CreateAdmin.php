<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'ainchors:create-admin
                            {--email= : Administrator email address}';

    protected $description = 'Create or recover the single protected AINCHORS administrator.';

    public function handle(): int
    {
        $soleAdminEmail = $this->soleAdminEmail();

        if ($soleAdminEmail === null) {
            return self::FAILURE;
        }

        $email = $this->normalizedEmail();

        if ($email === null) {
            return self::FAILURE;
        }

        if ($email !== $soleAdminEmail) {
            $this->error("The only permitted administrator account is {$soleAdminEmail}.");

            return self::FAILURE;
        }

        $existing = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing !== null) {
            return $this->promoteExisting($existing);
        }

        if (User::query()->where('role', 'admin')->exists()) {
            $this->error('An administrator account already exists. A second administrator cannot be created.');

            return self::FAILURE;
        }

        $fullName = $this->fullName();

        if ($fullName === null) {
            return self::FAILURE;
        }

        $password = $this->password();

        if ($password === null) {
            return self::FAILURE;
        }

        $admin = DB::transaction(function () use ($email, $fullName, $password): User {
            return User::query()->create([
                'role' => 'admin',
                'full_name' => $fullName,
                'email' => $email,
                'password' => Hash::make($password),
                'status' => 'active',
            ]);
        });

        $this->info("Administrator created for {$admin->email}.");

        return self::SUCCESS;
    }

    private function soleAdminEmail(): ?string
    {
        $email = strtolower(trim((string) config('ainchors.admin.email', '')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('AINCHORS_ADMIN_EMAIL must contain a valid administrator email address.');

            return null;
        }

        return $email;
    }

    private function normalizedEmail(): ?string
    {
        $email = trim((string) ($this->option('email') ?: $this->ask('Administrator email')));
        $email = strtolower($email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid administrator email address.');

            return null;
        }

        return $email;
    }

    private function fullName(): ?string
    {
        $fullName = trim((string) $this->ask('Administrator full name'));

        if ($fullName === '' || strlen($fullName) > 255) {
            $this->error('Administrator full name is required and must contain at most 255 characters.');

            return null;
        }

        return $fullName;
    }

    private function password(): ?string
    {
        $password = (string) $this->secret('Administrator password');
        $confirmation = (string) $this->secret('Confirm administrator password');

        if ($password === '' || strlen($password) < 12) {
            $this->error('Administrator passwords must contain at least 12 characters.');

            return null;
        }

        if (! hash_equals($password, $confirmation)) {
            $this->error('Password confirmation does not match.');

            return null;
        }

        return $password;
    }

    private function promoteExisting(User $user): int
    {
        if ($user->isAuthorizedAdmin()) {
            $this->warn("{$user->email} is already the configured administrator. No changes were made.");

            return self::SUCCESS;
        }

        if ($user->isAdmin()) {
            $this->error('The existing administrator record is not authorized by the configured administrator identity.');

            return self::FAILURE;
        }

        if (User::query()->where('role', 'admin')->exists()) {
            $this->error('An administrator account already exists. This account cannot be promoted.');

            return self::FAILURE;
        }

        if (! $this->confirm("Promote existing account {$user->email} to administrator?", false)) {
            $this->warn('Administrator promotion cancelled.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill(['role' => 'admin', 'status' => 'active'])->save();
        });

        $this->info("Existing account {$user->email} has been promoted to administrator.");

        return self::SUCCESS;
    }
}
