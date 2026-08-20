<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $report = [
            'appointments' => [
                'scanned' => 0,
                'updated' => 0,
                'from_team_member' => 0,
                'from_service' => 0,
                'conflicts' => 0,
                'unresolved' => 0,
            ],
            'services' => [
                'scanned' => 0,
                'updated' => 0,
                'from_appointments' => 0,
                'conflicts' => 0,
                'unresolved' => 0,
            ],
            'business_hours' => [
                'scanned_null_rows' => 0,
                'unresolved' => 0,
            ],
            'blocked_time_slots' => [
                'scanned_null_rows' => 0,
                'unresolved' => 0,
            ],
        ];

        if (! Schema::hasTable('appointments')) {
            Log::warning('Tenant user_id backfill skipped: appointments table does not exist.');
            return;
        }

        $teamMemberOwners = DB::table('team_members')
            ->select(['id', 'user_id'])
            ->get()
            ->keyBy('id');

        $serviceOwners = DB::table('services')
            ->select(['id', 'user_id'])
            ->get()
            ->keyBy('id');

        DB::transaction(function () use (&$report, $teamMemberOwners, $serviceOwners): void {
            $appointments = DB::table('appointments')
                ->select(['id', 'user_id', 'service_id', 'team_member_id'])
                ->orderBy('id')
                ->get();

            foreach ($appointments as $appointment) {
                $report['appointments']['scanned']++;

                $inference = $this->inferAppointmentTenant(
                    serviceId: (int) $appointment->service_id,
                    teamMemberId: $appointment->team_member_id !== null ? (int) $appointment->team_member_id : null,
                    teamMemberOwners: $teamMemberOwners,
                    serviceOwners: $serviceOwners
                );

                if ($inference['tenant_id'] === null) {
                    $report['appointments']['unresolved']++;
                    continue;
                }

                if ($inference['conflict']) {
                    $report['appointments']['conflicts']++;
                    continue;
                }

                if ((int) $appointment->user_id !== $inference['tenant_id']) {
                    DB::table('appointments')
                        ->where('id', $appointment->id)
                        ->update(['user_id' => $inference['tenant_id']]);

                    $report['appointments']['updated']++;
                    if ($inference['source'] === 'team_member') {
                        $report['appointments']['from_team_member']++;
                    } else {
                        $report['appointments']['from_service']++;
                    }
                }
            }

            $services = DB::table('services')
                ->select(['id', 'user_id'])
                ->orderBy('id')
                ->get();

            foreach ($services as $service) {
                $report['services']['scanned']++;

                $tenantIds = DB::table('appointments')
                    ->where('service_id', $service->id)
                    ->whereNotNull('user_id')
                    ->distinct()
                    ->pluck('user_id')
                    ->map(fn ($tenantId) => (int) $tenantId)
                    ->values()
                    ->all();

                if (count($tenantIds) === 0) {
                    $report['services']['unresolved']++;
                    continue;
                }

                if (count($tenantIds) > 1) {
                    $report['services']['conflicts']++;
                    continue;
                }

                $tenantId = $tenantIds[0];

                if ((int) $service->user_id !== $tenantId) {
                    DB::table('services')
                        ->where('id', $service->id)
                        ->update(['user_id' => $tenantId]);

                    $report['services']['updated']++;
                    $report['services']['from_appointments']++;
                }
            }

            if (Schema::hasTable('business_hours')) {
                $report['business_hours']['scanned_null_rows'] = (int) DB::table('business_hours')
                    ->whereNull('user_id')
                    ->count();
                $report['business_hours']['unresolved'] = $report['business_hours']['scanned_null_rows'];
            }

            if (Schema::hasTable('blocked_time_slots')) {
                $report['blocked_time_slots']['scanned_null_rows'] = (int) DB::table('blocked_time_slots')
                    ->whereNull('user_id')
                    ->count();
                $report['blocked_time_slots']['unresolved'] = $report['blocked_time_slots']['scanned_null_rows'];
            }
        });

        Log::info('Tenant user_id backfill report', $report);
    }

    public function down(): void
    {
        Log::info('Tenant user_id backfill rollback skipped: this migration only normalizes existing tenant ownership.');
    }

    /**
     * Infer the tenant for an appointment using only direct relationships.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $teamMemberOwners
     * @param  \Illuminate\Support\Collection<int, object>  $serviceOwners
     * @return array{tenant_id: int|null, source: string|null, conflict: bool}
     */
    private function inferAppointmentTenant(int $serviceId, ?int $teamMemberId, $teamMemberOwners, $serviceOwners): array
    {
        $serviceTenantId = null;
        $teamMemberTenantId = null;

        if ($teamMemberId !== null && $teamMemberOwners->has($teamMemberId)) {
            $teamMemberTenantId = (int) $teamMemberOwners->get($teamMemberId)->user_id;
        }

        if ($serviceId !== 0 && $serviceOwners->has($serviceId)) {
            $serviceTenantId = (int) $serviceOwners->get($serviceId)->user_id;
        }

        if ($teamMemberTenantId !== null && $serviceTenantId !== null) {
            if ($teamMemberTenantId !== $serviceTenantId) {
                return [
                    'tenant_id' => null,
                    'source' => null,
                    'conflict' => true,
                ];
            }

            return [
                'tenant_id' => $teamMemberTenantId,
                'source' => 'team_member',
                'conflict' => false,
            ];
        }

        if ($teamMemberTenantId !== null) {
            return [
                'tenant_id' => $teamMemberTenantId,
                'source' => 'team_member',
                'conflict' => false,
            ];
        }

        if ($serviceTenantId !== null) {
            return [
                'tenant_id' => $serviceTenantId,
                'source' => 'service',
                'conflict' => false,
            ];
        }

        return [
            'tenant_id' => null,
            'source' => null,
            'conflict' => false,
        ];
    }
};
