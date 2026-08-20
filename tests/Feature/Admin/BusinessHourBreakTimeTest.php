<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessHourBreakTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_a_business_hour_with_lunch_break(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.business-hours.index'))
            ->post(route('admin.business-hours.store'), [
                'day_of_week' => 1,
                'label' => 'Expediente com Pausa',
                'opens_at' => '08:00',
                'closes_at' => '18:00',
                'slot_interval_minutes' => 45,
                'has_break' => true,
                'break_opens_at' => '12:00',
                'break_closes_at' => '13:00',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.business-hours.index'));
        $response->assertSessionHas('success', 'Horário de funcionamento cadastrado com sucesso.');

        $this->assertDatabaseHas('business_hours', [
            'user_id' => $admin->id,
            'day_of_week' => 1,
            'label' => 'Expediente com Pausa',
            'opens_at' => '08:00:00',
            'closes_at' => '18:00:00',
            'slot_duration_minutes' => 45,
            'break_opens_at' => '12:00:00',
            'break_closes_at' => '13:00:00',
            'is_active' => true,
        ]);

        $this->assertSame(1, BusinessHour::query()->where('user_id', $admin->id)->count());
    }

    public function test_lunch_break_slots_are_excluded_from_available_slots(): void
    {
        $admin = User::factory()->create();

        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Corte com Pausa',
            'description' => null,
            'price' => 75,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => 1,
            'label' => 'Expediente com Pausa',
            'opens_at' => '08:00:00',
            'closes_at' => '18:00:00',
            'slot_duration_minutes' => 45,
            'break_opens_at' => '12:00:00',
            'break_closes_at' => '13:00:00',
            'is_active' => true,
        ]);

        $slots = app(BookingAvailabilityService::class)->slotsFor($service, '2026-08-17')['slots'];

        $this->assertSame([
            '08:00',
            '08:45',
            '09:30',
            '10:15',
            '11:00',
            '13:15',
            '14:00',
            '14:45',
            '15:30',
            '16:15',
            '17:00',
        ], $slots);

        $this->assertNotContains('12:00', $slots);
        $this->assertNotContains('12:15', $slots);
        $this->assertNotContains('12:30', $slots);
        $this->assertNotContains('12:45', $slots);
    }
}
