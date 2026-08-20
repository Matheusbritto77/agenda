<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class AdminVisualAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_clickable_agenda_cards_that_open_the_details_modal(): void
    {
        $admin = User::factory()->create();
        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Corte Premium',
            'description' => null,
            'price' => 120,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        Appointment::create([
            'user_id' => $admin->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Agenda',
            'client_email' => 'cliente.agenda@example.com',
            'client_phone' => '11999990000',
            'appointment_date' => '2026-08-20',
            'appointment_time' => '14:30',
            'status' => 'confirmed',
            'notes' => 'Abrir pelo card da agenda',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['date' => '2026-08-20']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('appointments', 1)
            ->where('appointments.0.client_name', 'Cliente Agenda')
            ->where('appointments.0.service.name', 'Corte Premium')
        );
    }

    public function test_admin_dashboard_kpis_follow_the_selected_date(): void
    {
        $admin = User::factory()->create();
        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Corte Premium',
            'description' => null,
            'price' => 120,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        Appointment::create([
            'user_id' => $admin->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Confirmado',
            'client_email' => 'confirmado@example.com',
            'client_phone' => '11999990000',
            'appointment_date' => '2026-08-20',
            'appointment_time' => '09:00',
            'status' => 'confirmed',
        ]);

        Appointment::create([
            'user_id' => $admin->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Concluído',
            'client_email' => 'concluido@example.com',
            'client_phone' => '11999990001',
            'appointment_date' => '2026-08-20',
            'appointment_time' => '10:00',
            'status' => 'completed',
        ]);

        Appointment::create([
            'user_id' => $admin->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Outro Dia',
            'client_email' => 'outro@example.com',
            'client_phone' => '11999990002',
            'appointment_date' => '2026-08-21',
            'appointment_time' => '11:00',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['date' => '2026-08-20']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('stats.today_total', 2)
            ->where('stats.confirmed_total', 1)
            ->where('stats.completed_total', 1)
            ->where('stats.week_total', 3)
        );
    }

    public function test_admin_can_update_appointment_status_from_modal_and_return_to_dashboard(): void
    {
        $admin = User::factory()->create();
        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Barba Deluxe',
            'description' => null,
            'price' => 80,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'user_id' => $admin->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Editável',
            'client_email' => 'editavel@example.com',
            'client_phone' => '11988887777',
            'appointment_date' => '2026-08-21',
            'appointment_time' => '10:15',
            'status' => 'pending',
            'notes' => 'Atualizado pelo modal',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard', ['date' => '2026-08-21']))
            ->patch(route('admin.appointments.update-status', $appointment), [
                'status' => 'confirmed',
            ]);

        $response->assertRedirect(route('admin.dashboard', ['date' => '2026-08-21']));
        $response->assertSessionHas('success', 'Status do agendamento atualizado com sucesso.');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }
}
