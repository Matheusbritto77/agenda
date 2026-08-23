<?php

namespace App\Services;

use App\Models\BusinessHour;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BusinessHourService
{
    public function queryForTenant(?int $tenantId)
    {
        return BusinessHour::query()
            ->with('teamMember')
            ->when($tenantId === null, fn ($query) => $query->whereNull('business_hours.user_id'), fn ($query) => $query->where(function ($subQuery) use ($tenantId): void {
                $subQuery->where('business_hours.user_id', $tenantId)
                    ->orWhereNull('business_hours.user_id');
            }));
    }

    public function snapshot(?int $tenantId): array
    {
        return $this->queryForTenant($tenantId)
            ->orderBy('day_of_week')
            ->orderBy('opens_at')
            ->get()
            ->values()
            ->all();
    }

    public function validateData(array $data, bool $isUpdate = false): array
    {
        return Validator::make(
            $data,
            [
                'team_member_id' => ['nullable', 'integer', 'exists:team_members,id'],
                'day_of_week' => [$isUpdate ? 'nullable' : 'required', 'integer', 'min:0', 'max:6'],
                'label' => ['nullable', 'string', 'max:255'],
                'opens_at' => ['required', 'date_format:H:i'],
                'closes_at' => ['required', 'date_format:H:i'],
                'slot_interval_minutes' => ['nullable', 'integer', 'min:5', 'max:720'],
                'slot_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:720'],
                'has_break' => ['nullable', 'boolean'],
                'break_opens_at' => ['nullable', 'date_format:H:i'],
                'break_closes_at' => ['nullable', 'date_format:H:i'],
                'breaks' => ['nullable', 'array'],
                'breaks.*.opens_at' => ['nullable', 'date_format:H:i'],
                'breaks.*.closes_at' => ['nullable', 'date_format:H:i'],
                'breaks.*.label' => ['nullable', 'string', 'max:100'],
                'is_active' => ['nullable', 'boolean'],
            ]
        )->after(function ($validator) use ($data): void {
            $opensAt = $data['opens_at'] ?? null;
            $closesAt = $data['closes_at'] ?? null;
            $breakOpensAt = $data['break_opens_at'] ?? null;
            $breakClosesAt = $data['break_closes_at'] ?? null;

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

            // Validate breaks array
            if (! empty($data['breaks']) && is_array($data['breaks'])) {
                foreach ($data['breaks'] as $idx => $b) {
                    $bOpen = $b['opens_at'] ?? null;
                    $bClose = $b['closes_at'] ?? null;
                    if ($bOpen && $bClose) {
                        $bo = Carbon::createFromFormat('H:i', $bOpen);
                        $bc = Carbon::createFromFormat('H:i', $bClose);
                        if ($bc->lessThanOrEqualTo($bo)) {
                            $validator->errors()->add("breaks.{$idx}.closes_at", 'O término do intervalo deve ser maior que o início.');
                        }
                        if ($bo->lt($opens) || $bc->gt($closes)) {
                            $validator->errors()->add("breaks.{$idx}.opens_at", 'O intervalo deve ficar dentro do horário de expediente.');
                        }
                    }
                }
            }
        })->validate();
    }

    public function ensureUniqueDayOfWeek(?int $userId, int $dayOfWeek, ?int $ignoreId = null, ?int $teamMemberId = null): void
    {
        if ($userId === null) {
            return;
        }

        $query = BusinessHour::query()
            ->where('business_hours.user_id', $userId)
            ->where('business_hours.day_of_week', $dayOfWeek);

        if ($teamMemberId === null) {
            $query->whereNull('business_hours.team_member_id');
        } else {
            $query->where('business_hours.team_member_id', $teamMemberId);
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            $msg = $teamMemberId
                ? 'Este profissional já possui cadastro de horário para este dia da semana.'
                : 'Este dia já possui cadastro de horário de funcionamento padrão.';
            throw ValidationException::withMessages([
                'day_of_week' => $msg,
            ]);
        }
    }

    public function create(array $validated, ?int $tenantId, bool $hasBreak = false, bool $isActive = true): BusinessHour
    {
        $teamMemberId = ! empty($validated['team_member_id']) ? (int) $validated['team_member_id'] : null;
        $this->ensureUniqueDayOfWeek($tenantId, (int) $validated['day_of_week'], null, $teamMemberId);

        $slotMinutes = $validated['slot_duration_minutes'] ?? $validated['slot_interval_minutes'] ?? 45;

        // Process breaks array
        $breaksList = [];
        if (! empty($validated['breaks']) && is_array($validated['breaks'])) {
            foreach ($validated['breaks'] as $b) {
                if (! empty($b['opens_at']) && ! empty($b['closes_at'])) {
                    $breaksList[] = [
                        'opens_at' => Carbon::createFromFormat('H:i', $b['opens_at'])->format('H:i:s'),
                        'closes_at' => Carbon::createFromFormat('H:i', $b['closes_at'])->format('H:i:s'),
                        'label' => $b['label'] ?? null,
                    ];
                }
            }
        }

        if (empty($breaksList) && $hasBreak && ! empty($validated['break_opens_at']) && ! empty($validated['break_closes_at'])) {
            $breaksList[] = [
                'opens_at' => Carbon::createFromFormat('H:i', $validated['break_opens_at'])->format('H:i:s'),
                'closes_at' => Carbon::createFromFormat('H:i', $validated['break_closes_at'])->format('H:i:s'),
                'label' => 'Intervalo Principal',
            ];
        }

        $createData = [
            'user_id' => $tenantId,
            'team_member_id' => $teamMemberId,
            'day_of_week' => (int) $validated['day_of_week'],
            'label' => $validated['label'] ?? null,
            'opens_at' => Carbon::createFromFormat('H:i', $validated['opens_at'])->format('H:i:s'),
            'closes_at' => Carbon::createFromFormat('H:i', $validated['closes_at'])->format('H:i:s'),
            'breaks' => ! empty($breaksList) ? $breaksList : null,
            'is_active' => $isActive,
        ];

        if (Schema::hasColumn('business_hours', 'slot_duration_minutes')) {
            $createData['slot_duration_minutes'] = $slotMinutes;
        }
        if (Schema::hasColumn('business_hours', 'slot_interval_minutes')) {
            $createData['slot_interval_minutes'] = $slotMinutes;
        }
        if (Schema::hasColumn('business_hours', 'break_opens_at')) {
            $createData['break_opens_at'] = ! empty($breaksList[0]['opens_at']) ? $breaksList[0]['opens_at'] : null;
        }
        if (Schema::hasColumn('business_hours', 'break_closes_at')) {
            $createData['break_closes_at'] = ! empty($breaksList[0]['closes_at']) ? $breaksList[0]['closes_at'] : null;
        }

        return BusinessHour::create($createData);
    }

    public function update(BusinessHour $businessHour, array $validated, ?int $tenantId, bool $hasBreak = false, ?bool $isActive = null): BusinessHour
    {
        $teamMemberId = array_key_exists('team_member_id', $validated)
            ? (! empty($validated['team_member_id']) ? (int) $validated['team_member_id'] : null)
            : $businessHour->team_member_id;

        if (isset($validated['day_of_week']) || array_key_exists('team_member_id', $validated)) {
            $dayOfWeek = isset($validated['day_of_week']) ? (int) $validated['day_of_week'] : $businessHour->day_of_week;
            $this->ensureUniqueDayOfWeek(
                $tenantId,
                $dayOfWeek,
                (int) $businessHour->id,
                $teamMemberId
            );
        }

        $slotMinutes = $validated['slot_duration_minutes'] ?? $validated['slot_interval_minutes'] ?? $businessHour->slot_duration_minutes ?? $businessHour->slot_interval_minutes ?? 45;

        // Process breaks array
        $breaksList = [];
        if (! empty($validated['breaks']) && is_array($validated['breaks'])) {
            foreach ($validated['breaks'] as $b) {
                if (! empty($b['opens_at']) && ! empty($b['closes_at'])) {
                    $breaksList[] = [
                        'opens_at' => Carbon::createFromFormat('H:i', $b['opens_at'])->format('H:i:s'),
                        'closes_at' => Carbon::createFromFormat('H:i', $b['closes_at'])->format('H:i:s'),
                        'label' => $b['label'] ?? null,
                    ];
                }
            }
        }

        if (empty($breaksList) && $hasBreak && ! empty($validated['break_opens_at']) && ! empty($validated['break_closes_at'])) {
            $breaksList[] = [
                'opens_at' => Carbon::createFromFormat('H:i', $validated['break_opens_at'])->format('H:i:s'),
                'closes_at' => Carbon::createFromFormat('H:i', $validated['break_closes_at'])->format('H:i:s'),
                'label' => 'Intervalo Principal',
            ];
        }

        $updateData = [
            'team_member_id' => $teamMemberId,
            'day_of_week' => isset($validated['day_of_week']) ? (int) $validated['day_of_week'] : $businessHour->day_of_week,
            'label' => $validated['label'] ?? null,
            'opens_at' => Carbon::createFromFormat('H:i', $validated['opens_at'])->format('H:i:s'),
            'closes_at' => Carbon::createFromFormat('H:i', $validated['closes_at'])->format('H:i:s'),
            'breaks' => ! empty($breaksList) ? $breaksList : null,
            'is_active' => $isActive !== null ? $isActive : $businessHour->is_active,
        ];

        if (Schema::hasColumn('business_hours', 'slot_duration_minutes')) {
            $updateData['slot_duration_minutes'] = $slotMinutes;
        }
        if (Schema::hasColumn('business_hours', 'slot_interval_minutes')) {
            $updateData['slot_interval_minutes'] = $slotMinutes;
        }
        if (Schema::hasColumn('business_hours', 'break_opens_at')) {
            $updateData['break_opens_at'] = ! empty($breaksList[0]['opens_at']) ? $breaksList[0]['opens_at'] : null;
        }
        if (Schema::hasColumn('business_hours', 'break_closes_at')) {
            $updateData['break_closes_at'] = ! empty($breaksList[0]['closes_at']) ? $breaksList[0]['closes_at'] : null;
        }

        $businessHour->update($updateData);

        return $businessHour;
    }
}
