<?php

namespace Tests\Feature\Admin;

use Inertia\Testing\AssertableInertia as Assert;
use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_view_team_management_page(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'barbearia-vip',
            'active_domain_type' => 'subdomain',
        ]);

        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Corte Degradê',
            'price' => 50.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $member = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Carlos Silva',
            'job_title' => 'Barbeiro Master',
            'subdomain' => 'carlos',
            'services' => [$service->id],
            'is_active' => true,
        ]);

        $response = $this->actingAs($tenant)->get(route('admin.team.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Team/Index')
            ->has('teamMembers', 1)
            ->where('teamMembers.0.name', 'Carlos Silva')
            ->where('teamMembers.0.job_title', 'Barbeiro Master')
            ->where('teamMembers.0.subdomain', 'carlos')
        );
    }

    public function test_tenant_can_create_team_member(): void
    {
        $tenant = User::factory()->create();

        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Barba Terapia',
            'price' => 45.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($tenant)->post(route('admin.team.store'), [
            'name' => 'Lucas Barbeiro',
            'job_title' => 'Especialista em Barba',
            'email' => 'lucas@barbearia.com',
            'phone' => '(11) 98888-7777',
            'subdomain' => 'lucas-barba',
            'services' => [$service->id],
            'bio' => 'Especialista em visagismo e barba desenhada.',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.team.index'));
        $this->assertDatabaseHas('team_members', [
            'user_id' => $tenant->id,
            'name' => 'Lucas Barbeiro',
            'job_title' => 'Especialista em Barba',
            'subdomain' => 'lucas-barba',
            'is_active' => true,
        ]);
    }

    public function test_tenant_can_update_team_member(): void
    {
        $tenant = User::factory()->create();

        $member = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Marcos Teste',
            'job_title' => 'Junior',
            'subdomain' => 'marcos-old',
            'is_active' => true,
        ]);

        $response = $this->actingAs($tenant)->put(route('admin.team.update', $member->id), [
            'name' => 'Marcos Santos',
            'job_title' => 'Senior Stylist',
            'subdomain' => 'marcos-pro',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.team.index'));
        $this->assertDatabaseHas('team_members', [
            'id' => $member->id,
            'name' => 'Marcos Santos',
            'job_title' => 'Senior Stylist',
            'subdomain' => 'marcos-pro',
        ]);
    }

    public function test_tenant_can_toggle_team_member_status(): void
    {
        $tenant = User::factory()->create();

        $member = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Renato Status',
            'job_title' => 'Barbeiro',
            'is_active' => true,
        ]);

        $response = $this->actingAs($tenant)->patch(route('admin.team.toggle-status', $member->id));

        $response->assertRedirect(route('admin.team.index'));
        $this->assertFalse($member->fresh()->is_active);
    }

    public function test_tenant_can_delete_team_member(): void
    {
        $tenant = User::factory()->create();

        $member = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Para Deletar',
            'job_title' => 'Estagiario',
            'is_active' => true,
        ]);

        $response = $this->actingAs($tenant)->delete(route('admin.team.destroy', $member->id));

        $response->assertRedirect(route('admin.team.index'));
        $this->assertDatabaseMissing('team_members', [
            'id' => $member->id,
        ]);
    }

    public function test_tenant_cannot_modify_other_tenant_team_member(): void
    {
        $tenant1 = User::factory()->create();
        $tenant2 = User::factory()->create();

        $memberTenant2 = TeamMember::create([
            'user_id' => $tenant2->id,
            'name' => 'Profissional Tenant 2',
            'job_title' => 'Especialista',
            'is_active' => true,
        ]);

        $response = $this->actingAs($tenant1)->put(route('admin.team.update', $memberTenant2->id), [
            'name' => 'Tentativa de Hack',
            'job_title' => 'Invasor',
        ]);

        $response->assertNotFound();
    }

    public function test_public_booking_stores_team_member_id(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'studio-beleza',
            'active_domain_type' => 'subdomain',
        ]);

        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Escova Modeladora',
            'price' => 80.00,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $member = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Juliana Cabelereira',
            'job_title' => 'Colorista Master',
            'subdomain' => 'juliana',
            'is_active' => true,
        ]);

        BusinessHour::create([
            'user_id' => $tenant->id,
            'day_of_week' => 1, // Monday
            'opens_at' => '08:00',
            'closes_at' => '18:00',
            'is_active' => true,
        ]);

        $nextMonday = now()->next('Monday')->toDateString();

        $response = $this->post('http://studio-beleza.localhost/booking', [
            'service_id' => $service->id,
            'team_member_id' => $member->id,
            'appointment_date' => $nextMonday,
            'appointment_time' => '10:00',
            'client_name' => 'Ana Paula',
            'client_email' => 'ana@gmail.com',
            'client_phone' => '(11) 99999-1234',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'user_id' => $tenant->id,
            'service_id' => $service->id,
            'team_member_id' => $member->id,
            'client_name' => 'Ana Paula',
            'appointment_date' => $nextMonday,
            'appointment_time' => '10:00',
        ]);
    }

    public function test_public_booking_page_includes_team_members(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'barbearia-top',
            'active_domain_type' => 'subdomain',
        ]);

        $member = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Roberto Navalha',
            'job_title' => 'Barbeiro Tradicional',
            'subdomain' => 'roberto',
            'is_active' => true,
        ]);

        $response = $this->get('http://barbearia-top.localhost/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Booking')
            ->has('teamMembers', 1)
            ->where('teamMembers.0.name', 'Roberto Navalha')
        );
    }

    public function test_tenant_can_reset_team_member_password(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'barbearia-vip',
            'active_domain_type' => 'subdomain',
        ]);

        $member = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'job_title' => 'Manicure',
            'subdomain' => 'jane',
            'is_active' => true,
        ]);

        // Shadow user
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'parent_id' => $tenant->id,
            'password' => bcrypt('old-password'),
        ]);

        $response = $this->actingAs($tenant)->post(route('admin.team.reset-password', $member->id), [
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('admin.team.index'));
        $this->assertTrue(auth()->attempt([
            'email' => 'jane@example.com',
            'password' => 'agendae123',
        ]));
    }

    public function test_query_param_professional_resolves_tenant_and_preselects_professional(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'barbearia-top',
            'active_domain_type' => 'subdomain',
        ]);

        $member = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Roberto Navalha',
            'job_title' => 'Barbeiro Tradicional',
            'subdomain' => 'roberto',
            'is_active' => true,
        ]);

        $response = $this->get('http://barbearia-top.localhost/?professional=roberto');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Booking')
            ->has('selectedProfessional')
            ->where('selectedProfessional.name', 'Roberto Navalha')
        );
    }

    public function test_direct_professional_subdomain_resolves_tenant_and_preselected_professional(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'barbearia-top',
            'active_domain_type' => 'subdomain',
        ]);

        $member = TeamMember::create([
            'user_id' => $tenant->id,
            'name' => 'Felipe Tesoura',
            'job_title' => 'Visagista',
            'subdomain' => 'felipe',
            'is_active' => true,
        ]);

        $response = $this->get('http://felipe.localhost/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Booking')
            ->has('selectedProfessional')
            ->where('selectedProfessional.name', 'Felipe Tesoura')
        );
    }
}
