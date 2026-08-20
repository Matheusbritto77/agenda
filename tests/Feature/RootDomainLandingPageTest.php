<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RootDomainLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_host_always_renders_the_landing_page_and_never_the_booking_view(): void
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
        $response->assertDontSee('Agendamento de Serviços - Agendae', false);
    }
}
