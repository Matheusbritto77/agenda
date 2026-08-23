<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\AppointmentReview;
use App\Models\ClientAccount;
use App\Models\CompanyReview;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_clients_service_reviews_and_company_reviews_from_its_company(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        [$client, $appointment, $review] = $this->createReviewedAppointment($owner, 'cliente@empresa.test');
        $this->createReviewedAppointment($otherOwner, 'cliente@outra.test');

        CompanyReview::create([
            'user_id' => $owner->id,
            'client_account_id' => $client->id,
            'rating' => 5,
            'comment' => 'Empresa excelente.',
            'is_public' => true,
        ]);

        $response = $this->actingAs($owner)->get(route('admin.client-area.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ClientArea/Index')
            ->has('clients.data', 1)
            ->where('clients.data.0.email', 'cliente@empresa.test')
            ->has('serviceReviews.data', 1)
            ->where('serviceReviews.data.0.id', $review->id)
            ->where('serviceReviews.data.0.is_public', false)
            ->has('companyReviews.data', 1)
            ->where('stats.clients', 1)
            ->where('stats.internal_reviews', 1)
        );
    }

    public function test_professional_sees_only_clients_and_reviews_from_own_appointments(): void
    {
        $owner = User::factory()->create();
        $professionalUser = User::factory()->create([
            'parent_id' => $owner->id,
            'email' => 'profissional@empresa.test',
        ]);
        $professional = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Profissional Um',
            'email' => $professionalUser->email,
            'role_id' => 'professional',
            'is_active' => true,
        ]);
        $otherProfessional = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Profissional Dois',
            'email' => 'outro@empresa.test',
            'role_id' => 'professional',
            'is_active' => true,
        ]);

        $this->createReviewedAppointment($owner, 'meu-cliente@empresa.test', $professional);
        $this->createReviewedAppointment($owner, 'outro-cliente@empresa.test', $otherProfessional);

        $response = $this->actingAs($professionalUser)->get(route('admin.client-area.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.email', 'meu-cliente@empresa.test')
            ->has('serviceReviews.data', 1)
            ->where('scopeLabel', 'Somente meus atendimentos')
        );
    }

    public function test_custom_role_permissions_can_block_client_area_access(): void
    {
        $owner = User::factory()->create([
            'role_permissions' => ['professional' => []],
        ]);
        $professionalUser = User::factory()->create([
            'parent_id' => $owner->id,
            'email' => 'sem-permissao@empresa.test',
        ]);
        TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Sem Permissão',
            'email' => $professionalUser->email,
            'role_id' => 'professional',
            'is_active' => true,
        ]);

        $this->actingAs($professionalUser)
            ->get(route('admin.client-area.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_edit_company_contact_history_without_changing_client_login_email(): void
    {
        $owner = User::factory()->create();
        [$client, $appointment] = $this->createReviewedAppointment($owner, 'login@cliente.test');

        $response = $this->actingAs($owner)->patch(route('admin.client-area.clients.update', $client), [
            'name' => 'Nome Atualizado',
            'phone' => '(11) 98888-7777',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'client_name' => 'Nome Atualizado',
            'client_phone' => '(11) 98888-7777',
            'client_email' => 'login@cliente.test',
        ]);
        $this->assertSame('login@cliente.test', $client->fresh()->email);
    }

    public function test_review_moderation_is_tenant_scoped(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        [, , $ownReview] = $this->createReviewedAppointment($owner, 'cliente@empresa.test');
        [, , $otherReview] = $this->createReviewedAppointment($otherOwner, 'cliente@outra.test');

        $this->actingAs($owner)
            ->patch(route('admin.client-area.service-reviews.toggle-public', $ownReview))
            ->assertRedirect();

        $this->assertTrue($ownReview->fresh()->is_public);

        $this->actingAs($owner)
            ->patch(route('admin.client-area.service-reviews.toggle-public', $otherReview))
            ->assertNotFound();

        $this->assertFalse($otherReview->fresh()->is_public);
    }

    public function test_client_permissions_are_available_in_roles_matrix(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('admin.roles.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('permissionModules.clients.title', 'Área do Cliente & Avaliações')
                ->has('permissionModules.clients.permissions', 4)
            );
    }

    private function createReviewedAppointment(User $owner, string $email, ?TeamMember $teamMember = null): array
    {
        $client = ClientAccount::create([
            'name' => 'Cliente '.uniqid(),
            'email' => $email,
            'phone' => '11999999999',
            'password' => Hash::make('password'),
            'must_reset_password' => false,
        ]);
        $service = Service::create([
            'user_id' => $owner->id,
            'name' => 'Serviço '.uniqid(),
            'description' => 'Serviço de teste',
            'price' => 100,
            'duration_minutes' => 60,
            'slot_duration_minutes' => 60,
            'is_active' => true,
        ]);
        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'client_account_id' => $client->id,
            'service_id' => $service->id,
            'team_member_id' => $teamMember?->id,
            'client_name' => $client->name,
            'client_email' => $client->email,
            'client_phone' => $client->phone,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00',
            'status' => 'completed',
        ]);
        $review = AppointmentReview::create([
            'appointment_id' => $appointment->id,
            'client_account_id' => $client->id,
            'rating' => 4,
            'comment' => 'Feedback interno do serviço.',
            'is_public' => false,
        ]);

        return [$client, $appointment, $review];
    }
}
