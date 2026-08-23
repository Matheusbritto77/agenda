<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->parent_id) {
            $tenantId = (int) $user->parent_id;
            $teamMember = \App\Models\TeamMember::where('user_id', $tenantId)
                ->where('email', $user->email)
                ->first();
        } else {
            $tenantId = (int) $user->id;
            $teamMember = null;
        }

        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $parsedDate = Carbon::parse($selectedDate);
        $startOfWeek = $parsedDate->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $parsedDate->copy()->endOfWeek(Carbon::SATURDAY);

        $appointments = collect();
        $weekAppointments = collect();

        // Enforce appointments.view permission
        if ($user->hasPermission('appointments.view')) {
            $showAll = ! $user->parent_id || $user->hasPermission('appointments.view_all');

            $appointmentsQuery = Appointment::with(['service', 'teamMember'])
                ->where('appointments.user_id', $tenantId)
                ->where('appointment_date', $selectedDate)
                ->orderBy('appointment_time', 'asc');

            if (! $showAll) {
                if ($teamMember) {
                    $appointmentsQuery->where('appointments.team_member_id', $teamMember->id);
                } else {
                    $appointmentsQuery->whereNull('appointments.id');
                }
            }

            $appointments = $appointmentsQuery->get();

            $weekAppointmentsQuery = Appointment::with(['service', 'teamMember'])
                ->where('appointments.user_id', $tenantId)
                ->whereBetween('appointment_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc');

            if (! $showAll) {
                if ($teamMember) {
                    $weekAppointmentsQuery->where('appointments.team_member_id', $teamMember->id);
                } else {
                    $weekAppointmentsQuery->whereNull('appointments.id');
                }
            }

            $weekAppointments = $weekAppointmentsQuery->get();
        }

        $stats = [
            'today_total' => 0,
            'confirmed_total' => 0,
            'completed_total' => 0,
            'total_appointments' => 0,
            'week_total' => 0,
        ];

        // Appointment KPI cards should match the visible agenda scope.
        if ($user->hasPermission('appointments.view') || $user->hasPermission('reports.view') || $user->hasPermission('reports.view_all')) {
            $showAll = ! $user->parent_id || $user->hasPermission('appointments.view_all') || $user->hasPermission('reports.view_all');

            $scopedAppointments = function () use ($tenantId, $showAll, $teamMember) {
                $query = Appointment::query()
                    ->where('appointments.user_id', $tenantId);

                if (! $showAll) {
                    if ($teamMember) {
                        $query->where('appointments.team_member_id', $teamMember->id);
                    } else {
                        $query->whereNull('appointments.id');
                    }
                }

                return $query;
            };

            $stats = [
                'today_total' => $scopedAppointments()
                    ->whereDate('appointment_date', $selectedDate)
                    ->count(),
                'confirmed_total' => $scopedAppointments()
                    ->whereDate('appointment_date', $selectedDate)
                    ->where('status', 'confirmed')
                    ->count(),
                'completed_total' => $scopedAppointments()
                    ->whereDate('appointment_date', $selectedDate)
                    ->where('status', 'completed')
                    ->count(),
                'total_appointments' => $scopedAppointments()
                    ->count(),
                'week_total' => $scopedAppointments()
                    ->whereBetween('appointment_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
                    ->count(),
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'appointments' => $appointments,
            'weekAppointments' => $weekAppointments,
            'selectedDate' => $selectedDate,
            'stats' => $stats,
            'startOfWeek' => $startOfWeek->toDateString(),
            'endOfWeek' => $endOfWeek->toDateString(),
            'teamMember' => $teamMember,
        ]);
    }

    public function cancelAppointment($id)
    {
        $user = auth()->user();
        if (! $user || ! $user->hasPermission('appointments.cancel')) {
            abort(403, 'Você não tem permissão para realizar esta ação.');
        }

        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;
        $appointment = Appointment::query()->where('appointments.user_id', $tenantId)->findOrFail($id);
        $appointment->update(['status' => 'cancelled']);

        return back()->with('success', 'Agendamento cancelado com sucesso.');
    }
}
