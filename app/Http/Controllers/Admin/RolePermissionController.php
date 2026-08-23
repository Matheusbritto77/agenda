<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\RoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        $roles = RoleCatalog::all();

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

        // Merge stored tenant permissions with default ones
        $tenantPermissions = $owner->role_permissions ?? [];
        foreach ($rolePermissions as $r => $perms) {
            if (isset($tenantPermissions[$r])) {
                $rolePermissions[$r] = (array) $tenantPermissions[$r];
            }
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
        $role = $request->input('role', 'admin');
        $permissions = (array) $request->input('permissions', []);

        $user = $request->user() ?? auth()->user();
        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;

        $owner = User::findOrFail($tenantId);
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

    public function updateMemberRole(Request $request, TeamMember $teamMember)
    {
        $tenantId = $request->user()?->id ?? auth()->id();
        abort_unless((int) $teamMember->user_id === (int) $tenantId, 404);

        $validated = Validator::make($request->all(), [
            'role_id' => ['required', 'string', 'in:' . implode(',', RoleCatalog::ids())],
        ])->validate();

        $teamMember->update([
            'role_id' => $validated['role_id'],
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
}
