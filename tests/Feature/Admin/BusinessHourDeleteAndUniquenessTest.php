<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessHourDeleteAndUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_business_hour_successfully(): void
    {
        $admin = User::factory()->create();

        $businessHour = BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => 2,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'label' => 'Terça',
            'slot_duration_minutes' => 45,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.business-hours.index'))
            ->delete(route('admin.business-hours.destroy', $businessHour));

        $response->assertRedirect(route('admin.business-hours.index'));
        $response->assertSessionHas('success', 'Horário de funcionamento removido com sucesso.');

        $this->assertDatabaseMissing('business_hours', [
            'id' => $businessHour->id,
        ]);
    }

    public function test_admin_cannot_create_a_duplicate_business_hour_for_the_same_day_of_week(): void
    {
        $admin = User::factory()->create();

        BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => 3,
            'opens_at' => '08:00:00',
            'closes_at' => '12:00:00',
            'label' => 'Manhã',
            'slot_duration_minutes' => 45,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.business-hours.index'))
            ->post(route('admin.business-hours.store'), [
                'day_of_week' => 3,
                'label' => 'Duplicado',
                'opens_at' => '13:00',
                'closes_at' => '18:00',
                'slot_duration_minutes' => 45,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.business-hours.index'));
        $response->assertSessionHasErrors(['day_of_week']);

        $this->assertSame(1, BusinessHour::query()->where('day_of_week', 3)->count());
    }
}
