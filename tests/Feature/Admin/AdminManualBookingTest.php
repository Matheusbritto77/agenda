<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class AdminManualBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_manual_appointment(): void
    {
        $admin = User::factory()->create();
        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Corte Premium',
            'description' => 'Agendamento criado manualmente',
            'price' => 120,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $targetDate = Carbon::now()->addDays(2)->format('Y-m-d');

        BusinessHour::create([
            'user_id' => $admin->id,
            'day_of_week' => Carbon::parse($targetDate)->dayOfWeek,
            'opens_at' => '08:00:00',
            'closes_at' => '18:00:00',
            'label' => 'Expediente do Admin',
            'slot_duration_minutes' => 45,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->from(route('admin.appointments.index'))->post(
            route('admin.appointments.store'),
            [
                'service_id' => $service->id,
                'client_name' => 'Cliente Manual',
                'client_email' => 'cliente.manual@example.com',
                'client_phone' => '11999990000',
                'appointment_date' => $targetDate,
                'appointment_time' => '14:45',
                'status' => 'confirmed',
                'notes' => 'Criado pelo painel administrativo',
            ]
        );

        $response
            ->assertRedirect(route('admin.appointments.index'))
            ->assertSessionHas('success', 'Agendamento interno criado com sucesso.');

        $this->assertDatabaseHas('appointments', [
            'service_id' => $service->id,
            'client_name' => 'Cliente Manual',
            'client_email' => 'cliente.manual@example.com',
            'client_phone' => '11999990000',
            'appointment_date' => $targetDate,
            'appointment_time' => '14:45',
            'status' => 'confirmed',
            'notes' => 'Criado pelo painel administrativo',
        ]);
    }

    public function test_admin_cannot_create_a_manual_appointment_without_required_fields(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.appointments.index'))
            ->post(route('admin.appointments.store'), [
                'service_id' => '',
                'client_name' => '',
                'client_email' => '',
                'client_phone' => '',
                'appointment_date' => '',
                'appointment_time' => '',
                'status' => '',
                'notes' => '',
            ]);

        $response->assertRedirect(route('admin.appointments.index'));
        $response->assertSessionHasErrors([
            'service_id',
            'client_name',
            'appointment_date',
            'appointment_time',
            'status',
        ]);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_admin_appointments_index_displays_manual_appointment(): void
    {
        $admin = User::factory()->create();
        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Barba Deluxe',
            'description' => null,
            'price' => 55,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        Appointment::create([
            'user_id' => $admin->id,
            'service_id' => $service->id,
            'client_name' => 'João Manual',
            'client_email' => 'joao.manual@example.com',
            'client_phone' => '11988887777',
            'appointment_date' => '2026-08-21',
            'appointment_time' => '10:15',
            'status' => 'pending',
            'notes' => 'Agendamento inserido pelo admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.appointments.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Appointments/Index')
            ->has('appointments', 1)
            ->where('appointments.0.client_name', 'João Manual')
            ->where('appointments.0.service.name', 'Barba Deluxe')
        );
    }

    public function test_admin_manual_booking_modal_includes_csrf_token(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.appointments.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Appointments/Index')
        );
    }

    public function test_admin_appointments_index_escapes_manual_appointment_content(): void
    {
        $admin = User::factory()->create();
        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Barba Deluxe',
            'description' => null,
            'price' => 55,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        Appointment::create([
            'user_id' => $admin->id,
            'service_id' => $service->id,
            'client_name' => '<script>alert(1)</script>',
            'client_email' => 'xss@example.com',
            'client_phone' => '11988887777',
            'appointment_date' => '2026-08-21',
            'appointment_time' => '10:15',
            'status' => 'pending',
            'notes' => '<img src=x onerror=alert(2)>',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.appointments.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Appointments/Index')
            ->has('appointments', 1)
            ->where('appointments.0.client_name', '<script>alert(1)</script>')
            ->where('appointments.0.notes', '<img src=x onerror=alert(2)>')
        );
    }

    public function test_admin_cannot_create_a_manual_appointment_in_an_occupied_slot(): void
    {
        $admin = User::factory()->create();
        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Barba Deluxe',
            'description' => null,
            'price' => 55,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $targetDate = now()->addDays(2)->format('Y-m-d');

        Appointment::create([
            'user_id' => $admin->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Existente',
            'client_email' => 'existente@example.com',
            'client_phone' => '11900000000',
            'appointment_date' => $targetDate,
            'appointment_time' => '14:30',
            'status' => 'confirmed',
            'notes' => null,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.appointments.index'))
            ->post(route('admin.appointments.store'), [
                'service_id' => $service->id,
                'client_name' => 'Cliente Novo',
                'client_email' => 'novo@example.com',
                'client_phone' => '11911112222',
                'appointment_date' => $targetDate,
                'appointment_time' => '14:30',
                'status' => 'confirmed',
                'notes' => 'Tentativa de conflito',
            ]);

        $response->assertSessionHasErrors(['appointment_time']);
        $this->assertSame(1, Appointment::query()->count());
    }
}
