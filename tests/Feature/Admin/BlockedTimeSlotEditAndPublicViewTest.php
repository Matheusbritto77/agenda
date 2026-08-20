<?php

namespace Tests\Feature\Admin;

use App\Models\BlockedTimeSlot;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedTimeSlotEditAndPublicViewTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $this->tenant = User::factory()->create([
            'subdomain' => 'studio',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        BusinessHour::create([
            'user_id' => $this->tenant->id,
            'day_of_week' => Carbon::MONDAY,
            'opens_at' => '08:00:00',
            'closes_at' => '18:00:00',
            'label' => 'Expediente Base',
            'slot_duration_minutes' => 45,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_edit_a_blocked_time_slot_successfully(): void
    {
        $admin = $this->tenant;

        $blockedTimeSlot = BlockedTimeSlot::create([
            'user_id' => $admin->id,
            'starts_at' => '2026-08-17 12:00:00',
            'ends_at' => '2026-08-17 13:00:00',
            'reason' => 'Feriado municipal',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->putJson(route('admin.business-hours.blocks.update', $blockedTimeSlot), [
            'starts_at' => '2026-08-17T12:30',
            'ends_at' => '2026-08-17T13:30',
            'reason' => 'Feriado municipal ajustado',
            'is_active' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Bloqueio atualizado com sucesso.');
        $response->assertJsonPath('blocked_slot.reason', 'Feriado municipal ajustado');
        $response->assertJsonPath('blocked_slot.is_active', true);
        $response->assertJsonPath('blocked_slots.0.reason', 'Feriado municipal ajustado');

        $blockedTimeSlot->refresh();

        $this->assertSame('2026-08-17 12:30:00', $blockedTimeSlot->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-17 13:30:00', $blockedTimeSlot->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('Feriado municipal ajustado', $blockedTimeSlot->reason);
        $this->assertTrue($blockedTimeSlot->is_active);

        $this->assertDatabaseHas('blocked_time_slots', [
            'id' => $blockedTimeSlot->id,
            'user_id' => $admin->id,
            'reason' => 'Feriado municipal ajustado',
            'is_active' => true,
        ]);
    }

    public function test_public_available_slots_exposes_blocked_dates_with_reason(): void
    {
        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Corte Executivo',
            'description' => null,
            'price' => 120,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        BlockedTimeSlot::create([
            'user_id' => $this->tenant->id,
            'starts_at' => '2026-08-24 12:00:00',
            'ends_at' => '2026-08-24 13:30:00',
            'reason' => 'Feriado municipal',
            'is_active' => true,
        ]);

        $response = $this->getJson('http://studio.agendae.app/available-slots?service_id=' . $service->id . '&date=2026-08-24');

        $response->assertOk();
        $response->assertJsonPath('service_id', $service->id);
        $response->assertJsonPath('date', '2026-08-24');
        $response->assertJsonPath('blocked_slots.0.starts_at', '2026-08-24 12:00');
        $response->assertJsonPath('blocked_slots.0.ends_at', '2026-08-24 13:30');
        $response->assertJsonPath('blocked_slots.0.reason', 'Feriado municipal');

        $slots = $response->json('slots');

        $this->assertNotContains('12:00', $slots);
        $this->assertNotContains('12:45', $slots);
    }

    public function test_admin_can_update_block_via_blocks_update_route_with_separate_dates_and_times(): void
    {
        $admin = $this->tenant;

        $blockedTimeSlot = BlockedTimeSlot::create([
            'user_id' => $admin->id,
            'starts_at' => '2026-08-17 08:00:00',
            'ends_at' => '2026-08-17 18:00:00',
            'reason' => 'Manutenção',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->putJson(route('admin.blocks.update', $blockedTimeSlot->id), [
            'start_date' => '2026-08-18',
            'start_time' => '09:00',
            'end_date' => '2026-08-18',
            'end_time' => '17:00',
            'label' => 'Reforma Geral',
            'is_active' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Bloqueio atualizado com sucesso.');
        $response->assertJsonPath('blocked_slot.reason', 'Reforma Geral');

        $blockedTimeSlot->refresh();

        $this->assertSame('2026-08-18 09:00:00', $blockedTimeSlot->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-18 17:00:00', $blockedTimeSlot->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('Reforma Geral', $blockedTimeSlot->reason);
        $this->assertTrue($blockedTimeSlot->is_active);
    }
}
