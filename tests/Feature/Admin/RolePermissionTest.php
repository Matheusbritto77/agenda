<?php

namespace Tests\Feature\Admin;

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_roles_and_permissions_page(): void
    {
        $user = User::factory()->create();
        $member = TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Carlos Barbeiro',
            'job_title' => 'Barbeiro Sênior',
            'role_id' => 'professional',
            'email' => 'carlos@exemplo.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.roles.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Index')
            ->has('roles')
            ->has('teamMembers', 1)
            ->where('teamMembers.0.name', 'Carlos Barbeiro')
            ->where('teamMembers.0.job_title', 'Barbeiro Sênior')
        );
    }

    public function test_admin_can_update_role_permissions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.roles.permissions.update'), [
            'role' => 'manager',
            'permissions' => [
                'appointments.view',
                'appointments.create',
                'services.view',
            ],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');

        $this->assertEquals([
            'appointments.view',
            'appointments.create',
            'services.view',
        ], $user->fresh()->role_permissions['manager']);
    }

    public function test_admin_can_update_team_member_role_directly(): void
    {
        $user = User::factory()->create();
        $member = TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Ana Cabeleireira',
            'job_title' => 'Colorista Master',
            'role_id' => 'professional',
            'email' => 'ana@exemplo.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('admin.roles.team-member.update-role', $member->id), [
            'role_id' => 'manager',
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');
        $this->assertEquals('manager', $member->fresh()->role_id);
    }

    public function test_user_forced_to_reset_password_is_redirected(): void
    {
        $user = User::factory()->create([
            'must_reset_password' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.force-password-change.show'));
    }

    public function test_user_can_submit_forced_password_reset(): void
    {
        $user = User::factory()->create([
            'must_reset_password' => true,
        ]);

        $response = $this->actingAs($user)->post(route('admin.force-password-change.submit'), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertFalse($user->fresh()->must_reset_password);
    }
}
