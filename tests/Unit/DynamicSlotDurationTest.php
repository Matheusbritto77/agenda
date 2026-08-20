<?php

namespace Tests\Unit;

use App\Models\BusinessHour;
use App\Models\Service;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicSlotDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_divides_business_hours_using_the_service_duration(): void
    {
        $tenant = \App\Models\User::factory()->create();

        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Corte Preciso',
            'description' => null,
            'price' => 60,
            'duration_minutes' => 45,
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

        $result = app(BookingAvailabilityService::class)->slotsFor($service, '2026-08-17');

        $this->assertSame('2026-08-17', $result['date']);
        $this->assertSame([
            '08:00',
            '08:45',
            '09:30',
            '10:15',
            '11:00',
        ], $result['slots']);
    }
}
