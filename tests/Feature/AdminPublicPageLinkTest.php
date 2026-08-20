<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class AdminPublicPageLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_public_page_link_uses_the_saved_subdomain_url(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $admin = User::factory()->create([
            'subdomain' => 'barbearia-vip',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('publicBookingUrl', 'https://barbearia-vip.agendae.app/')
        );
    }

    public function test_admin_public_page_link_uses_custom_domain_when_active(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $admin = User::factory()->create([
            'subdomain' => 'barbearia-vip',
            'custom_domain' => 'agenda.minhaempresa.com.br',
            'active_domain_type' => 'custom',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('publicBookingUrl', 'https://agenda.minhaempresa.com.br/')
        );
    }
}
