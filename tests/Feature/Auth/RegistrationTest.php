<?php

namespace Tests\Feature\Auth;

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_registration_uses_the_next_available_subdomain_when_the_first_choice_is_taken(): void
    {
        User::factory()->create([
            'subdomain' => 'test-user',
        ]);

        $owner = User::factory()->create();

        TeamMember::query()->create([
            'user_id' => $owner->id,
            'name' => 'Clash Example',
            'job_title' => 'Profissional',
            'role_id' => 'professional',
            'subdomain' => 'test-user-2',
            'custom_domain' => null,
            'email' => 'clash@example.com',
            'phone' => null,
            'avatar_url' => null,
            'bio' => null,
            'is_active' => true,
            'services' => null,
            'business_hours' => null,
            'commission_rate' => 0,
            'service_commissions' => null,
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'another@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'another@example.com',
            'subdomain' => 'test-user-3',
        ]);
    }
}
