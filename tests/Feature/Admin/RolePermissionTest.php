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

    public function test_admin_can_create_custom_role(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.roles.custom.store'), [
            'name' => 'Supervisor',
            'role_id' => 'supervisor',
            'description' => 'Coordena a equipe e acompanha a operação.',
            'icon' => 'fa-solid fa-user-tag',
            'badge_color' => 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-500/30',
            'base_role_id' => 'manager',
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');

        $fresh = $user->fresh();
        $this->assertSame('Supervisor', $fresh->custom_roles['supervisor']['name']);
        $this->assertSame('fa-solid fa-user-tag', $fresh->custom_roles['supervisor']['icon']);
        $this->assertSame(
            $fresh->rolePermissionsFor('manager'),
            $fresh->role_permissions['supervisor']
        );
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

    public function test_admin_can_update_team_member_role_to_custom_role_and_sync_user_title(): void
    {
        $user = User::factory()->create([
            'custom_roles' => [
                'supervisor' => [
                    'name' => 'Supervisor',
                    'description' => 'Coordena a equipe',
                    'badge_color' => 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-500/30',
                    'icon' => 'fa-solid fa-user-tag',
                ],
            ],
            'role_permissions' => [
                'supervisor' => ['appointments.view', 'team.view'],
            ],
        ]);

        $member = TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Ana Cabeleireira',
            'job_title' => 'Colorista Master',
            'role_id' => 'professional',
            'email' => 'ana@exemplo.com',
            'is_active' => true,
        ]);

        $linkedUser = User::factory()->create([
            'parent_id' => $user->id,
            'name' => 'Ana Cabeleireira',
            'email' => 'ana@exemplo.com',
            'role_title' => 'Profissional / Especialista',
        ]);

        $response = $this->actingAs($user)->patch(route('admin.roles.team-member.update-role', $member->id), [
            'role_id' => 'supervisor',
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');

        $this->assertSame('supervisor', $member->fresh()->role_id);
        $this->assertSame('Supervisor', $linkedUser->fresh()->role_title);
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
