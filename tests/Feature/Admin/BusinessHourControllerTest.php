<?php

namespace Tests\Feature\Admin;

use App\Models\BlockedTimeSlot;
use App\Models\BusinessHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class BusinessHourControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_business_hours_view_renders_create_modal_and_button(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.business-hours.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/BusinessHours/Index')
        );
    }

    public function test_admin_can_create_a_business_hour_via_form_post(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.business-hours.index'))
            ->post(route('admin.business-hours.store'), [
                'day_of_week' => 3,
                'label' => 'Quarta Tarde',
                'opens_at' => '13:00',
                'closes_at' => '19:00',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.business-hours.index'));
        $response->assertSessionHas('success', 'Horário de funcionamento cadastrado com sucesso.');

        $this->assertDatabaseHas('business_hours', [
            'day_of_week' => 3,
            'label' => 'Quarta Tarde',
            'opens_at' => '13:00:00',
            'closes_at' => '19:00:00',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_a_business_hour_via_json_request(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.business-hours.store'), [
            'day_of_week' => 5,
            'label' => 'Sexta Estendida',
            'opens_at' => '08:00',
            'closes_at' => '20:00',
            'is_active' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Horário de funcionamento cadastrado com sucesso.');
        $response->assertJsonPath('business_hour.day_of_week', 5);
        $response->assertJsonPath('business_hour.label', 'Sexta Estendida');
        $response->assertJsonPath('business_hour.opens_at', '08:00:00');
        $response->assertJsonPath('business_hour.closes_at', '20:00:00');
        $response->assertJsonPath('business_hour.is_active', true);

        $this->assertDatabaseHas('business_hours', [
            'day_of_week' => 5,
            'label' => 'Sexta Estendida',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_business_hour_via_json_request(): void
    {
        $admin = User::factory()->create();
        $businessHour = BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => 1,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'label' => 'Manhã',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->putJson(route('admin.business-hours.update', $businessHour), [
            'label' => 'Expediente Ajustado',
            'opens_at' => '09:15',
            'closes_at' => '17:45',
            'is_active' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Horário de funcionamento atualizado com sucesso.');
        $response->assertJsonPath('business_hour.label', 'Expediente Ajustado');
        $response->assertJsonPath('business_hour.opens_at', '09:15:00');
        $response->assertJsonPath('business_hour.closes_at', '17:45:00');
        $response->assertJsonPath('business_hour.is_active', false);

        $businessHour->refresh();

        $this->assertSame('09:15:00', $businessHour->opens_at);
        $this->assertSame('17:45:00', $businessHour->closes_at);
        $this->assertFalse($businessHour->is_active);
    }

    public function test_admin_cannot_save_business_hour_when_closing_time_is_not_after_opening_time(): void
    {
        $admin = User::factory()->create();
        $businessHour = BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => 2,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'label' => 'Tarde',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.business-hours.index'))
            ->put(route('admin.business-hours.update', $businessHour), [
                'label' => 'Inválido',
                'opens_at' => '14:00',
                'closes_at' => '13:00',
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors(['closes_at']);

        $businessHour->refresh();

        $this->assertSame('08:00:00', $businessHour->opens_at);
        $this->assertSame('12:00:00', $businessHour->closes_at);
        $this->assertSame('Tarde', $businessHour->label);
    }

    public function test_admin_can_create_a_blocked_time_slot_via_json_request(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.business-hours.blocks.store'), [
            'starts_at' => '2026-08-20T10:00',
            'ends_at' => '2026-08-20T11:30',
            'reason' => 'Manutenção',
            'is_active' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Bloqueio criado com sucesso.');
        $response->assertJsonPath('blocked_slot.reason', 'Manutenção');
        $response->assertJsonPath('blocked_slot.is_active', true);

        $this->assertDatabaseHas('blocked_time_slots', [
            'reason' => 'Manutenção',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_a_business_hour(): void
    {
        $admin = User::factory()->create();
        $businessHour = BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => 2,
            'label' => 'Terça-feira Comercial',
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.business-hours.index'))
            ->delete(route('admin.business-hours.destroy', $businessHour->id));

        $response->assertRedirect(route('admin.business-hours.index'));
        $response->assertSessionHas('success', 'Horário de funcionamento removido com sucesso.');

        $this->assertDatabaseMissing('business_hours', [
            'id' => $businessHour->id,
        ]);
    }

    public function test_configured_days_are_disabled_in_create_modal_select(): void
    {
        $admin = User::factory()->create();
        BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => 1,
            'label' => 'Segunda-feira',
            'opens_at' => '08:00:00',
            'closes_at' => '18:00:00',
            'slot_duration_minutes' => 15,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.business-hours.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/BusinessHours/Index')
            ->has('businessHours', 1)
            ->where('businessHours.0.day_of_week', 1)
            ->where('businessHours.0.label', 'Segunda-feira')
        );
    }
}
