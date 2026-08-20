<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdomainAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_reports_when_a_subdomain_is_taken_and_suggests_an_alternative(): void
    {
        User::factory()->create([
            'subdomain' => 'studio',
        ]);

        $response = $this->getJson('/api/subdomains/availability?subdomain=studio');

        $response->assertOk();
        $response->assertJson([
            'available' => false,
            'normalized_subdomain' => 'studio',
        ]);
        $response->assertJsonPath('suggested_subdomain', 'studio-2');
    }

    public function test_api_ignores_the_current_user_when_editing_its_own_subdomain(): void
    {
        $admin = User::factory()->create([
            'subdomain' => 'barbearia-vip',
        ]);

        TeamMember::query()->create([
            'user_id' => $admin->id,
            'name' => 'Profissional Um',
            'job_title' => 'Profissional',
            'role_id' => 'professional',
            'subdomain' => 'profissional-um',
            'custom_domain' => null,
            'email' => 'prof1@example.com',
            'phone' => null,
            'avatar_url' => null,
            'bio' => null,
            'is_active' => true,
            'services' => null,
            'business_hours' => null,
            'commission_rate' => 0,
            'service_commissions' => null,
        ]);

        $response = $this->getJson('/api/subdomains/availability?subdomain=barbearia-vip&ignore_user_id=' . $admin->id);

        $response->assertOk();
        $response->assertJson([
            'available' => true,
            'normalized_subdomain' => 'barbearia-vip',
            'suggested_subdomain' => 'barbearia-vip',
        ]);
    }
}
