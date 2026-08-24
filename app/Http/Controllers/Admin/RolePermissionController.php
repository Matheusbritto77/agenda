<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\RoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;
        $currentUser = $user;

        // Get ONLY the tenant owner account (single owner row) - NOT sub-users / child accounts
        $owner = User::query()
            ->where('id', $tenantId)
            ->firstOrFail();

        $users = collect([$owner]);

        // Get sub-users (child accounts created for team members with email) for future
        // reference / extended permission UI, but NOT rendered as "owner" rows
        $subUsers = User::query()
            ->where('parent_id', $tenantId)
            ->where('id', '!=', $tenantId)
            ->latest()
            ->get();

        // Get team members of the current tenant
        $teamMembers = TeamMember::query()
            ->where('user_id', $tenantId)
            ->latest()
            ->get();

        $roles = RoleCatalog::all($owner->custom_roles ?? []);

        $permissionModules = [
            'appointments' => [
                'title' => 'Agendamentos & Clientes',
                'icon' => 'fa-regular fa-calendar-check',
                'permissions' => [
                    'appointments.view' => 'Visualizar Agendamentos',
                    'appointments.create' => 'Criar Novos Agendamentos',
                    'appointments.edit' => 'Editar e Reagendar Horários',
                    'appointments.cancel' => 'Cancelar / Excluir Agendamentos',
                    'appointments.view_all' => 'Visualizar Agendamentos de Toda a Equipe',
                ],
            ],
            'services' => [
                'title' => 'Serviços & Catálogo',
                'icon' => 'fa-solid fa-scissors',
                'permissions' => [
                    'services.view' => 'Visualizar Catálogo de Serviços',
                    'services.create' => 'Cadastrar Novos Serviços',
                    'services.edit' => 'Editar Serviços e Descrições',
                    'services.delete' => 'Desativar ou Excluir Serviços',
                    'services.prices' => 'Alterar Tabela de Preços',
                ],
            ],
            'clients' => [
                'title' => 'Área do Cliente & Avaliações',
                'icon' => 'fa-solid fa-users-viewfinder',
                'permissions' => [
                    'clients.view' => 'Visualizar a Área do Cliente',
                    'clients.edit' => 'Editar dados de contato no histórico',
                    'clients.reviews' => 'Moderar avaliações internas e públicas',
                    'clients.view_all' => 'Visualizar clientes de toda a equipe',
                ],
            ],
            'schedules' => [
                'title' => 'Horários & Bloqueios',
                'icon' => 'fa-regular fa-clock',
                'permissions' => [
                    'schedules.view' => 'Visualizar Horários e Bloqueios',
                    'schedules.manage' => 'Configurar Horários de Abertura e Fechamento',
                    'schedules.breaks' => 'Definir Intervalos e Pausas de Almoço',
                    'schedules.blocks' => 'Cadastrar Bloqueios e Feriados',
                ],
            ],
            'team' => [
                'title' => 'Time & Profissionais',
                'icon' => 'fa-solid fa-users',
                'permissions' => [
                    'team.view' => 'Visualizar Lista de Profissionais',
                    'team.create' => 'Cadastrar Novos Especialistas',
                    'team.edit' => 'Editar Dados, Fotos e Subdomínios',
                    'team.delete' => 'Remover Membros da Equipe',
                ],
            ],
            'reports' => [
                'title' => 'Relatórios & Métricas',
                'icon' => 'fa-solid fa-chart-line',
                'permissions' => [
                    'reports.view' => 'Visualizar Métricas Próprias (Membro)',
                    'reports.view_all' => 'Visualizar Métricas Globais da Empresa (Toda Equipe)',
                    'reports.revenue' => 'Visualizar Faturamento e Comissões Próprias',
                    'reports.revenue_all' => 'Visualizar Faturamento Global e DRE da Empresa',
                    'reports.export' => 'Exportar Dados para Excel / CSV',
                ],
            ],
            'integrations' => [
                'title' => 'Integrações & Pagamentos',
                'icon' => 'fa-solid fa-puzzle-piece',
                'permissions' => [
                    'integrations.view' => 'Visualizar Configurações de Integrações',
                    'integrations.manage' => 'Gerenciar Integrações e Gateways de Pagamento',
                ],
            ],
            'branding' => [
                'title' => 'Identidade Visual & Cores',
                'icon' => 'fa-solid fa-palette',
                'permissions' => [
                    'branding.view' => 'Visualizar Configurações de Cores e Logo',
                    'branding.manage' => 'Gerenciar Personalização Visual',
                ],
            ],
            'settings' => [
                'title' => 'Configurações do Sistema',
                'icon' => 'fa-solid fa-gear',
                'permissions' => [
                    'settings.domain' => 'Gerenciar Domínios e Subdomínios',
                    'settings.roles' => 'Gerenciar Usuários e Matriz de Permissões',
                ],
            ],
        ];

        // Default active permissions by role
        $rolePermissions = [
            'admin' => [
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.view_all',
                'clients.view', 'clients.edit', 'clients.reviews', 'clients.view_all',
                'services.view', 'services.create', 'services.edit', 'services.delete', 'services.prices',
                'schedules.view', 'schedules.manage', 'schedules.breaks', 'schedules.blocks',
                'team.view', 'team.create', 'team.edit', 'team.delete',
                'reports.view', 'reports.view_all', 'reports.revenue', 'reports.revenue_all', 'reports.export',
                'integrations.view', 'integrations.manage',
                'branding.view', 'branding.manage',
                'settings.domain', 'settings.roles',
            ],
            'manager' => [
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.view_all',
                'clients.view', 'clients.edit', 'clients.reviews', 'clients.view_all',
                'services.view', 'services.create', 'services.edit', 'services.prices',
                'schedules.view', 'schedules.manage', 'schedules.breaks', 'schedules.blocks',
                'team.view', 'team.create', 'team.edit',
                'reports.view', 'reports.view_all', 'reports.revenue', 'reports.export',
                'integrations.view', 'integrations.manage',
                'branding.view', 'branding.manage',
            ],
            'professional' => [
                'appointments.view', 'appointments.create', 'appointments.edit',
                'clients.view', 'clients.edit', 'clients.reviews',
                'services.view',
                'schedules.view', 'schedules.breaks',
                'team.view',
                'reports.view', 'reports.revenue',
            ],
            'receptionist' => [
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.view_all',
                'clients.view', 'clients.edit', 'clients.view_all',
                'services.view',
                'schedules.view', 'schedules.manage', 'schedules.blocks',
                'team.view',
            ],
        ];

        // Merge stored tenant permissions with defaults and initialize custom roles
        $tenantPermissions = $owner->role_permissions ?? [];
        foreach (array_keys($roles) as $roleId) {
            $rolePermissions[$roleId] = array_values(array_unique((array) ($tenantPermissions[$roleId] ?? $rolePermissions[$roleId] ?? [])));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'users' => $users,
                'sub_users' => $subUsers,
                'team_members' => $teamMembers,
                'roles' => $roles,
                'permission_modules' => $permissionModules,
                'role_permissions' => $rolePermissions,
                'stats' => [
                    'total_accounts' => $users->count() + $subUsers->count() + $teamMembers->count(),
                    'admins' => 1 + $teamMembers->where('role_id', 'admin')->count(),
                    'team_members_count' => $teamMembers->count(),
                ],
            ]);
        }

        return Inertia::render('Admin/Roles/Index', compact('users', 'subUsers', 'teamMembers', 'roles', 'permissionModules', 'rolePermissions', 'currentUser'));
    }

    public function updatePermissions(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;
        $owner = User::findOrFail($tenantId);

        $validated = Validator::make($request->all(), [
            'role' => ['required', 'string', Rule::in($this->availableRoleIds($owner))],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ])->validate();

        $role = $validated['role'];
        $permissions = array_values(array_unique((array) ($validated['permissions'] ?? [])));

        $rolePermissions = $owner->role_permissions ?? [];
        $rolePermissions[$role] = $permissions;
        $owner->role_permissions = $rolePermissions;
        $owner->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Permissões do cargo '{$role}' atualizadas com sucesso!",
                'role' => $role,
                'permissions' => $permissions,
            ]);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Permissões do cargo atualizadas com sucesso!");
    }

    public function storeCustomRole(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;
        $owner = User::findOrFail($tenantId);
        $existingRoles = RoleCatalog::all($owner->custom_roles ?? []);
        $existingRoleIds = array_keys($existingRoles);

        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'role_id' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_-]+$/i'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:100'],
            'badge_color' => ['nullable', 'string', 'max:255'],
            'base_role_id' => ['nullable', 'string', Rule::in($existingRoleIds)],
        ])->validate();

        $roleId = Str::slug((string) ($validated['role_id'] ?? $validated['name']), '-');
        if ($roleId === '') {
            throw ValidationException::withMessages([
                'name' => 'Informe um nome válido para o novo cargo.',
            ]);
        }

        if (in_array($roleId, $existingRoleIds, true)) {
            throw ValidationException::withMessages([
                'role_id' => 'Este identificador já está em uso. Escolha outro nome ou slug.',
            ]);
        }

        $baseRoleId = $validated['base_role_id'] ?? 'professional';
        $basePermissions = $owner->rolePermissionsFor($baseRoleId);

        $customRoles = $owner->custom_roles ?? [];
        $customRoles[$roleId] = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'badge_color' => $validated['badge_color'] ?? 'bg-slate-500/15 text-slate-700 dark:text-slate-300 border-slate-500/30',
            'icon' => $validated['icon'] ?? 'fa-solid fa-user-tag',
        ];

        $owner->custom_roles = $customRoles;
        $rolePermissions = $owner->role_permissions ?? [];
        $rolePermissions[$roleId] = $basePermissions;
        $owner->role_permissions = $rolePermissions;
        $owner->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Cargo '{$validated['name']}' criado com sucesso!",
                'role' => $roleId,
                'role_data' => $customRoles[$roleId],
            ], 201);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Cargo '{$validated['name']}' criado com sucesso!");
    }

    public function updateMemberRole(Request $request, TeamMember $teamMember)
    {
        $authUser = $request->user() ?? auth()->user();
        $tenantId = $authUser?->parent_id ? (int) $authUser->parent_id : (int) ($authUser?->id ?? auth()->id());
        abort_unless((int) $teamMember->user_id === (int) $tenantId, 404);
        $owner = User::findOrFail($tenantId);

        $validated = Validator::make($request->all(), [
            'role_id' => ['required', 'string', Rule::in($this->availableRoleIds($owner))],
        ])->validate();

        $teamMember->update([
            'role_id' => $validated['role_id'],
        ]);

        User::query()
            ->where('parent_id', $tenantId)
            ->where('email', $teamMember->email)
            ->update([
                'role_title' => RoleCatalog::titleFor($validated['role_id'], $owner->custom_roles ?? []),
            ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Cargo de {$teamMember->name} alterado para '{$teamMember->role_name}' com sucesso!",
                'team_member' => $teamMember->fresh(),
            ]);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Cargo de {$teamMember->name} alterado para '{$teamMember->role_name}' com sucesso!");
    }

    public function destroyCustomRole(Request $request, string $roleId)
    {
        $user = $request->user() ?? auth()->user();
        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;
        $owner = User::findOrFail($tenantId);

        $defaultRoleIds = array_keys(RoleCatalog::defaults());
        if (in_array($roleId, $defaultRoleIds, true)) {
            abort(422, 'Cargos padrão do sistema não podem ser removidos.');
        }

        $customRoles = $owner->custom_roles ?? [];
        if (! isset($customRoles[$roleId])) {
            abort(404, 'Cargo personalizado não encontrado.');
        }

        $roleName = $customRoles[$roleId]['name'] ?? $roleId;

        // Fallback any team members with this custom role to 'professional'
        TeamMember::query()
            ->where('user_id', $tenantId)
            ->where('role_id', $roleId)
            ->update([
                'role_id' => 'professional',
            ]);

        unset($customRoles[$roleId]);
        $owner->custom_roles = $customRoles;

        $rolePermissions = $owner->role_permissions ?? [];
        unset($rolePermissions[$roleId]);
        $owner->role_permissions = $rolePermissions;
        $owner->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Cargo '{$roleName}' excluído com sucesso!",
            ]);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Cargo '{$roleName}' excluído com sucesso!");
    }

    /**
     * @return array<int, string>
     */
    private function availableRoleIds(User $owner): array
    {
        return RoleCatalog::ids($owner->custom_roles ?? []);
    }
}
