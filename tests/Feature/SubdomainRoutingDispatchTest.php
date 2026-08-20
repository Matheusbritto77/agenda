<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubdomainRoutingDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_request_opens_the_public_landing_page(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        User::factory()->create([
            'subdomain' => 'barbearia-vip',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        Service::create([
            'name' => 'Corte Premium',
            'description' => null,
            'price' => 100,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $response = $this->get('http://agendae.app/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Welcome'));
    }

    public function test_valid_subdomain_opens_the_tenant_booking_page(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $tenant = User::factory()->create([
            'subdomain' => 'barbearia-vip',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        Service::create([
            'name' => 'Barba Deluxe',
            'description' => null,
            'price' => 80,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->app->forgetInstance('bookingTenant');

        $response = $this->get('http://barbearia-vip.agendae.app/');

        $response->assertOk();
        $this->assertTrue($this->app->bound('bookingTenant'));
        $this->assertSame($tenant->id, $this->app->make('bookingTenant')->id);
        $response->assertInertia(fn (Assert $page) => $page->component('Client/Booking'));
    }

    public function test_nonexistent_subdomain_returns_http_404(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        User::factory()->create([
            'subdomain' => 'barbearia-vip',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        Service::create([
            'name' => 'Corte',
            'description' => null,
            'price' => 60,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->get('http://inexistente.agendae.app/');

        $response->assertNotFound();
    }
}
