<?php

namespace Tests\Feature\Admin;

use App\Models\TeamMember;
use App\Models\User;
use App\Models\Service;
use App\Models\BusinessHour;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $subUser;
    private TeamMember $member;

    protected function setUp(): void
    {
        parent::setUp();

        // Create company owner
        $this->owner = User::factory()->create([
            'parent_id' => null,
        ]);

        // Create team member database record (role = professional)
        $this->member = TeamMember::create([
            'user_id' => $this->owner->id,
            'name' => 'Carlos Barbeiro',
            'job_title' => 'Barbeiro',
            'role_id' => 'professional',
            'email' => 'carlos@exemplo.com',
            'is_active' => true,
            'services' => [],
        ]);

        // Create sub-user login account for that team member
        $this->subUser = User::factory()->create([
            'parent_id' => $this->owner->id,
            'email' => 'carlos@exemplo.com',
            'name' => 'Carlos Barbeiro',
        ]);
    }

    public function test_owner_has_full_access(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->owner)->get(route('admin.roles.index'));
        $response->assertStatus(200);
    }

    public function test_subuser_without_permission_is_blocked(): void
    {
        // 'professional' does not have 'settings.roles' permission
        $response = $this->actingAs($this->subUser)->get(route('admin.roles.index'));
        $response->assertStatus(403);

        // 'professional' does not have 'reports.revenue' permission
        $response = $this->actingAs($this->subUser)->get(route('admin.financial.index'));
        $response->assertStatus(403);

        // 'professional' has 'schedules.view' by default, so they can access business-hours index
        $response = $this->actingAs($this->subUser)->get(route('admin.business-hours.index'));
        $response->assertStatus(200);

        // Clear all permissions (excluding default ones by storing empty config)
        $this->owner->update([
            'role_permissions' => [
                'professional' => []
            ]
        ]);

        $response = $this->actingAs($this->subUser)->get(route('admin.business-hours.index'));
        $response->assertStatus(403);
    }

    public function test_subuser_cannot_alter_service_prices_without_permission(): void
    {
        // Grant professional role the service.edit permission but NOT services.prices
        $this->owner->update([
            'role_permissions' => [
                'professional' => [
                    'services.view',
                    'services.edit',
                ]
            ]
        ]);

        // Create a service owned by the company
        $service = Service::create([
            'user_id' => $this->owner->id,
            'name' => 'Corte Simples',
            'description' => 'Corte comum',
            'price' => 50.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        // Attempting to update service fields WITHOUT changing the price is ALLOWED
        $response = $this->actingAs($this->subUser)->put(route('admin.services.update', $service->id), [
            'name' => 'Corte Diferenciado',
            'price' => 50.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $response->assertRedirect();
        $this->assertEquals('Corte Diferenciado', $service->fresh()->name);

        // Attempting to change the price is BLOCKED (422 validation exception / error)
        $response = $this->actingAs($this->subUser)->put(route('admin.services.update', $service->id), [
            'name' => 'Corte Diferenciado',
            'price' => 60.00, // Changed
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $response->assertSessionHasErrors(['price']);
    }

    public function test_subuser_cannot_alter_main_business_hours_without_permission(): void
    {
        // Grant professional role schedules.breaks permission but NOT schedules.manage
        $this->owner->update([
            'role_permissions' => [
                'professional' => [
                    'schedules.breaks',
                ]
            ]
        ]);

        $hour = BusinessHour::create([
            'user_id' => $this->owner->id,
            'day_of_week' => 1,
            'opens_at' => '08:00:00',
            'closes_at' => '18:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
            'break_opens_at' => null,
            'break_closes_at' => null,
        ]);

        // Changing breaks fields IS ALLOWED
        $response = $this->actingAs($this->subUser)->put(route('admin.business-hours.update', $hour->id), [
            'day_of_week' => 1,
            'opens_at' => '08:00',
            'closes_at' => '18:00',
            'has_break' => true,
            'break_opens_at' => '12:00',
            'break_closes_at' => '13:00',
            'is_active' => true,
        ]);
        $response->assertRedirect();
        $this->assertEquals('12:00:00', $hour->fresh()->break_opens_at);

        // Changing main fields IS BLOCKED
        $response = $this->actingAs($this->subUser)->put(route('admin.business-hours.update', $hour->id), [
            'day_of_week' => 1,
            'opens_at' => '09:00', // Changed
            'closes_at' => '18:00',
            'is_active' => true,
        ]);
        $response->assertSessionHasErrors(['schedules']);
    }

    public function test_subuser_without_view_all_can_only_see_their_own_appointments(): void
    {
        $service = Service::create([
            'user_id' => $this->owner->id,
            'name' => 'Corte',
            'price' => 50.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        // Create another professional
        $otherMember = TeamMember::create([
            'user_id' => $this->owner->id,
            'name' => 'Outro Profissional',
            'job_title' => 'Cabelereiro',
            'role_id' => 'professional',
            'email' => 'outro@exemplo.com',
            'is_active' => true,
            'services' => [],
        ]);

        // Appointment 1 linked to Carlos
        $appointment1 = Appointment::create([
            'user_id' => $this->owner->id,
            'service_id' => $service->id,
            'team_member_id' => $this->member->id,
            'appointment_date' => now()->format('Y-m-d'),
            'appointment_time' => '10:00',
            'client_name' => 'Cliente do Carlos',
            'client_email' => 'carlos.client@example.com',
            'client_phone' => '11988887777',
            'status' => 'confirmed',
        ]);

        // Appointment 2 linked to another team member
        $appointment2 = Appointment::create([
            'user_id' => $this->owner->id,
            'service_id' => $service->id,
            'team_member_id' => $otherMember->id, // Valid ID
            'appointment_date' => now()->format('Y-m-d'),
            'appointment_time' => '11:00',
            'client_name' => 'Outro Cliente',
            'client_email' => 'outro.client@example.com',
            'client_phone' => '11955556666',
            'status' => 'confirmed',
        ]);

        // Visita o dashboard como Carlos
        $response = $this->actingAs($this->subUser)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Apenas o agendamento de Carlos deve ser retornado
        $props = $response->viewData('page')['props'];
        $appointments = $props['appointments'];
        $this->assertCount(1, $appointments);
        $this->assertEquals($appointment1->id, $appointments[0]['id']);
        $this->assertSame(1, $props['stats']['today_total']);
        $this->assertSame(1, $props['stats']['confirmed_total']);
        $this->assertSame(1, $props['stats']['week_total']);
        $this->assertSame(1, $props['stats']['total_appointments']);
    }
}
