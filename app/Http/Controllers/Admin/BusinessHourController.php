<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedTimeSlot;
use App\Models\BusinessHour;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class BusinessHourController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasPermission('schedules.view')) {
            abort(403, 'Você não tem permissão para acessar esta página.');
        }

        $tenantId = $this->tenantId($request);

        $businessHours = $this->businessHourQueryForTenant($tenantId)
            ->orderBy('day_of_week')
            ->orderBy('opens_at')
            ->get();

        $blockedSlots = $this->blockedSlotQueryForTenant($tenantId)
            ->orderByDesc('starts_at')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'business_hours' => $businessHours,
                'blocked_slots' => $blockedSlots,
            ]);
        }

        return Inertia::render('Admin/BusinessHours/Index', compact('businessHours', 'blockedSlots'));
    }

    public function store(Request $request)
    {
        try {
            $validated = Validator::make(
                $request->all(),
                [
                    'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
                    'label' => ['nullable', 'string', 'max:255'],
                    'opens_at' => ['required', 'date_format:H:i'],
                    'closes_at' => ['required', 'date_format:H:i'],
                    'slot_interval_minutes' => ['nullable', 'integer', 'min:5', 'max:720'],
                    'break_opens_at' => ['nullable', 'date_format:H:i'],
                    'break_closes_at' => ['nullable', 'date_format:H:i'],
                    'is_active' => ['nullable', 'boolean'],
                ]
            )->after(function ($validator) use ($request): void {
                $opensAt = $request->input('opens_at');
                $closesAt = $request->input('closes_at');
                $breakOpensAt = $request->input('break_opens_at');
                $breakClosesAt = $request->input('break_closes_at');

                if (! $opensAt || ! $closesAt) {
                    return;
                }

                $opens = Carbon::createFromFormat('H:i', $opensAt);
                $closes = Carbon::createFromFormat('H:i', $closesAt);

                if ($closes->lessThanOrEqualTo($opens)) {
                    $validator->errors()->add('closes_at', 'O horário de fechamento deve ser maior que o de abertura.');
                }

                if ($breakOpensAt && ! $breakClosesAt) {
                    $validator->errors()->add('break_closes_at', 'Informe o término da pausa.');
                }

                if ($breakClosesAt && ! $breakOpensAt) {
                    $validator->errors()->add('break_opens_at', 'Informe o início da pausa.');
                }

                if ($breakOpensAt && $breakClosesAt) {
                    $breakOpens = Carbon::createFromFormat('H:i', $breakOpensAt);
                    $breakCloses = Carbon::createFromFormat('H:i', $breakClosesAt);

                    if ($breakCloses->lessThanOrEqualTo($breakOpens)) {
                        $validator->errors()->add('break_closes_at', 'O término da pausa deve ser maior que o início.');
                    }

                    if ($breakOpens->lt($opens) || $breakCloses->gt($closes)) {
                        $validator->errors()->add('break_opens_at', 'A pausa deve ficar dentro do horário de funcionamento.');
                    }
                }
            })->validate();

            $tenantId = $this->tenantId($request);

            $this->ensureUniqueDayOfWeek($tenantId, (int) $validated['day_of_week']);

            $slotMinutes = $validated['slot_duration_minutes'] ?? $validated['slot_interval_minutes'] ?? 45;
            $hasBreak = $request->boolean('has_break');

            $createData = [
                'user_id' => $tenantId,
                'day_of_week' => (int) $validated['day_of_week'],
                'label' => $this->sanitizeText($validated['label'] ?? null),
                'opens_at' => Carbon::createFromFormat('H:i', $validated['opens_at'])->format('H:i:s'),
                'closes_at' => Carbon::createFromFormat('H:i', $validated['closes_at'])->format('H:i:s'),
                'is_active' => $request->boolean('is_active', true),
            ];

            if (Schema::hasColumn('business_hours', 'slot_duration_minutes')) {
                $createData['slot_duration_minutes'] = $slotMinutes;
            }
            if (Schema::hasColumn('business_hours', 'slot_interval_minutes')) {
                $createData['slot_interval_minutes'] = $slotMinutes;
            }
            if (Schema::hasColumn('business_hours', 'break_opens_at') && $hasBreak && ! empty($validated['break_opens_at'])) {
                $createData['break_opens_at'] = Carbon::createFromFormat('H:i', $validated['break_opens_at'])->format('H:i:s');
            }
            if (Schema::hasColumn('business_hours', 'break_closes_at') && $hasBreak && ! empty($validated['break_closes_at'])) {
                $createData['break_closes_at'] = Carbon::createFromFormat('H:i', $validated['break_closes_at'])->format('H:i:s');
            }

            $businessHour = BusinessHour::create($createData);

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Horário de funcionamento cadastrado com sucesso.', [
                    'business_hour' => $businessHour->fresh(),
                    'business_hours' => $this->businessHoursSnapshot($request),
                    'blocked_slots' => $this->blockedSlotsSnapshot($request),
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
            $businessHour = $this->businessHourQueryForTenant($tenantId)
                ->findOrFail($id);

            if (! $request->user()->hasPermission('schedules.manage')) {
                if ($request->user()->hasPermission('schedules.breaks')) {
                    $changedMainFields = false;

                    if ($request->has('day_of_week') && (int) $request->input('day_of_week') !== (int) $businessHour->day_of_week) {
                        $changedMainFields = true;
                    }
                    if ($request->has('label') && $this->sanitizeText($request->input('label')) !== $businessHour->label) {
                        $changedMainFields = true;
                    }
                    if ($request->has('opens_at') && Carbon::createFromFormat('H:i', $request->input('opens_at'))->format('H:i:s') !== $businessHour->opens_at) {
                        $changedMainFields = true;
                    }
                    if ($request->has('closes_at') && Carbon::createFromFormat('H:i', $request->input('closes_at'))->format('H:i:s') !== $businessHour->closes_at) {
                        $changedMainFields = true;
                    }
                    if ($request->has('is_active') && $request->boolean('is_active') !== (bool) $businessHour->is_active) {
                        $changedMainFields = true;
                    }

                    $newSlotMinutes = $request->input('slot_duration_minutes') ?? $request->input('slot_interval_minutes') ?? null;
                    if ($newSlotMinutes !== null && (int) $newSlotMinutes !== (int) ($businessHour->slot_duration_minutes ?? $businessHour->slot_interval_minutes)) {
                        $changedMainFields = true;
                    }

                    if ($changedMainFields) {
                        throw ValidationException::withMessages([
                            'schedules' => 'Você não tem permissão para alterar o expediente principal (apenas intervalos/pausas).',
                        ]);
                    }
                } else {
                    abort(403, 'Você não tem permissão para realizar esta ação.');
                }
            }

            $validated = Validator::make(
                $request->all(),
                [
                    'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
                    'label' => ['nullable', 'string', 'max:255'],
                    'opens_at' => ['required', 'date_format:H:i'],
                    'closes_at' => ['required', 'date_format:H:i'],
                    'slot_interval_minutes' => ['nullable', 'integer', 'min:5', 'max:720'],
                    'slot_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:720'],
                    'has_break' => ['nullable', 'boolean'],
                    'break_opens_at' => ['nullable', 'date_format:H:i'],
                    'break_closes_at' => ['nullable', 'date_format:H:i'],
                    'is_active' => ['nullable', 'boolean'],
                ]
            )->after(function ($validator) use ($request): void {
                $opensAt = $request->input('opens_at');
                $closesAt = $request->input('closes_at');
                $breakOpensAt = $request->input('break_opens_at');
                $breakClosesAt = $request->input('break_closes_at');

                if (! $opensAt || ! $closesAt) {
                    return;
                }

                $opens = Carbon::createFromFormat('H:i', $opensAt);
                $closes = Carbon::createFromFormat('H:i', $closesAt);

                if ($closes->lessThanOrEqualTo($opens)) {
                    $validator->errors()->add('closes_at', 'O horário de fechamento deve ser maior que o de abertura.');
                }

                if ($breakOpensAt && ! $breakClosesAt) {
                    $validator->errors()->add('break_closes_at', 'Informe o término da pausa.');
                }

                if ($breakClosesAt && ! $breakOpensAt) {
                    $validator->errors()->add('break_opens_at', 'Informe o início da pausa.');
                }

                if ($breakOpensAt && $breakClosesAt) {
                    $breakOpens = Carbon::createFromFormat('H:i', $breakOpensAt);
                    $breakCloses = Carbon::createFromFormat('H:i', $breakClosesAt);

                    if ($breakCloses->lessThanOrEqualTo($breakOpens)) {
                        $validator->errors()->add('break_closes_at', 'O término da pausa deve ser maior que o início.');
                    }

                    if ($breakOpens->lt($opens) || $breakCloses->gt($closes)) {
                        $validator->errors()->add('break_opens_at', 'A pausa deve ficar dentro do horário de funcionamento.');
                    }
                }
            })->validate();

            if (isset($validated['day_of_week'])) {
                $this->ensureUniqueDayOfWeek(
                    $tenantId,
                    (int) $validated['day_of_week'],
                    (int) $businessHour->id
                );
            }

            $slotMinutes = $validated['slot_duration_minutes'] ?? $validated['slot_interval_minutes'] ?? $businessHour->slot_duration_minutes ?? $businessHour->slot_interval_minutes ?? 45;
            $hasBreak = $request->boolean('has_break');

            $updateData = [
                'day_of_week' => isset($validated['day_of_week']) ? (int) $validated['day_of_week'] : $businessHour->day_of_week,
                'label' => $this->sanitizeText($validated['label'] ?? null),
                'opens_at' => Carbon::createFromFormat('H:i', $validated['opens_at'])->format('H:i:s'),
                'closes_at' => Carbon::createFromFormat('H:i', $validated['closes_at'])->format('H:i:s'),
                'is_active' => $request->boolean('is_active', $businessHour->is_active),
            ];

            if (Schema::hasColumn('business_hours', 'slot_duration_minutes')) {
                $updateData['slot_duration_minutes'] = $slotMinutes;
            }
            if (Schema::hasColumn('business_hours', 'slot_interval_minutes')) {
                $updateData['slot_interval_minutes'] = $slotMinutes;
            }
            if (Schema::hasColumn('business_hours', 'break_opens_at')) {
                $updateData['break_opens_at'] = $hasBreak && ! empty($validated['break_opens_at'])
                    ? Carbon::createFromFormat('H:i', $validated['break_opens_at'])->format('H:i:s')
                    : null;
            }
            if (Schema::hasColumn('business_hours', 'break_closes_at')) {
                $updateData['break_closes_at'] = $hasBreak && ! empty($validated['break_closes_at'])
                    ? Carbon::createFromFormat('H:i', $validated['break_closes_at'])->format('H:i:s')
                    : null;
            }

            $businessHour->update($updateData);

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Horário de funcionamento atualizado com sucesso.', [
                    'business_hour' => $businessHour->fresh(),
                    'business_hours' => $this->businessHoursSnapshot($request),
                    'blocked_slots' => $this->blockedSlotsSnapshot($request),
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
            $businessHour = $this->businessHourQueryForTenant($tenantId)
                ->findOrFail($id);
            $deletedBusinessHour = $businessHour->toArray();
            $businessHour->delete();

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Horário de funcionamento removido com sucesso.', [
                    'business_hour' => $deletedBusinessHour,
                    'business_hours' => $this->businessHoursSnapshot($request),
                    'blocked_slots' => $this->blockedSlotsSnapshot($request),
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
            $validated = Validator::make(
                $request->all(),
                [
                    'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
                    'ends_at' => ['required', 'date_format:Y-m-d\TH:i'],
                    'reason' => ['nullable', 'string', 'max:255'],
                    'is_active' => ['nullable', 'boolean'],
                ]
            )->after(function ($validator) use ($request): void {
                $startsAt = $request->input('starts_at');
                $endsAt = $request->input('ends_at');

                if (! $startsAt || ! $endsAt) {
                    return;
                }

                $start = Carbon::createFromFormat('Y-m-d\TH:i', $startsAt);
                $end = Carbon::createFromFormat('Y-m-d\TH:i', $endsAt);

                if ($end->lessThanOrEqualTo($start)) {
                    $validator->errors()->add('ends_at', 'O término deve ser maior que o início.');
                }
            })->validate();

            $blockedSlot = BlockedTimeSlot::create([
                'user_id' => $this->tenantId($request),
                'starts_at' => Carbon::createFromFormat('Y-m-d\TH:i', $validated['starts_at']),
                'ends_at' => Carbon::createFromFormat('Y-m-d\TH:i', $validated['ends_at']),
                'reason' => $this->sanitizeText($validated['reason'] ?? null),
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Bloqueio criado com sucesso.', [
                    'blocked_slot' => $blockedSlot,
                    'business_hours' => $this->businessHoursSnapshot($request),
                    'blocked_slots' => $this->blockedSlotsSnapshot($request),
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

            $validated = Validator::make(
                $request->all(),
                [
                    'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
                    'ends_at' => ['required', 'date_format:Y-m-d\TH:i'],
                    'reason' => ['nullable', 'string', 'max:255'],
                    'is_active' => ['nullable', 'boolean'],
                ]
            )->after(function ($validator) use ($request): void {
                $startsAt = $request->input('starts_at');
                $endsAt = $request->input('ends_at');

                if (! $startsAt || ! $endsAt) {
                    return;
                }

                $start = Carbon::createFromFormat('Y-m-d\TH:i', $startsAt);
                $end = Carbon::createFromFormat('Y-m-d\TH:i', $endsAt);

                if ($end->lessThanOrEqualTo($start)) {
                    $validator->errors()->add('ends_at', 'O término deve ser maior que o início.');
                }
            })->validate();

            $blockedTimeSlot->update([
                'starts_at' => Carbon::createFromFormat('Y-m-d\TH:i', $validated['starts_at']),
                'ends_at' => Carbon::createFromFormat('Y-m-d\TH:i', $validated['ends_at']),
                'reason' => $this->sanitizeText($validated['reason'] ?? null),
                'is_active' => $request->boolean('is_active', $blockedTimeSlot->is_active),
            ]);

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Bloqueio atualizado com sucesso.', [
                    'blocked_slot' => $blockedTimeSlot->fresh(),
                    'business_hours' => $this->businessHoursSnapshot($request),
                    'blocked_slots' => $this->blockedSlotsSnapshot($request),
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
                    'business_hours' => $this->businessHoursSnapshot($request),
                    'blocked_slots' => $this->blockedSlotsSnapshot($request),
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function businessHoursSnapshot(Request $request): array
    {
        $tenantId = $this->tenantId($request);

        return BusinessHour::query()
            ->when($tenantId === null, fn ($query) => $query->whereNull('business_hours.user_id'), fn ($query) => $query->where(function ($subQuery) use ($tenantId): void {
                $subQuery->where('business_hours.user_id', $tenantId)
                    ->orWhereNull('business_hours.user_id');
            }))
            ->orderBy('day_of_week')
            ->orderBy('opens_at')
            ->get()
            ->values()
            ->all();
    }

    private function blockedSlotsSnapshot(Request $request): array
    {
        $tenantId = $this->tenantId($request);

        return BlockedTimeSlot::query()
            ->when($tenantId === null, fn ($query) => $query->whereNull('blocked_time_slots.user_id'), fn ($query) => $query->where(function ($subQuery) use ($tenantId): void {
                $subQuery->where('blocked_time_slots.user_id', $tenantId)
                    ->orWhereNull('blocked_time_slots.user_id');
            }))
            ->orderByDesc('starts_at')
            ->get()
            ->values()
            ->all();
    }

    private function ensureUniqueDayOfWeek(?int $userId, int $dayOfWeek, ?int $ignoreId = null): void
    {
        if ($userId === null) {
            return;
        }

        $query = BusinessHour::query()
            ->where('business_hours.user_id', $userId)
            ->where('business_hours.day_of_week', $dayOfWeek);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'day_of_week' => 'Este dia já possui cadastro de horário de funcionamento.',
            ]);
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

    private function businessHourQueryForTenant(?int $tenantId)
    {
        return BusinessHour::query()
            ->when($tenantId === null, fn ($query) => $query->whereNull('business_hours.user_id'), fn ($query) => $query->where(function ($subQuery) use ($tenantId): void {
                $subQuery->where('business_hours.user_id', $tenantId)
                    ->orWhereNull('business_hours.user_id');
            }));
    }

    private function blockedSlotQueryForTenant(?int $tenantId)
    {
        return BlockedTimeSlot::query()
            ->when($tenantId === null, fn ($query) => $query->whereNull('blocked_time_slots.user_id'), fn ($query) => $query->where(function ($subQuery) use ($tenantId): void {
                $subQuery->where('blocked_time_slots.user_id', $tenantId)
                    ->orWhereNull('blocked_time_slots.user_id');
            }));
    }
}
