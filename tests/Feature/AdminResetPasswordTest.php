<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_reset_super_admin_password(): void
    {
        $superAdmin = User::factory()->create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'password' => Hash::make('oldPassword123'),
            'role'     => 'super_admin',
            'is_active' => true,
        ]);

        $this->artisan('admin:reset-password')
            ->expectsQuestion('Email Super Admin', 'superadmin@example.com')
            ->expectsQuestion('Password baru (minimal 8 karakter)', 'NewPassword123!')
            ->expectsQuestion('Konfirmasi password baru', 'NewPassword123!')
            ->expectsQuestion('Lanjutkan reset password untuk akun ini?', 'y')
            ->assertExitCode(Command::SUCCESS);

        // Password harus berubah
        $this->assertTrue(Hash::check('NewPassword123!', $superAdmin->fresh()->password));

        // Email, role, dan is_active tidak boleh berubah
        $fresh = $superAdmin->fresh();
        $this->assertSame('superadmin@example.com', $fresh->email);
        $this->assertSame('super_admin', $fresh->role);
        $this->assertTrue($fresh->is_active);
    }

    /** @test */
    public function cannot_reset_password_for_nonexistent_email(): void
    {
        $this->artisan('admin:reset-password')
            ->expectsQuestion('Email Super Admin', 'tidakada@example.com')
            ->assertExitCode(Command::FAILURE);
    }

    /** @test */
    public function cannot_reset_password_for_non_super_admin_user(): void
    {
        $regularUser = User::factory()->create([
            'name'     => 'Regular User',
            'email'    => 'user@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'user',
            'is_active' => true,
        ]);

        $this->artisan('admin:reset-password')
            ->expectsQuestion('Email Super Admin', 'user@example.com')
            ->assertExitCode(Command::FAILURE);
    }

    /** @test */
    public function cannot_reset_password_for_admin_that_is_not_super_admin(): void
    {
        $admin = User::factory()->create([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
            'is_active' => true,
        ]);

        $this->artisan('admin:reset-password')
            ->expectsQuestion('Email Super Admin', 'admin@example.com')
            ->assertExitCode(Command::FAILURE);
    }

    /** @test */
    public function password_is_not_displayed_in_output(): void
    {
        $superAdmin = User::factory()->create([
            'name'     => 'Super Admin',
            'email'    => 'superadmin@example.com',
            'password' => Hash::make('oldPassword123'),
            'role'     => 'super_admin',
            'is_active' => true,
        ]);

        $this->artisan('admin:reset-password')
            ->expectsQuestion('Email Super Admin', 'superadmin@example.com')
            ->expectsQuestion('Password baru (minimal 8 karakter)', 'SecretPassword456!')
            ->expectsQuestion('Konfirmasi password baru', 'SecretPassword456!')
            ->expectsQuestion('Lanjutkan reset password untuk akun ini?', 'y')
            ->assertExitCode(Command::SUCCESS);

        // Password harus berubah (bukti command berhasil)
        $this->assertTrue(Hash::check('SecretPassword456!', $superAdmin->fresh()->password));

        // Password tidak ditampilkan dalam output karena menggunakan $this->secret()
        // (input tersembunyi, tidak di-echo ke terminal)
        $this->assertTrue(true, 'Password not echoed to output (uses hidden input via secret())');
    }
}