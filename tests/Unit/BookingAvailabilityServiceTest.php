<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\BlockedTimeSlot;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_slots_from_business_hours_and_excludes_conflicts(): void
    {
        $tenant = \App\Models\User::factory()->create();

        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Corte',
            'description' => 'Teste',
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        BusinessHour::create([
            'user_id' => $tenant->id,
            'day_of_week' => Carbon::MONDAY,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'label' => 'Manhã',
            'is_active' => true,
        ]);

        BusinessHour::create([
            'user_id' => $tenant->id,
            'day_of_week' => Carbon::MONDAY,
            'opens_at' => '13:00:00',
            'closes_at' => '18:00:00',
            'label' => 'Tarde',
            'is_active' => true,
        ]);

        Appointment::create([
            'user_id' => $tenant->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Teste',
            'client_email' => 'cliente@example.com',
            'client_phone' => '(11) 99999-9999',
            'appointment_date' => '2026-08-17',
            'appointment_time' => '10:00:00',
            'status' => 'confirmed',
        ]);

        BlockedTimeSlot::create([
            'user_id' => $tenant->id,
            'starts_at' => '2026-08-17 15:00:00',
            'ends_at' => '2026-08-17 15:30:00',
            'reason' => 'Bloqueio manual',
            'is_active' => true,
        ]);

        $result = app(BookingAvailabilityService::class)->slotsFor($service, '2026-08-17');

        $this->assertSame('2026-08-17', $result['date']);
        $this->assertContains('08:00', $result['slots']);
        $this->assertContains('09:30', $result['slots']);
        $this->assertNotContains('10:00', $result['slots']);
        $this->assertNotContains('10:15', $result['slots']);
        $this->assertNotContains('15:00', $result['slots']);
    }

    public function test_it_returns_no_slots_when_no_business_hours_exist(): void
    {
        $tenant = \App\Models\User::factory()->create();

        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Barba',
            'description' => null,
            'price' => 35,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $result = app(BookingAvailabilityService::class)->slotsFor($service, '2026-08-16');

        $this->assertSame([], $result['slots']);
    }

    public function test_it_ignores_inactive_business_hours_when_generating_slots(): void
    {
        $tenant = \App\Models\User::factory()->create();

        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Escova',
            'description' => null,
            'price' => 70,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        BusinessHour::create([
            'user_id' => $tenant->id,
            'day_of_week' => Carbon::MONDAY,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'label' => 'Manhã',
            'is_active' => true,
        ]);

        BusinessHour::create([
            'user_id' => $tenant->id,
            'day_of_week' => Carbon::MONDAY,
            'opens_at' => '13:00:00',
            'closes_at' => '18:00:00',
            'label' => 'Tarde desativada',
            'is_active' => false,
        ]);

        $result = app(BookingAvailabilityService::class)->slotsFor($service, '2026-08-17');

        $this->assertContains('08:00', $result['slots']);
        $this->assertContains('11:00', $result['slots']);
        $this->assertNotContains('13:00', $result['slots']);
        $this->assertNotContains('16:00', $result['slots']);
    }
}
