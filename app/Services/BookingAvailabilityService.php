<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BlockedTimeSlot;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

class BookingAvailabilityService
{
    /**
     * Cache the computed slots for the current request lifecycle.
     *
     * @var array<string, array{service_id:int,date:string,slots:array<int,string>,blocked_dates:array<int,array{date:string,reason:?string}>}>
     */
    private array $slotsCache = [];

    /**
     * @return array{service_id:int,date:string,slots:array<int,string>,blocked_dates:array<int,array{date:string,reason:?string}>}
     */
    public function slotsFor(Service $service, ?string $date = null, User|TeamMember|null $professional = null): array
    {
        $date = $date ? Carbon::parse($date)->startOfDay() : now()->startOfDay();
        $tenantId = $this->resolveTenantId($service, $professional);
        $cacheKey = $tenantId . '|' . $this->resolveProfessionalCacheKey($professional) . '|' . $service->id . '|' . $date->toDateString();

        if (isset($this->slotsCache[$cacheKey])) {
            return $this->slotsCache[$cacheKey];
        }

        $tenantBusinessHours = $this->businessHoursForContext($tenantId, $professional);

        if ($tenantBusinessHours->isEmpty()) {
            return $this->slotsCache[$cacheKey] = $this->payloadFor(
                $service,
                $date,
                [],
                $this->blockedDatesForDate($date, $tenantId, $professional)
            );
        }

        $dayCandidates = $this->dayCandidatesForDate($date);
        $businessHours = $tenantBusinessHours
            ->filter(function (BusinessHour $businessHour) use ($dayCandidates): bool {
                return in_array($this->normalizeDayValue($businessHour->day_of_week), $dayCandidates, true);
            })
            ->values();

        if ($businessHours->isEmpty()) {
            return $this->slotsCache[$cacheKey] = $this->payloadFor(
                $service,
                $date,
                [],
                $this->blockedDatesForDate($date, $tenantId, $professional)
            );
        }

        $activeBusinessHours = $businessHours
            ->filter(fn (BusinessHour $businessHour): bool => (bool) $businessHour->is_active)
            ->values();

        if ($activeBusinessHours->isEmpty()) {
            return $this->slotsCache[$cacheKey] = $this->payloadFor(
                $service,
                $date,
                [],
                $this->blockedDatesForDate($date, $tenantId, $professional)
            );
        }

        $businessHours = $activeBusinessHours;

        $busyRanges = $this->mergeOverlappingRanges($this->busyRangesForDate($date, $service, $tenantId, $professional));

        foreach ($businessHours as $businessHour) {
            $breakRange = $this->breakRangeForBusinessHour($businessHour, $date);

            if ($breakRange !== null) {
                $busyRanges[] = $breakRange;
            }
        }

        $busyRanges = $this->mergeOverlappingRanges($busyRanges);
        $availableSlots = [];

        foreach ($businessHours as $businessHour) {
            $windowStart = $this->dateWithTime($date, data_get($businessHour, 'opens_at'));
            $windowEnd = $this->dateWithTime($date, data_get($businessHour, 'closes_at'));
            $slotStepMinutes = $this->slotStepMinutesFor($businessHour, $service);

            if ($windowEnd->lessThanOrEqualTo($windowStart)) {
                continue;
            }

            $totalAvailableMinutes = $windowStart->diffInMinutes($windowEnd);
            $maxIterations = max(1, intdiv($totalAvailableMinutes, $slotStepMinutes));

            for ($slotIndex = 0; $slotIndex < $maxIterations; $slotIndex++) {
                $candidate = $windowStart->copy()->addMinutes($slotIndex * $slotStepMinutes);
                $candidateEnd = $candidate->copy()->addMinutes($service->duration_minutes);

                if ($candidateEnd->gt($windowEnd)) {
                    break;
                }

                if ($this->isRangeFree($candidate, $candidateEnd, $busyRanges)) {
                    $availableSlots[] = $candidate->format('H:i');
                }
            }
        }

        $availableSlots = array_values(array_unique($availableSlots));
        sort($availableSlots);

        return $this->slotsCache[$cacheKey] = $this->payloadFor(
            $service,
            $date,
            $availableSlots,
            $this->blockedDatesForDate($date, $tenantId, $professional)
        );
    }

    public function isSlotAvailable(Service $service, string|CarbonInterface $date, string $time, User|TeamMember|null $professional = null): bool
    {
        $date = $date instanceof CarbonInterface ? Carbon::instance($date) : Carbon::parse($date);
        $normalizedTime = Carbon::parse($time)->format('H:i');

        return in_array(
            $normalizedTime,
            $this->slotsFor($service, $date->toDateString(), $professional)['slots'],
            true
        );
    }

