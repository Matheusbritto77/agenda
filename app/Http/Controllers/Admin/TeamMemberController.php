<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\RoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class TeamMemberController extends Controller
{
    /**
     * Map a team member role_id to the human-readable role title
     * used for the user record's role_title attribute.
     */
    private function roleIdToTitle(string $roleId): string
    {
        return RoleCatalog::titleFor($roleId);
    }

    /**
     * Sync the User record (matched by email) with the team member's
     * chosen role_id, keeping role_title in sync.
     *
     * @param  array<string, mixed>  $validated
     */
    private function syncUserRecordForMember(array $validated, int $tenantId, ?string $roleId = null, ?TeamMember $member = null): void
    {
        $email = $validated['email'] ?? ($member?->email ?? null);
        if (empty($email)) {
            return;
        }

        // Handle email change to avoid orphaned User accounts
        $oldEmail = $member ? $member->getOriginal('email') : null;
        if ($oldEmail && $oldEmail !== $email) {
            $existingUser = User::where('email', $oldEmail)
                ->where('parent_id', $tenantId)
                ->first();
            if ($existingUser) {
                $existingUser->update(['email' => $email]);
            }
        }

        $roleIdChosen = $roleId ?? ($member?->role_id ?? ($validated['role_id'] ?? 'professional'));
        $roleTitle = $this->roleIdToTitle((string) $roleIdChosen);

        $user = User::where('email', $email)->first();
        $userPassword = ! empty($validated['password'] ?? null)
            ? \Illuminate\Support\Facades\Hash::make((string) $validated['password'])
            : \Illuminate\Support\Facades\Hash::make('agendae123');

        if (! $user) {
            $subdomain = ! empty($validated['subdomain']) ? strtolower(trim((string) $validated['subdomain'])) : ($member?->subdomain ?? null);

            User::create([
                'name' => $validated['name'] ?? ($member?->name ?? ''),
                'email' => $email,
                'password' => $userPassword,
                'parent_id' => $tenantId,
                'subdomain' => $subdomain,
                'active_domain_type' => ! empty($subdomain) ? 'subdomain' : 'subdomain',
                'must_reset_password' => empty($validated['password'] ?? null),
                'role_title' => $roleTitle,
            ]);

            return;
        }

        $updateData = [
            'parent_id' => $tenantId,
            'role_title' => $roleTitle,
        ];

        if (! empty($validated['password'] ?? null)) {
            $updateData['password'] = $userPassword;
            $updateData['must_reset_password'] = false;
        }

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if (isset($validated['subdomain']) || ($member?->subdomain !== null)) {
            $subdomain = ! empty($validated['subdomain'] ?? null)
                ? strtolower(trim((string) $validated['subdomain']))
                : ($member?->subdomain ?? null);
            $updateData['subdomain'] = $subdomain;
            $updateData['active_domain_type'] = ! empty($subdomain) ? 'subdomain' : 'subdomain';
        }

        $user->update($updateData);
    }
    public function index(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;

        $teamMembers = TeamMember::query()
            ->where('user_id', $tenantId)
            ->latest()
            ->get();

        $services = Service::query()
            ->where('user_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'team_members' => $teamMembers,
                'services' => $services,
                'roles' => RoleCatalog::all(),
            ]);
        }

        return Inertia::render('Admin/Team/Index', [
            'teamMembers' => $teamMembers,
            'services' => $services,
            'roles' => RoleCatalog::all(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user() ?? auth()->user();
            $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;

            $validated = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'job_title' => ['nullable', 'string', 'max:255'],
                'role_id' => ['nullable', 'string', 'in:' . implode(',', RoleCatalog::ids())],
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    function ($attribute, $value, $fail) use ($tenantId) {
                        if ($value === null || $value === '') {
                            return;
                        }
                        $exists = TeamMember::query()
                            ->where('user_id', $tenantId)
                            ->where('email', $value)
                            ->exists();
                        if ($exists) {
                            $fail('Já existe um profissional cadastrado com este e-mail no seu estabelecimento.');
                        }
                    },
                ],
                'phone' => ['nullable', 'string', 'max:50'],
                'avatar_url' => ['nullable', 'string', 'max:1000'],
                'avatar' => ['nullable', 'image', 'max:4096'],
                'subdomain' => [
                    'nullable',
                    'string',
                    'max:100',
                    'regex:/^[a-z0-9-]+$/i',
                    function ($attribute, $value, $fail) {
                        $slug = strtolower(trim($value));
                        if ($slug === '') return;
                        $existsInUsers = \App\Models\User::where('subdomain', $slug)->exists();
                        $existsInMembers = \App\Models\TeamMember::where('subdomain', $slug)->exists();
                        if ($existsInUsers || $existsInMembers) {
                            $fail('Este subdomínio já está em uso por outro usuário ou profissional.');
                        }
                    }
                ],
                'custom_domain' => ['nullable', 'string', 'max:255'],
                'bio' => ['nullable', 'string', 'max:1000'],
                'is_active' => ['nullable', 'boolean'],
                'services' => ['nullable', 'array'],
                'business_hours' => ['nullable', 'array'],
                'password' => ['nullable', 'string', 'min:6'],
                'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'service_commissions' => ['nullable', 'array'],
            ])->validate();

            $avatarUrl = $validated['avatar_url'] ?? null;

            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $avatarUrl = Storage::url($path);
            }

            $subdomain = ! empty($validated['subdomain']) ? strtolower(trim($validated['subdomain'])) : null;
            $this->assertGlobalSubdomainIsAvailable($subdomain);

            $teamMember = TeamMember::create([
                'user_id' => $tenantId,
                'name' => $validated['name'],
                'job_title' => $this->resolveJobTitle($validated),
                'role_id' => $validated['role_id'] ?? $request->input('role_id', 'professional'),
                'commission_rate' => $validated['commission_rate'] ?? 0.00,
                'service_commissions' => $validated['service_commissions'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'avatar_url' => $avatarUrl,
                'subdomain' => $subdomain,
                'custom_domain' => $validated['custom_domain'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'services' => $validated['services'] ?? null,
                'business_hours' => $validated['business_hours'] ?? null,
            ]);

            $this->syncUserRecordForMember($validated, $tenantId, $teamMember->role_id, $teamMember);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Profissional adicionado ao time com sucesso!',
                    'team_member' => $teamMember,
                ], 201);
            }

            return redirect()
                ->route('admin.team.index')
                ->with('success', 'Profissional adicionado ao time com sucesso!');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível cadastrar o profissional.');
            }

            throw $e;
        }
    }

    public function update(Request $request, TeamMember $teamMember)
    {
        try {
            $user = $request->user() ?? auth()->user();
            $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;
            abort_unless((int) $teamMember->user_id === (int) $tenantId, 404);

            $validated = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'job_title' => ['nullable', 'string', 'max:255'],
                'role_id' => ['nullable', 'string', 'in:' . implode(',', RoleCatalog::ids())],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'avatar_url' => ['nullable', 'string', 'max:1000'],
                'avatar' => ['nullable', 'image', 'max:4096'],
                'subdomain' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/i'],
                'custom_domain' => ['nullable', 'string', 'max:255'],
                'bio' => ['nullable', 'string', 'max:1000'],
                'is_active' => ['nullable', 'boolean'],
                'services' => ['nullable', 'array'],
                'business_hours' => ['nullable', 'array'],
                'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'service_commissions' => ['nullable', 'array'],
            ])->validate();

            $avatarUrl = $teamMember->avatar_url;

            if ($request->filled('avatar_url')) {
                $avatarUrl = $validated['avatar_url'];
            }

            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $avatarUrl = Storage::url($path);
            }

            $subdomain = ! empty($validated['subdomain']) ? strtolower(trim($validated['subdomain'])) : null;
            $this->assertGlobalSubdomainIsAvailable($subdomain, $teamMember->id);

            $teamMember->update([
                'name' => $validated['name'],
                'job_title' => $this->resolveJobTitle($validated),
                'role_id' => $validated['role_id'] ?? $request->input('role_id', $teamMember->role_id ?? 'professional'),
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'avatar_url' => $avatarUrl,
                'subdomain' => $subdomain,
                'custom_domain' => $validated['custom_domain'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'is_active' => $request->boolean('is_active', $teamMember->is_active),
                'services' => $validated['services'] ?? $teamMember->services,
                'business_hours' => $validated['business_hours'] ?? $teamMember->business_hours,
                'commission_rate' => $validated['commission_rate'] ?? $teamMember->commission_rate,
                'service_commissions' => $validated['service_commissions'] ?? $teamMember->service_commissions,
            ]);

            $this->syncUserRecordForMember($validated, $tenantId, $teamMember->role_id, $teamMember);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Dados do profissional atualizados com sucesso!',
                    'team_member' => $teamMember->fresh(),
                ]);
            }

            return redirect()
                ->route('admin.team.index')
                ->with('success', 'Dados do profissional atualizados com sucesso!');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível atualizar o profissional.');
            }

            throw $e;
        }
    }

    public function destroy(Request $request, TeamMember $teamMember)
    {
        try {
            $user = $request->user() ?? auth()->user();
            $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;
            abort_unless((int) $teamMember->user_id === (int) $tenantId, 404);

            DB::transaction(function () use ($teamMember, $tenantId): void {
                Appointment::query()
                    ->where('team_member_id', $teamMember->id)
                    ->update(['team_member_id' => null]);

                if ($teamMember->email) {
                    User::query()
                        ->where('parent_id', $tenantId)
                        ->where('email', $teamMember->email)
                        ->delete();
                }

                $teamMember->delete();
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Profissional removido com sucesso!',
                ]);
            }

            return redirect()
                ->route('admin.team.index')
                ->with('success', 'Profissional removido com sucesso!');
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível remover o profissional.');
            }

            throw $e;
        }
    }

    public function toggleStatus(Request $request, TeamMember $teamMember)
    {
        try {
            $user = $request->user() ?? auth()->user();
            $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;
            abort_unless((int) $teamMember->user_id === (int) $tenantId, 404);

            $teamMember->update([
                'is_active' => ! $teamMember->is_active,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Status do profissional alterado com sucesso!',
                    'team_member' => $teamMember->fresh(),
                ]);
            }

            return redirect()
                ->route('admin.team.index')
                ->with('success', 'Status do profissional alterado com sucesso!');
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível alterar o status.');
            }

            throw $e;
        }
    }

    public function resetPassword(Request $request, TeamMember $teamMember)
    {
        try {
            $user = $request->user() ?? auth()->user();
            $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;
            abort_unless((int) $teamMember->user_id === (int) $tenantId, 404);

            if (empty($teamMember->email)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'O profissional precisa ter um e-mail cadastrado para redefinir a senha.',
                    ], 422);
                }
                return redirect()->back()->with('error', 'O profissional precisa ter um e-mail cadastrado.');
            }

            // Find or create user corresponding to this professional
            $user = User::where('email', $teamMember->email)->first();

            if (! $user) {
                User::create([
                    'name' => $teamMember->name,
                    'email' => $teamMember->email,
                    'password' => \Illuminate\Support\Facades\Hash::make('agendae123'),
                    'parent_id' => $tenantId,
                    'subdomain' => $teamMember->subdomain,
                    'active_domain_type' => !empty($teamMember->subdomain) ? 'subdomain' : 'subdomain',
                    'must_reset_password' => true,
                    'role_title' => $this->roleIdToTitle((string) ($teamMember->role_id ?? 'professional')),
                ]);
            } else {
                $user->update([
                    'password' => \Illuminate\Support\Facades\Hash::make('agendae123'),
                    'must_reset_password' => true,
                    'parent_id' => $tenantId,
                    'role_title' => $this->roleIdToTitle((string) ($teamMember->role_id ?? 'professional')),
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Senha do profissional redefinida para "agendae123" com sucesso!',
                ]);
            }

            return redirect()
                ->route('admin.team.index')
                ->with('success', 'Senha do profissional redefinida para "agendae123" com sucesso!');
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Erro ao redefinir a senha.'], 500);
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function resolveJobTitle(array $validated): ?string
    {
        $jobTitle = $validated['job_title'] ?? $validated['role_title'] ?? null;

        if (! is_string($jobTitle)) {
            return null;
        }

        $jobTitle = trim($jobTitle);

        return $jobTitle === '' ? null : $jobTitle;
    }

    private function assertGlobalSubdomainIsAvailable(?string $subdomain, ?int $ignoreTeamMemberId = null): void
    {
        if ($subdomain === null || $subdomain === '') {
            return;
        }

        $normalizedSubdomain = strtolower(trim($subdomain));

        $teamMemberExists = TeamMember::query()
            ->whereRaw('LOWER(subdomain) = ?', [$normalizedSubdomain])
            ->when($ignoreTeamMemberId !== null, fn ($query) => $query->where('id', '!=', $ignoreTeamMemberId))
            ->exists();

        if ($teamMemberExists) {
            throw ValidationException::withMessages([
                'subdomain' => 'Este subdomínio já está em uso por outro profissional.',
            ]);
        }

        $userExists = User::query()
            ->whereRaw('LOWER(subdomain) = ?', [$normalizedSubdomain])
            ->exists();

        if ($userExists) {
            throw ValidationException::withMessages([
                'subdomain' => 'Este subdomínio já está em uso por outro usuário.',
            ]);
        }
    }
}
