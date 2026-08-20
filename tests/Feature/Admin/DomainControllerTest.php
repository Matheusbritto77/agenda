<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_custom_domain_settings_and_generate_custom_public_url(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.domain.update'), [
            'subdomain' => 'studio',
            'custom_domain' => 'studio.example.com',
            'active_domain_type' => 'custom',
        ]);

        $response->assertRedirect(route('admin.domain.index'));

        $admin->refresh();

        $this->assertSame('studio', $admin->subdomain);
        $this->assertSame('studio.example.com', $admin->custom_domain);
        $this->assertSame('custom', $admin->active_domain_type);
    }

    public function test_admin_cannot_activate_custom_domain_without_providing_custom_domain(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.domain.index'))
            ->post(route('admin.domain.update'), [
                'subdomain' => 'studio',
                'custom_domain' => '',
                'active_domain_type' => 'custom',
            ]);

        $response->assertSessionHasErrors(['custom_domain']);

        $admin->refresh();

        $this->assertNull($admin->custom_domain);
        $this->assertNotSame('custom', $admin->active_domain_type);
    }
}
