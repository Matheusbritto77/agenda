<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteMemberWithEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_destroy_member_that_has_linked_subuser_email(): void
    {
        $tenant = User::factory()->create();

        // Criar membro com email (isso deve criar sub-user automaticamente via syncUserRecordForMember)
        $email = 'pro_email_' . uniqid() . '@teste.com';
        $storeResponse = $this->actingAs($tenant)->post(route('admin.team.store'), [
            'name' => 'Profissional Com Email',
            'job_title' => 'Barbeiro',
            'email' => $email,
            'role_id' => 'professional',
            'password' => 'password123',
        ]);
        $storeResponse->assertSessionHasNoErrors();

        $member = TeamMember::where('email', $email)->first();
        $this->assertNotNull($member, 'TeamMember não foi criado');

        // Verificar que sub-user foi criado
        $linkedUser = User::where('email', $email)->where('parent_id', $tenant->id)->first();
        $this->assertNotNull($linkedUser, 'Sub-user não foi criado para o membro');

        // Criar um appointment vinculado a este membro
        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Corte',
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        Appointment::create([
            'user_id' => $tenant->id,
            'service_id' => $service->id,
            'team_member_id' => $member->id,
            'client_name' => 'Cliente',
            'client_email' => 'cliente@teste.com',
            'client_phone' => '(11) 99999-1234',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'status' => 'confirmed',
        ]);

        // Agora tentar excluir
        $destroyResponse = $this->actingAs($tenant)->delete(route('admin.team.destroy', $member->id));

        $this->assertDatabaseMissing('team_members', ['id' => $member->id]);
        $this->assertDatabaseMissing('users', ['id' => $linkedUser->id]);
    }
}
