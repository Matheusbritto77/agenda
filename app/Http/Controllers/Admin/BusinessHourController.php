<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedTimeSlot;
use App\Models\BusinessHour;
use App\Models\User;
use App\Services\BlockedSlotService;
use App\Services\BusinessHourService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class BusinessHourController extends Controller
{
    public function __construct(
        protected BusinessHourService $businessHourService,
        protected BlockedSlotService $blockedSlotService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasPermission('schedules.view')) {
            abort(403, 'Você não tem permissão para acessar esta página.');
        }

        $tenantId = $this->tenantId($request);

        $businessHours = $this->businessHourService->queryForTenant($tenantId)
            ->orderBy('day_of_week')
            ->orderBy('opens_at')
            ->get();

        $blockedSlots = $this->blockedSlotService->queryForTenant($tenantId)
            ->orderByDesc('starts_at')
            ->get();

        $teamMembers = \App\Models\TeamMember::query()
            ->where('user_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'job_title', 'avatar_url', 'subdomain', 'is_active']);

        if ($request->expectsJson()) {
            return response()->json([
                'business_hours' => $businessHours,
                'blocked_slots' => $blockedSlots,
                'team_members' => $teamMembers,
            ]);
        }

        return Inertia::render('Admin/BusinessHours/Index', compact('businessHours', 'blockedSlots', 'teamMembers'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->businessHourService->validateData($request->all());
            $tenantId = $this->tenantId($request);

            $businessHour = $this->businessHourService->create(
                $validated,
                $tenantId,
                $request->boolean('has_break'),
                $request->boolean('is_active', true)
            );

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Horário de funcionamento cadastrado com sucesso.', [
                    'business_hour' => $businessHour->fresh(),
                    'business_hours' => $this->businessHourService->snapshot($tenantId),
                    'blocked_slots' => $this->blockedSlotService->snapshot($tenantId),
                ], 201);
            }

            return redirect()
                ->route('admin.business-hours.index')
                ->with('success', 'Horário de funcionamento cadastrado com sucesso.');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }
            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);
            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível cadastrar o horário de funcionamento.');
            }
            throw $e;
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $tenantId = $this->tenantId($request);
            $businessHour = $this->businessHourService->queryForTenant($tenantId)->findOrFail($id);

            $this->authorizeScheduleUpdate($request, $businessHour);

            $validated = $this->businessHourService->validateData($request->all(), true);

            $this->businessHourService->update(
                $businessHour,
                $validated,
                $tenantId,
                $request->boolean('has_break'),
                $request->has('is_active') ? $request->boolean('is_active') : null
            );

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Horário de funcionamento atualizado com sucesso.', [
                    'business_hour' => $businessHour->fresh(),
                    'business_hours' => $this->businessHourService->snapshot($tenantId),
                    'blocked_slots' => $this->blockedSlotService->snapshot($tenantId),
                ]);
            }

            return redirect()
                ->route('admin.business-hours.index')
                ->with('success', 'Horário de funcionamento atualizado com sucesso.');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }
            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);
            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível atualizar o horário de funcionamento.');
            }
            throw $e;
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $tenantId = $this->tenantId($request);
            $businessHour = $this->businessHourService->queryForTenant($tenantId)->findOrFail($id);
            $deletedBusinessHour = $businessHour->toArray();
            $businessHour->delete();

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Horário de funcionamento removido com sucesso.', [
                    'business_hour' => $deletedBusinessHour,
                    'business_hours' => $this->businessHourService->snapshot($tenantId),
                    'blocked_slots' => $this->blockedSlotService->snapshot($tenantId),
                ]);
            }

            return redirect()
                ->route('admin.business-hours.index')
                ->with('success', 'Horário de funcionamento removido com sucesso.');
        } catch (Throwable $e) {
            $this->reportThrowable($e);
            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível excluir o horário de funcionamento.');
            }
            throw $e;
        }
    }

    public function storeBlock(Request $request)
    {
        try {
            $validated = $this->blockedSlotService->validateData($request->all());
            $tenantId = $this->tenantId($request);

            $blockedSlot = $this->blockedSlotService->create(
                $validated,
                $tenantId,
                $request->boolean('is_active', true)
            );

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Bloqueio criado com sucesso.', [
                    'blocked_slot' => $blockedSlot,
                    'business_hours' => $this->businessHourService->snapshot($tenantId),
                    'blocked_slots' => $this->blockedSlotService->snapshot($tenantId),
                ], 201);
            }

            return redirect()
                ->route('admin.business-hours.index')
                ->with('success', 'Bloqueio criado com sucesso.');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }
            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);
            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível criar o bloqueio.');
            }
            throw $e;
        }
    }

    public function updateBlock(Request $request, BlockedTimeSlot $blockedTimeSlot)
    {
        try {
            $tenantId = $this->tenantId($request);
            abort_unless((int) $blockedTimeSlot->user_id === $tenantId, 404);

            $this->normalizeBlockInput($request);

            $validated = $this->blockedSlotService->validateData($request->all());

            $this->blockedSlotService->update(
                $blockedTimeSlot,
                $validated,
                $request->has('is_active') ? $request->boolean('is_active') : null
            );

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Bloqueio atualizado com sucesso.', [
                    'blocked_slot' => $blockedTimeSlot->fresh(),
                    'business_hours' => $this->businessHourService->snapshot($tenantId),
                    'blocked_slots' => $this->blockedSlotService->snapshot($tenantId),
                ]);
            }

            return redirect()
                ->route('admin.business-hours.index')
                ->with('success', 'Bloqueio atualizado com sucesso.');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }
            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);
            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível atualizar o bloqueio.');
            }
            throw $e;
        }
    }

    public function destroyBlock(Request $request, BlockedTimeSlot $blockedTimeSlot)
    {
        try {
            $tenantId = $this->tenantId($request);
            abort_unless((int) $blockedTimeSlot->user_id === $tenantId, 404);

            $deletedBlock = $blockedTimeSlot->toArray();
            $blockedTimeSlot->delete();

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Bloqueio removido com sucesso.', [
                    'blocked_slot' => $deletedBlock,
                    'business_hours' => $this->businessHourService->snapshot($tenantId),
                    'blocked_slots' => $this->blockedSlotService->snapshot($tenantId),
                ]);
            }

            return redirect()
                ->route('admin.business-hours.index')
                ->with('success', 'Bloqueio removido com sucesso.');
        } catch (Throwable $e) {
            $this->reportThrowable($e);
            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível remover o bloqueio.');
            }
            throw $e;
        }
    }

    private function authorizeScheduleUpdate(Request $request, BusinessHour $businessHour): void
    {
        if (! $request->user()->hasPermission('schedules.manage')) {
            if ($request->user()->hasPermission('schedules.breaks')) {
                $changedMain = false;

                if ($request->has('day_of_week') && (int) $request->input('day_of_week') !== (int) $businessHour->day_of_week) {
                    $changedMain = true;
                }
                if ($request->has('label') && $this->sanitizeText($request->input('label')) !== $businessHour->label) {
                    $changedMain = true;
                }
                if ($request->has('opens_at') && Carbon::createFromFormat('H:i', $request->input('opens_at'))->format('H:i:s') !== $businessHour->opens_at) {
                    $changedMain = true;
                }
                if ($request->has('closes_at') && Carbon::createFromFormat('H:i', $request->input('closes_at'))->format('H:i:s') !== $businessHour->closes_at) {
                    $changedMain = true;
                }
                if ($request->has('is_active') && $request->boolean('is_active') !== (bool) $businessHour->is_active) {
                    $changedMain = true;
                }

                $newSlotMinutes = $request->input('slot_duration_minutes') ?? $request->input('slot_interval_minutes') ?? null;
                if ($newSlotMinutes !== null && (int) $newSlotMinutes !== (int) ($businessHour->slot_duration_minutes ?? $businessHour->slot_interval_minutes)) {
                    $changedMain = true;
                }

                if ($changedMain) {
                    throw ValidationException::withMessages([
                        'schedules' => 'Você não tem permissão para alterar o expediente principal (apenas intervalos/pausas).',
                    ]);
                }
            } else {
                abort(403, 'Você não tem permissão para realizar esta ação.');
            }
        }
    }

    private function normalizeBlockInput(Request $request): void
    {
        if ($request->has('start_date') && $request->has('start_time')) {
            $startDate = $request->input('start_date');
            $startTime = $request->input('start_time');
            if ($startDate && $startTime) {
                $request->merge(['starts_at' => $startDate . 'T' . substr($startTime, 0, 5)]);
            }
        }

        if ($request->has('end_date') && $request->has('end_time')) {
            $endDate = $request->input('end_date');
            $endTime = $request->input('end_time');
            if ($endDate && $endTime) {
                $request->merge(['ends_at' => $endDate . 'T' . substr($endTime, 0, 5)]);
            }
        }

        if ($request->has('label') && ! $request->has('reason')) {
            $request->merge(['reason' => $request->input('label')]);
        }
    }

    private function tenantId(Request $request): ?int
    {
        $user = $request->user();
        if ($user) {
            $tenantId = $user->parent_id ?: $user->id;
        } else {
            $tenantId = auth()->id();
        }

        if ($tenantId === null) {
            $tenantId = User::query()->first()?->id;
        }

        return $tenantId !== null ? (int) $tenantId : null;
    }
}
