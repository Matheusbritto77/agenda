<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolvePublicBookingTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DomainManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_domain_settings_on_the_real_schema(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $admin = User::factory()->create([
            'subdomain' => 'old-studio',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.domain.index'))
            ->post(route('admin.domain.update'), [
                'subdomain' => 'nova-agenda',
                'custom_domain' => 'agenda.minhaempresa.com.br',
                'active_domain_type' => 'custom',
            ]);

        $response
            ->assertRedirect(route('admin.domain.index'))
            ->assertSessionHas('success', 'Configurações de domínio salvas com sucesso!');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'subdomain' => 'nova-agenda',
            'custom_domain' => 'agenda.minhaempresa.com.br',
            'active_domain_type' => 'custom',
        ]);
    }

    public function test_admin_cannot_duplicate_an_existing_subdomain(): void
    {
        $admin = User::factory()->create([
            'subdomain' => 'meu-espaco',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        User::factory()->create([
            'subdomain' => 'duplicado',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.domain.index'))
            ->post(route('admin.domain.update'), [
                'subdomain' => 'duplicado',
                'custom_domain' => null,
                'active_domain_type' => 'subdomain',
            ]);

        $response->assertRedirect(route('admin.domain.index'));
        $response->assertSessionHasErrors(['subdomain']);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'subdomain' => 'meu-espaco',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);
    }

    public function test_admin_cannot_duplicate_an_existing_custom_domain(): void
    {
        $admin = User::factory()->create([
            'subdomain' => 'barbearia-central',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        User::factory()->create([
            'subdomain' => 'outro-espaco',
            'custom_domain' => 'agenda.cliente.com.br',
            'active_domain_type' => 'custom',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.domain.index'))
            ->post(route('admin.domain.update'), [
                'subdomain' => 'barbearia-central',
                'custom_domain' => 'agenda.cliente.com.br',
                'active_domain_type' => 'custom',
            ]);

        $response->assertRedirect(route('admin.domain.index'));
        $response->assertSessionHasErrors(['custom_domain']);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'subdomain' => 'barbearia-central',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);
    }

    public function test_public_booking_resolves_tenant_through_subdomain(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $tenant = User::factory()->create([
            'subdomain' => 'studio',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        $this->app->forgetInstance('bookingTenant');

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'studio.agendae.app']);

        $response = app(ResolvePublicBookingTenant::class)->handle($request, function (Request $request) {
            return response()->json([
                'booking_tenant_id' => $request->attributes->get('bookingTenant')?->id,
            ]);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($tenant->id, $response->getData(true)['booking_tenant_id']);
    }

    public function test_public_booking_resolves_tenant_through_custom_domain(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $tenant = User::factory()->create([
            'subdomain' => 'espaco-central',
            'custom_domain' => 'agenda.meu-negocio.com.br',
            'active_domain_type' => 'custom',
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
