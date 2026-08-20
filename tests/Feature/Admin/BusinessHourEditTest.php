<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessHourEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_saved_business_hours_and_recalculate_dynamic_slots(): void
    {
        $admin = User::factory()->create();

        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Corte Premium',
            'description' => null,
            'price' => 80,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $businessHour = BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => Carbon::MONDAY,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'label' => 'Manhã',
            'slot_duration_minutes' => 45,
            'is_active' => true,
        ]);

        $availabilityService = app(BookingAvailabilityService::class);

        $before = $availabilityService->slotsFor($service, '2026-08-17');

        $this->assertSame([
            '08:00',
            '08:45',
            '09:30',
            '10:15',
            '11:00',
        ], $before['slots']);

        $response = $this->actingAs($admin)
            ->from(route('admin.business-hours.index'))
            ->put(route('admin.business-hours.update', $businessHour), [
                'label' => 'Manhã Ajustada',
                'opens_at' => '09:00',
                'closes_at' => '13:00',
                'slot_duration_minutes' => 45,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.business-hours.index'));
        $response->assertSessionHas('success', 'Horário de funcionamento atualizado com sucesso.');

        $businessHour->refresh();

        $this->assertSame('Manhã Ajustada', $businessHour->label);
        $this->assertSame('09:00:00', $businessHour->opens_at);
        $this->assertSame('13:00:00', $businessHour->closes_at);
        $this->assertSame(45, $businessHour->slot_duration_minutes);
        $this->assertTrue($businessHour->is_active);

        $this->assertDatabaseHas('business_hours', [
            'id' => $businessHour->id,
            'label' => 'Manhã Ajustada',
            'opens_at' => '09:00:00',
            'closes_at' => '13:00:00',
            'slot_duration_minutes' => 45,
            'is_active' => true,
        ]);

        $this->app->forgetInstance(BookingAvailabilityService::class);
        $after = app(BookingAvailabilityService::class)->slotsFor($service, '2026-08-17');

        $this->assertSame([
            '09:00',
            '09:45',
            '10:30',
            '11:15',
            '12:00',
        ], $after['slots']);
    }
}
