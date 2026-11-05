<?php

namespace Tests\Feature;

use App\Models\ModerationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_make_super_admin_command_creates_super_admin_successfully(): void
    {
        $this->artisan('make:super-admin')
            ->expectsQuestion('Nama Lengkap / Username Super Admin', 'RootMaster')
            ->expectsQuestion('Email Super Admin', 'root@soundboard.test')
            ->expectsQuestion('Password (minimal 8 karakter)', 'SuperSecretPassword123')
            ->expectsQuestion('Konfirmasi Password', 'SuperSecretPassword123')
            ->expectsConfirmation('Lanjutkan pembuatan akun Super Admin ini?', 'yes')
            ->assertExitCode(0);

        $superAdmin = User::where('email', 'root@soundboard.test')->first();
        $this->assertNotNull($superAdmin);
        $this->assertEquals('super_admin', $superAdmin->role);
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->isAdmin()); // Super Admin inherits admin
        $this->assertTrue($superAdmin->is_active);

        // Assert audit log was recorded
        $log = ModerationLog::where('target_id', $superAdmin->id)->where('action', 'create_super_admin')->first();
        $this->assertNotNull($log);
    }

    public function test_super_admin_inherits_admin_access(): void
    {
        $superAdmin = User::factory()->create([
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin');
        $response->assertStatus(200);

        $response = $this->actingAs($superAdmin)->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('Daftar Akun Pengguna');
    }

    public function test_regular_admin_cannot_access_super_admin_role_management(): void
    {
        $admin = User::factory()->create([
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $targetUser = User::factory()->create([
            'role'      => 'user',
            'is_active' => true,
        ]);

        // Admin biasa mencoba mengubah role user menjadi admin
        $response = $this->actingAs($admin)->patch("/admin/users/{$targetUser->id}/role", [
            'role' => 'admin',
        ]);

        // Middleware super_admin harus mencegat
        $response->assertRedirect('/admin');
        $response->assertSessionHas('error');

        // Pastikan role di database TIDAK berubah
        $this->assertEquals('user', $targetUser->fresh()->role);
    }

    public function test_regular_admin_cannot_deactivate_super_admin(): void
    {
        $admin = User::factory()->create([
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $superAdmin = User::factory()->create([
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/users/{$superAdmin->id}/status");
        $response->assertSessionHas('error');

        // Pastikan status super admin tetap aktif
        $this->assertTrue($superAdmin->fresh()->is_active);
    }

    public function test_super_admin_can_promote_user_to_admin_and_creates_audit_log(): void
    {
        $superAdmin = User::factory()->create([
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $targetUser = User::factory()->create([
            'role'      => 'user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->patch("/admin/users/{$targetUser->id}/role", [
            'role'   => 'admin',
            'reason' => 'Dipromosikan menjadi moderator',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verifikasi perubahan role
        $this->assertEquals('admin', $targetUser->fresh()->role);

        // Verifikasi audit log tercatat
        $log = ModerationLog::where('target_id', $targetUser->id)
            ->where('action', 'change_role')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($superAdmin->id, $log->admin_id);
        $this->assertEquals('user', $log->meta['old_role']);
        $this->assertEquals('admin', $log->meta['new_role']);
    }

    public function test_anti_lockout_prevents_last_super_admin_from_being_demoted(): void
    {
        $superAdmin = User::factory()->create([
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        // Hanya ada 1 super admin aktif di database
        $this->assertEquals(1, User::activeSuperAdminCount());

        $response = $this->actingAs($superAdmin)->patch("/admin/users/{$superAdmin->id}/role", [
            'role' => 'admin', // Mencoba mendemosi diri sendiri padahal satu-satunya super admin
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('super_admin', $superAdmin->fresh()->role);
    }

    public function test_anti_lockout_prevents_last_super_admin_from_being_deactivated(): void
    {
        $superAdmin = User::factory()->create([
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $this->assertEquals(1, User::activeSuperAdminCount());

        $response = $this->actingAs($superAdmin)->patch("/admin/users/{$superAdmin->id}/status");
        $response->assertSessionHas('error');
        $this->assertTrue($superAdmin->fresh()->is_active);
    }

    public function test_super_admin_can_be_demoted_if_another_super_admin_exists(): void
    {
        $superAdmin1 = User::factory()->create([
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $superAdmin2 = User::factory()->create([
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $this->assertEquals(2, User::activeSuperAdminCount());

        // Super Admin 1 mendemosi Super Admin 2 (aman karena masih ada Super Admin 1)
        $response = $this->actingAs($superAdmin1)->patch("/admin/users/{$superAdmin2->id}/role", [
            'role' => 'admin',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('admin', $superAdmin2->fresh()->role);
        $this->assertEquals(1, User::activeSuperAdminCount());
    }
}
