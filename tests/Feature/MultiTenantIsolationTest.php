<?php

namespace Tests\Feature;

use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_a_public_catalog_shows_only_tenant_a_services(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $tenantA = User::factory()->create([
            'subdomain' => 'tenant-a',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $tenantB = User::factory()->create([
            'subdomain' => 'tenant-b',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        Service::create([
            'user_id' => $tenantA->id,
            'name' => 'Serviço A',
            'description' => null,
            'price' => 100,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        Service::create([
            'user_id' => $tenantB->id,
            'name' => 'Serviço B',
            'description' => null,
            'price' => 120,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->get('http://tenant-a.agendae.app/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Booking')
            ->has('services', 1)
            ->where('services.0.name', 'Serviço A')
        );
    }

    public function test_tenant_a_public_agenda_uses_only_tenant_a_hours_and_intervals(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $tenantA = User::factory()->create([
            'subdomain' => 'tenant-a',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $tenantB = User::factory()->create([
            'subdomain' => 'tenant-b',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $serviceA = Service::create([
            'user_id' => $tenantA->id,
            'name' => 'Corte A',
            'description' => null,
            'price' => 90,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        Service::create([
            'user_id' => $tenantB->id,
            'name' => 'Corte B',
            'description' => null,
            'price' => 110,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        BusinessHour::create([
            'user_id' => $tenantA->id,
            'day_of_week' => 1,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'label' => 'Tenant A Manhã',
            'slot_duration_minutes' => 45,
            'is_active' => true,
        ]);

        BusinessHour::create([
            'user_id' => $tenantB->id,
            'day_of_week' => 1,
            'opens_at' => '13:00:00',
            'closes_at' => '18:00:00',
            'label' => 'Tenant B Tarde',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->getJson('http://tenant-a.agendae.app/available-slots?service_id=' . $serviceA->id . '&date=2026-08-24');

        $response->assertOk();
        $response->assertJsonPath('service_id', $serviceA->id);
        $response->assertJsonPath('slots', [
            '08:00',
            '08:45',
            '09:30',
            '10:15',
            '11:00',
        ]);
    }
}
