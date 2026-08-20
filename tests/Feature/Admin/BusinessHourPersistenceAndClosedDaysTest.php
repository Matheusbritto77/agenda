<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessHourPersistenceAndClosedDaysTest extends TestCase
{
    use RefreshDatabase;

    public function test_label_and_coffee_break_changes_persist_in_the_database(): void
    {
        $admin = User::factory()->create();

        $businessHour = BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => Carbon::MONDAY,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'label' => 'Manhã',
            'slot_duration_minutes' => 45,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.business-hours.index'))
            ->put(route('admin.business-hours.update', $businessHour), [
                'day_of_week' => Carbon::MONDAY,
                'label' => 'Manhã com Café',
                'opens_at' => '08:00',
                'closes_at' => '12:00',
                'slot_duration_minutes' => 45,
                'has_break' => true,
                'break_opens_at' => '10:00',
                'break_closes_at' => '10:15',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.business-hours.index'));
        $response->assertSessionHas('success', 'Horário de funcionamento atualizado com sucesso.');

        $businessHour->refresh();

        $this->assertSame('Manhã com Café', $businessHour->label);
        $this->assertSame('08:00:00', $businessHour->opens_at);
        $this->assertSame('12:00:00', $businessHour->closes_at);
        $this->assertSame(45, $businessHour->slot_duration_minutes);
        $this->assertSame('10:00:00', $businessHour->break_opens_at);
        $this->assertSame('10:15:00', $businessHour->break_closes_at);
        $this->assertTrue($businessHour->is_active);

        $this->assertDatabaseHas('business_hours', [
            'id' => $businessHour->id,
            'label' => 'Manhã com Café',
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'slot_duration_minutes' => 45,
            'break_opens_at' => '10:00:00',
            'break_closes_at' => '10:15:00',
            'is_active' => true,
        ]);
    }

    public function test_closed_or_inactive_days_return_zero_public_slots(): void
    {
        $admin = User::factory()->create();

        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Corte Executivo',
            'description' => null,
            'price' => 120,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => Carbon::MONDAY,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'label' => 'Fechado',
            'slot_duration_minutes' => 45,
            'is_active' => false,
        ]);

        $slotsOnInactiveDay = app(BookingAvailabilityService::class)->slotsFor($service, '2026-08-17')['slots'];
        $slotsOnClosedDay = app(BookingAvailabilityService::class)->slotsFor($service, '2026-08-18')['slots'];

        $this->assertSame([], $slotsOnInactiveDay);
        $this->assertSame([], $slotsOnClosedDay);
    }
}
