<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke Arena');
    }

    public function test_register_page_can_be_rendered(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Buat Akun Gaming');
    }

    public function test_public_registration_always_creates_role_user(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'TestGamer',
            'email'                 => 'gamer@example.com',
            'password'              => 'secretpassword123',
            'password_confirmation' => 'secretpassword123',
            'role'                  => 'admin', // Attempted privilege escalation
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'gamer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('user', $user->role);
        $this->assertTrue($user->is_active);

        // Verify UserSetting created automatically
        $setting = UserSetting::where('user_id', $user->id)->first();
        $this->assertNotNull($setting);
        $this->assertEquals(80, $setting->master_volume);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'gamer2@example.com',
            'password' => 'secret12345',
            'role'     => 'user',
            'is_active'=> true,
        ]);

        $response = $this->post('/login', [
            'email'    => 'gamer2@example.com',
            'password' => 'secret12345',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email'    => 'banned@example.com',
            'password' => 'secret12345',
            'role'     => 'user',
            'is_active'=> false,
        ]);

        $response = $this->post('/login', [
            'email'    => 'banned@example.com',
            'password' => 'secret12345',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create([
            'role'      => 'user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_admin_user_can_access_admin_panel(): void
    {
        $admin = new User();
        $admin->name = 'AdminGamer';
        $admin->email = 'admin@example.com';
        $admin->password = 'adminsecret123';
        $admin->role = 'admin';
        $admin->is_active = true;
        $admin->save();

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Admin & Moderation Panel');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'role'      => 'user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
