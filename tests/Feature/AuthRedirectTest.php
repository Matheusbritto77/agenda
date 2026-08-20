<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_registration_redirects_directly_to_admin_dashboard(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Novo Usuário',
            'email' => 'novo.usuario@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'novo.usuario@example.com',
        ]);
    }

    public function test_login_redirects_directly_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin-login@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
