<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\TeamMember;
use App\Support\RoleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request)
    {
        $user = $request->user();
        $tenant = $user->parent ?? $user;
        $teamMember = $this->teamMemberForUser($user);
        $roleId = $teamMember?->role_id ?? ($user->parent_id ? 'professional' : 'admin');
        $permissions = $this->permissionModulesForUser($user);
        $tenantRoles = $tenant?->custom_roles ?? [];

        return Inertia::render('Profile/Edit', [
            'user' => $user,
            'status' => session('status'),
            'teamMember' => $teamMember,
            'accountContext' => [
                'is_owner' => $user->parent_id === null,
                'role_id' => $roleId,
                'role_name' => RoleCatalog::titleFor($roleId, $tenantRoles),
                'company_name' => $tenant->name,
                'company_email' => $tenant->email,
                'public_booking_url' => $user->parent_id ? ($teamMember?->publicBookingUrl() ?? $tenant->publicBookingUrl()) : $user->publicBookingUrl(),
                'active_domain_type' => $user->active_domain_type,
                'subdomain' => $user->subdomain,
                'custom_domain' => $user->custom_domain,
                'must_reset_password' => (bool) $user->must_reset_password,
                'avatar_url' => $user->avatar_url ?? $teamMember?->avatar_url,
            ],
            'permissionModules' => $permissions,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $oldEmail = $user->email;
        $avatarUrl = $user->avatar_url;

        if ($request->hasFile('avatar')) {
            \App\Support\StorageHelper::delete($user->avatar_url);
            $avatarUrl = $request->file('avatar')->store('avatars', 'public');
        } elseif (array_key_exists('avatar_url', $validated)) {
            $avatarUrl = $validated['avatar_url'] ?: null;
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'avatar_url' => $avatarUrl,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        $this->syncTeamMemberProfile($user, $oldEmail, $avatarUrl);

        return Redirect::away(route('profile.edit'))->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::away('/');
    }

    private function teamMemberForUser($user): ?TeamMember
    {
        if (! $user?->parent_id) {
            return null;
        }

        return TeamMember::query()
            ->where('user_id', $user->parent_id)
            ->where('email', $user->email)
            ->first();
    }

    private function syncTeamMemberProfile($user, string $oldEmail, ?string $avatarUrl): void
    {
        if (! $user->parent_id) {
            return;
        }

        $teamMember = TeamMember::query()
            ->where('user_id', $user->parent_id)
            ->where('email', $oldEmail)
            ->first();

        if (! $teamMember) {
            return;
        }

        $teamMember->update([
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $avatarUrl,
        ]);
    }

    private function permissionModulesForUser($user): array
    {
        $modules = [
            'appointments' => [
                'title' => 'Agendamentos & Clientes',
                'icon' => 'fa-regular fa-calendar-check',
                'permissions' => [
                    'appointments.view' => 'Visualizar agendamentos',
                    'appointments.create' => 'Criar agendamentos',
                    'appointments.edit' => 'Editar status e horários',
                    'appointments.cancel' => 'Cancelar agendamentos',
                    'appointments.view_all' => 'Ver agenda de toda a equipe',
                ],
            ],
            'clients' => [
                'title' => 'Área do Cliente & Avaliações',
                'icon' => 'fa-solid fa-users-viewfinder',
                'permissions' => [
                    'clients.view' => 'Visualizar a Área do Cliente',
                    'clients.edit' => 'Editar dados de contato no histórico',
                    'clients.reviews' => 'Moderar avaliações',
                    'clients.view_all' => 'Ver clientes de toda a equipe',
                ],
            ],
            'services' => [
                'title' => 'Serviços & Valores',
                'icon' => 'fa-solid fa-scissors',
                'permissions' => [
                    'services.view' => 'Visualizar serviços',
                    'services.create' => 'Criar serviços',
                    'services.edit' => 'Editar serviços',
                    'services.delete' => 'Remover serviços',
                    'services.prices' => 'Alterar preços',
                ],
            ],
            'schedules' => [
                'title' => 'Horários & Bloqueios',
                'icon' => 'fa-regular fa-clock',
                'permissions' => [
                    'schedules.view' => 'Visualizar horários',
                    'schedules.manage' => 'Gerenciar expediente',
                    'schedules.breaks' => 'Editar intervalos',
                    'schedules.blocks' => 'Gerenciar bloqueios',
                ],
            ],
            'team' => [
                'title' => 'Time & Profissionais',
                'icon' => 'fa-solid fa-users',
                'permissions' => [
                    'team.view' => 'Visualizar equipe',
                    'team.create' => 'Cadastrar profissionais',
                    'team.edit' => 'Editar equipe',
                    'team.delete' => 'Remover profissionais',
                ],
            ],
            'reports' => [
                'title' => 'Relatórios & Métricas',
                'icon' => 'fa-solid fa-chart-line',
                'permissions' => [
                    'reports.view' => 'Visualizar métricas próprias',
                    'reports.view_all' => 'Visualizar métricas globais da empresa',
                    'reports.revenue' => 'Visualizar faturamento e comissões próprias',
                    'reports.revenue_all' => 'Visualizar faturamento global da empresa',
                    'reports.export' => 'Exportar dados para Excel / CSV',
                ],
            ],
            'integrations' => [
                'title' => 'Integrações',
                'icon' => 'fa-solid fa-puzzle-piece',
                'permissions' => [
                    'integrations.view' => 'Visualizar integrações',
                    'integrations.manage' => 'Gerenciar integrações',
                ],
            ],
            'branding' => [
                'title' => 'Personalização',
                'icon' => 'fa-solid fa-palette',
                'permissions' => [
                    'branding.view' => 'Visualizar identidade visual',
                    'branding.manage' => 'Gerenciar identidade visual',
                ],
            ],
            'settings' => [
                'title' => 'Configurações',
                'icon' => 'fa-solid fa-gear',
                'permissions' => [
                    'settings.domain' => 'Gerenciar domínio',
                    'settings.roles' => 'Gerenciar cargos e permissões',
                ],
            ],
        ];

        return collect($modules)
            ->map(function (array $module) use ($user): array {
                $module['permissions'] = collect($module['permissions'])
                    ->map(fn (string $label, string $key): array => [
                        'key' => $key,
                        'label' => $label,
                        'granted' => $user->hasPermission($key),
                    ])
                    ->values()
                    ->all();

                $module['granted_count'] = collect($module['permissions'])
                    ->where('granted', true)
                    ->count();

                return $module;
            })
            ->values()
            ->all();
    }
}