    /**
     * @param  array<int, array{start:Carbon,end:Carbon}>  $ranges
     */
    private function isRangeFree(CarbonInterface $start, CarbonInterface $end, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if ($range['start']->greaterThanOrEqualTo($end)) {
                break;
            }

            if ($start->lt($range['end']) && $end->gt($range['start'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array{start:Carbon,end:Carbon}>
     */
    private function busyRangesForDate(CarbonInterface $date, Service $service, ?int $tenantId, User|TeamMember|null $professional = null): array
    {
        $ranges = [];
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $appointmentsQuery = Appointment::query()
            ->select([
                'appointments.appointment_date',
                'appointments.appointment_time',
                'appointments.service_id',
                'services.duration_minutes as service_duration_minutes',
            ])
            ->leftJoin('services', 'services.id', '=', 'appointments.service_id')
            ->where('appointments.appointment_date', $date->toDateString())
            ->whereIn('appointments.status', ['pending', 'confirmed'])
            ->orderBy('appointments.appointment_time');

        if ($professional instanceof TeamMember) {
            $appointmentsQuery->where('appointments.user_id', $tenantId)
                ->where('appointments.team_member_id', $professional->id);
        } elseif ($professional instanceof User) {
            $teamMemberId = $professional->parent_id
                ? TeamMember::query()
                    ->where('user_id', $tenantId)
                    ->where('email', $professional->email)
                    ->value('id')
                : null;

            $appointmentsQuery->where(function ($query) use ($professional, $tenantId, $teamMemberId): void {
                $query->where('appointments.user_id', $professional->id);

                if ($teamMemberId !== null) {
                    $query->orWhere(function ($query) use ($tenantId, $teamMemberId): void {
                        $query->where('appointments.user_id', $tenantId)
                            ->where('appointments.team_member_id', $teamMemberId);
                    });
                }
            });
        } else {
            $appointmentsQuery->where(function ($query) use ($tenantId): void {
                $query->where('appointments.user_id', $tenantId)
                    ->orWhere('services.user_id', $tenantId);
            });
        }

        $appointmentsQuery->get()
            ->each(function ($appointment) use (&$ranges, $date, $service): void {
                $start = $this->dateWithTime($date, (string) $appointment->appointment_time);
                $duration = (int) ($appointment->service_duration_minutes ?? $service->duration_minutes);

                $ranges[] = [
                    'start' => $start,
                    'end' => $start->copy()->addMinutes($duration),
                ];
            });

        $blockedSlotsQuery = BlockedTimeSlot::query()
            ->active()
            ->overlapping($startOfDay, $endOfDay)
            ->select(['starts_at', 'ends_at'])
            ->orderBy('starts_at');

        $memberId = null;
        if ($professional instanceof TeamMember) {
            $memberId = (int) $professional->id;
        } elseif ($professional instanceof User && ! empty($professional->parent_id)) {
            $memberId = TeamMember::query()
                ->where('user_id', $tenantId)
                ->where('email', $professional->email)
                ->value('id');
        }

        $blockedSlotsQuery->where(function ($query) use ($tenantId, $memberId): void {
            if ($tenantId === null) {
                $query->whereNull('blocked_time_slots.user_id');
            } else {
                $query->where('blocked_time_slots.user_id', $tenantId);
            }

            if ($memberId !== null) {
                $query->where(function ($q) use ($memberId): void {
                    $q->whereNull('blocked_time_slots.team_member_id')
                        ->orWhere('blocked_time_slots.team_member_id', $memberId);
                });
            } else {
                $query->whereNull('blocked_time_slots.team_member_id');
            }
        });

        $blockedSlotsQuery->get()
            ->each(function (BlockedTimeSlot $blockedTimeSlot) use (&$ranges, $startOfDay, $endOfDay): void {
                $ranges[] = [
                    'start' => $blockedTimeSlot->starts_at->copy()->max($startOfDay),
                    'end' => $blockedTimeSlot->ends_at->copy()->min($endOfDay),
                ];
            });

        usort($ranges, static fn (array $a, array $b): int => $a['start']->getTimestamp() <=> $b['start']->getTimestamp());

        return $this->mergeOverlappingRanges($ranges);
    }

    /**
     * @param  array<int, array{start:Carbon,end:Carbon}>  $ranges
     * @return array<int, array{start:Carbon,end:Carbon}>
     */
    private function mergeOverlappingRanges(array $ranges): array
    {
        if ($ranges === []) {
            return [];
        }

        usort(
            $ranges,
            static fn (array $left, array $right): int => $left['start']->getTimestamp() <=> $right['start']->getTimestamp()
                ?: $left['end']->getTimestamp() <=> $right['end']->getTimestamp()
        );

        $merged = [$ranges[0]];

        foreach (array_slice($ranges, 1) as $range) {
            $lastIndex = array_key_last($merged);
            $lastRange = $merged[$lastIndex];

            if ($range['start']->lte($lastRange['end'])) {
                if ($range['end']->gt($lastRange['end'])) {
                    $merged[$lastIndex]['end'] = $range['end'];
                }

                continue;
            }

            $merged[] = $range;
        }

        return $merged;
    }

    public function clearCache(): void
    {
        $this->slotsCache = [];
    }

    private function resolveTenantId(Service $service, User|TeamMember|null $professional = null): ?int
    {
        if (! empty($service->user_id)) {
            return (int) $service->user_id;
        }

        if ($professional instanceof TeamMember && ! empty($professional->user_id)) {
            return (int) $professional->user_id;
        }

        if ($professional instanceof User && ! empty($professional->parent_id)) {
            return (int) $professional->parent_id;
        }

        $tenant = app()->bound('bookingTenant') ? app('bookingTenant') : null;

        if (isset($tenant->id)) {
            return (int) $tenant->id;
        }

        $userId = auth()->id();

        if (is_int($userId) && $userId > 0) {
            return $userId;
        }

        return null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, BusinessHour>
     */
    private function businessHoursForContext(?int $tenantId, User|TeamMember|null $professional = null)
    {
        if ($tenantId === null) {
            return collect();
        }

        $defaultHours = BusinessHour::query()
            ->where('business_hours.user_id', $tenantId)
            ->whereNull('business_hours.team_member_id')
            ->get();

        $memberId = null;
        if ($professional instanceof TeamMember) {
            $memberId = (int) $professional->id;
        } elseif ($professional instanceof User && ! empty($professional->parent_id)) {
            $memberId = TeamMember::query()
                ->where('user_id', $tenantId)
                ->where('email', $professional->email)
                ->value('id');
        }

        if ($memberId !== null) {
            $customHours = BusinessHour::query()
                ->where('business_hours.user_id', $tenantId)
                ->where('business_hours.team_member_id', $memberId)
                ->get();

            if ($customHours->isNotEmpty()) {
                $customDayMap = $customHours->keyBy('day_of_week');
                $merged = collect();

                foreach ($defaultHours as $default) {
                    if ($customDayMap->has($default->day_of_week)) {
                        $merged->push($customDayMap->get($default->day_of_week));
                    } else {
                        $merged->push($default);
                    }
                }

                foreach ($customHours as $custom) {
                    if (! $merged->contains('id', $custom->id)) {
                        $merged->push($custom);
                    }
                }

                return $merged;
            }

            if ($professional instanceof TeamMember && is_array($professional->business_hours) && $professional->business_hours !== []) {
                return collect($professional->business_hours)->map(function ($item) use ($tenantId) {
                    if ($item instanceof BusinessHour) {
                        return $item;
                    }

                    return new BusinessHour((array) $item + ['user_id' => $tenantId]);
                });
            }
        }

        return $defaultHours;
    }

    private function resolveProfessionalCacheKey(User|TeamMember|null $professional): string
    {
        if ($professional instanceof TeamMember) {
            return 'team:' . $professional->id;
        }

        if ($professional instanceof User) {
            return 'user:' . $professional->id;
        }

        return 'tenant';
    }

    /**
     * @return array<int, string|int>
     */
    private function dayCandidatesForDate(CarbonInterface $date): array
    {
        $englishDay = $this->normalizeComparableDayValue($date->format('l'));
        $portugueseDays = [
            'sunday' => ['domingo'],
            'monday' => ['segunda-feira', 'segunda'],
            'tuesday' => ['terça-feira', 'terça', 'terca-feira', 'terca'],
            'wednesday' => ['quarta-feira', 'quarta'],
            'thursday' => ['quinta-feira', 'quinta'],
            'friday' => ['sexta-feira', 'sexta'],
            'saturday' => ['sábado', 'sabado'],
        ];

        $candidates = array_merge(
            [$this->normalizeComparableDayValue((string) $date->dayOfWeek), $englishDay],
            array_map(
                fn (string $candidate): string => $this->normalizeComparableDayValue($candidate),
                $portugueseDays[$englishDay] ?? []
            )
        );

        return array_values(array_unique($candidates, SORT_REGULAR));
    }

    /**
     * @param  mixed  $dayOfWeek
     * @return string|int|null
     */
    private function normalizeDayValue(mixed $dayOfWeek): ?string
    {
        if (is_int($dayOfWeek)) {
            return (string) $dayOfWeek;
        }

        if (! is_string($dayOfWeek) || $dayOfWeek === '') {
            return null;
        }

        return $this->normalizeComparableDayValue($dayOfWeek);
    }

    private function normalizeComparableDayValue(string $value): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if ($normalized === false) {
            $normalized = $value;
        }

        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '', $normalized);

        return $normalized ?? '';
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{opens_at:string,closes_at:string,slot_duration_minutes:int,is_active:bool}>
     */
    private function defaultBusinessHours(Service $service)
    {
        return collect([
            [
                'opens_at' => '08:00:00',
                'closes_at' => '18:00:00',
                'slot_duration_minutes' => (int) $service->duration_minutes,
                'is_active' => true,
            ],
        ]);
    }

    /**
     * @param  array<int, string>  $slots
     * @param  array<int, array{date:string,reason:?string}>  $blockedDates
     * @return array{service_id:int,date:string,slots:array<int,string>,blocked_dates:array<int,array{date:string,reason:?string}>}
     */
    private function payloadFor(Service $service, CarbonInterface $date, array $slots, array $blockedDates): array
    {
        return [
            'service_id' => $service->id,
            'date' => $date->toDateString(),
            'slots' => $slots,
            'blocked_dates' => $blockedDates,
        ];
    }

    /**
     * @return array<int, array{date:string,reason:?string}>
     */
    private function blockedDatesForDate(CarbonInterface $date, ?int $tenantId, User|TeamMember|null $professional = null): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $memberId = null;
        if ($professional instanceof TeamMember) {
            $memberId = (int) $professional->id;
        } elseif ($professional instanceof User && ! empty($professional->parent_id)) {
            $memberId = TeamMember::query()
                ->where('user_id', $tenantId)
                ->where('email', $professional->email)
                ->value('id');
        }

        $query = BlockedTimeSlot::query()
            ->active()
            ->overlapping($startOfDay, $endOfDay)
            ->select(['starts_at', 'reason'])
            ->orderBy('starts_at');

        $query->where(function ($builder) use ($tenantId, $memberId): void {
            if ($tenantId === null) {
                $builder->whereNull('blocked_time_slots.user_id');
            } else {
                $builder->where('blocked_time_slots.user_id', $tenantId);
            }

            if ($memberId !== null) {
                $builder->where(function ($q) use ($memberId): void {
                    $q->whereNull('blocked_time_slots.team_member_id')
                        ->orWhere('blocked_time_slots.team_member_id', $memberId);
                });
            } else {
                $builder->whereNull('blocked_time_slots.team_member_id');
            }
        });

        return $query->get()
            ->map(static function (BlockedTimeSlot $blockedTimeSlot): array {
                return [
                    'date' => $blockedTimeSlot->starts_at->toDateString(),
                    'reason' => $blockedTimeSlot->reason,
                ];
            })
            ->unique(fn (array $blockedDate): string => $blockedDate['date'] . '|' . (string) ($blockedDate['reason'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * @param  BusinessHour|array<string, mixed>  $businessHour
     */
    private function slotStepMinutesFor(BusinessHour|array $businessHour, Service $service): int
    {
        $serviceDuration = (int) $service->duration_minutes;

        if ($serviceDuration > 0) {
            return $serviceDuration;
        }

        return 30;
    }

    /**
     * @param  BusinessHour|array<string, mixed>  $businessHour
     * @return array{start:Carbon,end:Carbon}|null
     */
    private function breakRangeForBusinessHour(BusinessHour|array $businessHour, CarbonInterface $date): ?array
    {
        $breakOpensAt = data_get($businessHour, 'break_opens_at');
        $breakClosesAt = data_get($businessHour, 'break_closes_at');

        if (! is_string($breakOpensAt) || ! is_string($breakClosesAt) || $breakOpensAt === '' || $breakClosesAt === '') {
            return null;
        }

        $breakStart = $this->dateWithTime($date, $breakOpensAt);
        $breakEnd = $this->dateWithTime($date, $breakClosesAt);

        if ($breakEnd->lessThanOrEqualTo($breakStart)) {
            return null;
        }

        return [
            'start' => $breakStart,
            'end' => $breakEnd,
        ];
    }

    private function dateWithTime(CarbonInterface $date, string $time): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $date->toDateString() . ' ' . $this->normalizeTimeString($time));
    }

    private function normalizeTimeString(string $time): string
    {
        $format = strlen($time) === 5 ? 'H:i' : 'H:i:s';

        return Carbon::createFromFormat($format, $time)->format('H:i:s');
    }
}
