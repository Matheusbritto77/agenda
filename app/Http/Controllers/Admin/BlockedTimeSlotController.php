<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedTimeSlot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class BlockedTimeSlotController extends Controller
{
    public function update(Request $request, string $id)
    {
        try {
            $tenantId = $request->user()?->id ?? auth()->id();

            if (! is_int($tenantId) && ! is_string($tenantId)) {
                abort(404, 'Bloqueio não encontrado.');
            }

            $blockedTimeSlot = BlockedTimeSlot::query()
                ->where(function ($query) use ($tenantId): void {
                    $query->where('user_id', $tenantId)
                        ->orWhereNull('user_id');
                })
                ->findOrFail($id);

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

            $validated = $request->validate([
                'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
                'ends_at' => ['required', 'date_format:Y-m-d\TH:i'],
                'reason' => ['nullable', 'string', 'max:255'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $startsAt = Carbon::createFromFormat('Y-m-d\TH:i', $validated['starts_at']);
            $endsAt = Carbon::createFromFormat('Y-m-d\TH:i', $validated['ends_at']);

            if ($endsAt->lessThanOrEqualTo($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'O término deve ser maior que o início.',
                ]);
            }

            $blockedTimeSlot->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'reason' => $this->sanitizeText($validated['reason'] ?? $validated['title'] ?? null),
                'is_active' => $request->boolean('is_active', $blockedTimeSlot->is_active),
            ]);

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Bloqueio atualizado com sucesso.', [
                    'blocked_slot' => $blockedTimeSlot->fresh(),
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

    private function resolveDateTime(mixed $dateValue, mixed $timeValue): ?Carbon
    {
        $date = $this->normalizeDateValue($dateValue);
        $time = $this->normalizeTimeValue($timeValue);

        if ($date === null && $time === null) {
            return null;
        }

        if ($date !== null && $time !== null) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time);
        }

        $value = $date ?? $time;

        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/', $value) === 1) {
            return Carbon::parse($value);
        }

        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) === 1) {
            return Carbon::parse($time !== null ? ($date ?? now()->toDateString()) . ' ' . $time : $value);
        }

        if ($date !== null) {
            return Carbon::parse($date . ' ' . ($time ?? '00:00:00'));
        }

        return Carbon::parse($value);
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    private function normalizeTimeValue(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return $value;
        }

        return null;
    }
}
