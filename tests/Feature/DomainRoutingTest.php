<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolvePublicBookingTenant;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DomainRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_prefers_custom_domain_tenant_when_host_matches_both_types(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'http://agendae.app',
        ]);

        $subdomainTenant = User::factory()->create([
            'subdomain' => 'studio',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $customTenant = User::factory()->create([
            'subdomain' => 'other-studio',
            'custom_domain' => 'studio.agendae.app',
            'active_domain_type' => 'custom',
        ]);

        Service::create([
            'name' => 'Corte',
            'description' => null,
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->app->forgetInstance('bookingTenant');

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'studio.agendae.app']);

        $response = app(ResolvePublicBookingTenant::class)->handle($request, function (Request $request) {
            return response()->json([
                'booking_tenant_id' => $request->attributes->get('bookingTenant')?->id,
            ]);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($customTenant->id, $response->getData(true)['booking_tenant_id']);
    }

    public function test_public_booking_resolves_tenant_from_local_subdomain_even_with_production_app_domain(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'http://agendae.app',
        ]);

        $tenant = User::factory()->create([
            'subdomain' => 'seu-subdominio',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        Service::create([
            'name' => 'Corte',
            'description' => null,
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->app->forgetInstance('bookingTenant');

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'seu-subdominio.localhost']);

        $response = app(ResolvePublicBookingTenant::class)->handle($request, function (Request $request) {
            return response()->json([
                'booking_tenant_id' => $request->attributes->get('bookingTenant')?->id,
            ]);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($tenant->id, $response->getData(true)['booking_tenant_id']);
    }

    public function test_public_booking_keeps_custom_domain_resolution_working(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'http://agendae.app',
        ]);

        $tenant = User::factory()->create([
            'subdomain' => 'studio',
            'custom_domain' => 'agenda.meu-negocio.com.br',
            'active_domain_type' => 'custom',
        ]);

        Service::create([
            'name' => 'Barba',
            'description' => null,
            'price' => 40,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->app->forgetInstance('bookingTenant');

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'agenda.meu-negocio.com.br']);

        $response = app(ResolvePublicBookingTenant::class)->handle($request, function (Request $request) {
            return response()->json([
                'booking_tenant_id' => $request->attributes->get('bookingTenant')?->id,
            ]);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($tenant->id, $response->getData(true)['booking_tenant_id']);
    }
}
