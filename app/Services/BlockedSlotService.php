<?php

namespace App\Services;

use App\Models\BlockedTimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BlockedSlotService
{
    public function queryForTenant(?int $tenantId)
    {
        return BlockedTimeSlot::query()
            ->with('teamMember')
            ->when($tenantId === null, fn ($query) => $query->whereNull('blocked_time_slots.user_id'), fn ($query) => $query->where(function ($subQuery) use ($tenantId): void {
                $subQuery->where('blocked_time_slots.user_id', $tenantId)
                    ->orWhereNull('blocked_time_slots.user_id');
            }));
    }

    public function snapshot(?int $tenantId): array
    {
        return $this->queryForTenant($tenantId)
            ->orderByDesc('starts_at')
            ->get()
            ->values()
            ->all();
    }

    public function validateData(array $data): array
    {
        return Validator::make(
            $data,
            [
                'team_member_id' => ['nullable', 'integer', 'exists:team_members,id'],
                'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
                'ends_at' => ['required', 'date_format:Y-m-d\TH:i'],
                'reason' => ['nullable', 'string', 'max:255'],
                'is_active' => ['nullable', 'boolean'],
            ]
        )->after(function ($validator) use ($data): void {
            $startsAt = $data['starts_at'] ?? null;
            $endsAt = $data['ends_at'] ?? null;

            if (! $startsAt || ! $endsAt) {
                return;
            }

            $start = Carbon::createFromFormat('Y-m-d\TH:i', $startsAt);
            $end = Carbon::createFromFormat('Y-m-d\TH:i', $endsAt);

            if ($end->lessThanOrEqualTo($start)) {
                $validator->errors()->add('ends_at', 'O término deve ser maior que o início.');
            }
        })->validate();
    }

    public function create(array $validated, ?int $tenantId, bool $isActive = true): BlockedTimeSlot
    {
        return BlockedTimeSlot::create([
            'user_id' => $tenantId,
            'team_member_id' => ! empty($validated['team_member_id']) ? (int) $validated['team_member_id'] : null,
            'starts_at' => Carbon::createFromFormat('Y-m-d\TH:i', $validated['starts_at']),
            'ends_at' => Carbon::createFromFormat('Y-m-d\TH:i', $validated['ends_at']),
            'reason' => $validated['reason'] ?? null,
            'is_active' => $isActive,
        ]);
    }

    public function update(BlockedTimeSlot $blockedTimeSlot, array $validated, ?bool $isActive = null): BlockedTimeSlot
    {
        $updateData = [
            'starts_at' => Carbon::createFromFormat('Y-m-d\TH:i', $validated['starts_at']),
            'ends_at' => Carbon::createFromFormat('Y-m-d\TH:i', $validated['ends_at']),
            'reason' => $validated['reason'] ?? null,
            'is_active' => $isActive !== null ? $isActive : $blockedTimeSlot->is_active,
        ];

        if (array_key_exists('team_member_id', $validated)) {
            $updateData['team_member_id'] = ! empty($validated['team_member_id']) ? (int) $validated['team_member_id'] : null;
        }

        $blockedTimeSlot->update($updateData);

        return $blockedTimeSlot;
    }
}
