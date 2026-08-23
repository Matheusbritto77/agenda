<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleBreaksTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_update_business_hour_with_multiple_breaks(): void
    {
        $tenant = User::factory()->create();

        $response = $this->actingAs($tenant)->post(route('admin.business-hours.store'), [
            'day_of_week' => 1,
            'opens_at' => '08:00',
            'closes_at' => '18:00',
            'breaks' => [
                ['label' => 'Café Manhã', 'opens_at' => '10:00', 'closes_at' => '10:30'],
                ['label' => 'Almoço', 'opens_at' => '12:00', 'closes_at' => '13:00'],
                ['label' => 'Café Tarde', 'opens_at' => '16:00', 'closes_at' => '16:30'],
            ],
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.business-hours.index'));

        $hour = BusinessHour::where('user_id', $tenant->id)->where('day_of_week', 1)->first();
        $this->assertNotNull($hour);
        $this->assertCount(3, $hour->breaks);
        $this->assertEquals('10:00:00', $hour->breaks[0]['opens_at']);
        $this->assertEquals('12:00:00', $hour->breaks[1]['opens_at']);

        // Check availability service excludes all 3 break periods
        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Corte Simples',
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $availabilityService = app(BookingAvailabilityService::class);
        $result = $availabilityService->slotsFor($service, '2026-08-24'); // Monday

        $times = $result['slots'];

        // 10:00 (break 10:00-10:30) should not be available
        $this->assertNotContains('10:00', $times);
        // 12:00 & 12:30 (break 12:00-13:00) should not be available
        $this->assertNotContains('12:00', $times);
        $this->assertNotContains('12:30', $times);
        // 16:00 (break 16:00-16:30) should not be available
        $this->assertNotContains('16:00', $times);

        // 09:00, 11:00, 14:00 should be available
        $this->assertContains('09:00', $times);
        $this->assertContains('11:00', $times);
        $this->assertContains('14:00', $times);
    }
}
