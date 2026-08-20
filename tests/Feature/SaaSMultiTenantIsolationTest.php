<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SaaSMultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_url_opens_the_saas_landing_page(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        User::factory()->create([
            'subdomain' => 'barbearia-a',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $response = $this->get('https://agendae.app/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Welcome'));
        $response->assertDontSee('Agendamento de Serviços - Agendae', false);
    }

    public function test_tenant_a_subdomain_lists_only_tenant_a_services(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $tenantA = User::factory()->create([
            'subdomain' => 'barbearia-a',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $tenantB = User::factory()->create([
            'subdomain' => 'barbearia-b',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        Service::create([
            'user_id' => $tenantA->id,
            'name' => 'Corte do A',
            'description' => null,
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        Service::create([
            'user_id' => $tenantB->id,
            'name' => 'Corte do B',
            'description' => null,
            'price' => 70,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $response = $this->get('http://barbearia-a.agendae.app/');

        $response->assertOk();
        $response->assertSee('Corte do A', false);
        $response->assertDontSee('Corte do B', false);
    }

    public function test_tenant_b_does_not_leak_tenant_a_data(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $tenantA = User::factory()->create([
            'subdomain' => 'barbearia-a',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $tenantB = User::factory()->create([
            'subdomain' => 'barbearia-b',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        Service::create([
            'user_id' => $tenantA->id,
            'name' => 'Barba do A',
            'description' => null,
            'price' => 35,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        Service::create([
            'user_id' => $tenantB->id,
            'name' => 'Barba do B',
            'description' => null,
            'price' => 40,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->get('http://barbearia-b.agendae.app/');

        $response->assertOk();
        $response->assertSee('Barba do B', false);
        $response->assertDontSee('Barba do A', false);
    }
}
