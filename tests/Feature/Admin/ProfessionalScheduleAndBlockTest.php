<?php

namespace Tests\Feature\Admin;

use App\Models\BlockedTimeSlot;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessionalScheduleAndBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 8, 24, 8, 0, 0)); // Monday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_can_create_custom_business_hour_and_break_for_team_member(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'barbearia-pro',
        ]);

        $member = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Lucas Barbeiro',
            'job_title' => 'Barbeiro Master',
            'is_active' => true,
        ]);

        // Default company hours: Monday 08:00 - 18:00
        BusinessHour::create([
            'user_id' => $tenant->id,
            'team_member_id' => null,
            'day_of_week' => 1,
            'opens_at' => '08:00:00',
            'closes_at' => '18:00:00',
            'is_active' => true,
        ]);

        // Create Lucas custom hours: Monday 10:00 - 16:00, Coffee Break 13:00 - 14:00
        $response = $this->actingAs($tenant)->post(route('admin.business-hours.store'), [
            'team_member_id' => $member->id,
            'day_of_week' => 1,
            'opens_at' => '10:00',
            'closes_at' => '16:00',
            'has_break' => true,
            'break_opens_at' => '13:00',
            'break_closes_at' => '14:00',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.business-hours.index'));

        $this->assertDatabaseHas('business_hours', [
            'user_id' => $tenant->id,
            'team_member_id' => $member->id,
            'day_of_week' => 1,
            'opens_at' => '10:00:00',
            'closes_at' => '16:00:00',
            'break_opens_at' => '13:00:00',
            'break_closes_at' => '14:00:00',
        ]);
    }

    public function test_public_booking_availability_uses_professional_custom_hours_and_coffee_break(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'barbearia-pro',
        ]);

        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Corte Degrade',
            'price' => 50.00,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $memberWithCustom = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Lucas Barbeiro',
            'job_title' => 'Barbeiro Master',
            'is_active' => true,
        ]);

        $memberDefault = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Marcos Barbeiro',
            'job_title' => 'Barbeiro Jr',
            'is_active' => true,
        ]);

        // Default company: Monday (day 1) 08:00 - 18:00 (no break)
        BusinessHour::create([
            'user_id' => $tenant->id,
            'team_member_id' => null,
            'day_of_week' => 1,
            'opens_at' => '08:00:00',
            'closes_at' => '18:00:00',
            'is_active' => true,
        ]);

        // Lucas Custom: Monday (day 1) 10:00 - 15:00 with Break 12:00 - 13:00
        BusinessHour::create([
            'user_id' => $tenant->id,
            'team_member_id' => $memberWithCustom->id,
            'day_of_week' => 1,
            'opens_at' => '10:00:00',
            'closes_at' => '15:00:00',
            'break_opens_at' => '12:00:00',
            'break_closes_at' => '13:00:00',
            'is_active' => true,
        ]);

        $availabilityService = app(BookingAvailabilityService::class);

        // Test Lucas slots on 2026-08-24 (Monday):
        // Expected slots: 10:00, 11:00, 13:00, 14:00 (12:00 is break, 08:00-09:00 outside opening, 15:00 closing)
        $lucasPayload = $availabilityService->slotsFor($service, '2026-08-24', $memberWithCustom);
        $this->assertEquals(['10:00', '11:00', '13:00', '14:00'], $lucasPayload['slots']);
        $this->assertFalse(in_array('12:00', $lucasPayload['slots']));
        $this->assertFalse(in_array('08:00', $lucasPayload['slots']));

        // Test Marcos (default fallback) on 2026-08-24 (Monday):
        // Expected slots: starts at 08:00, goes until 17:00, includes 12:00
        $marcosPayload = $availabilityService->slotsFor($service, '2026-08-24', $memberDefault);
        $this->assertContains('08:00', $marcosPayload['slots']);
        $this->assertContains('12:00', $marcosPayload['slots']);
        $this->assertContains('17:00', $marcosPayload['slots']);
    }

    public function test_professional_specific_blocked_time_slots_block_only_that_professional(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'barbearia-pro',
        ]);

        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Barba Terapia',
            'price' => 40.00,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $memberA = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Profissional A',
            'is_active' => true,
        ]);
        $memberB = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Profissional B',
            'is_active' => true,
        ]);

        BusinessHour::create([
            'user_id' => $tenant->id,
            'team_member_id' => null,
            'day_of_week' => 1,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'is_active' => true,
        ]);

        // Block 10:00 - 11:00 ONLY for Profissional A
        BlockedTimeSlot::create([
            'user_id' => $tenant->id,
            'team_member_id' => $memberA->id,
            'starts_at' => '2026-08-24 10:00:00',
            'ends_at' => '2026-08-24 11:00:00',
            'reason' => 'Consulta Médica',
            'is_active' => true,
        ]);

        $availabilityService = app(BookingAvailabilityService::class);

        $slotsA = $availabilityService->slotsFor($service, '2026-08-24', $memberA)['slots'];
        $slotsB = $availabilityService->slotsFor($service, '2026-08-24', $memberB)['slots'];

        $this->assertNotContains('10:00', $slotsA);
        $this->assertContains('10:00', $slotsB);
    }
}
