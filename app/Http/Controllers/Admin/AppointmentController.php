<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\TeamMember;
use App\Services\AppointmentNotificationService;
use App\Services\ClientPortalProvisioningService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class AppointmentController extends Controller
{
    private function tenantId(Request $request): int
    {
        $user = $request->user();

        return $user->parent_id ? (int) $user->parent_id : (int) $user->id;
    }

    private function getTeamMember(Request $request): ?TeamMember
    {
        $user = $request->user();
        if ($user->parent_id) {
            return TeamMember::where('user_id', $user->parent_id)
                ->where('email', $user->email)
                ->first();
        }

        return null;
    }

    public function store(Request $request, ClientPortalProvisioningService $clientPortal)
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'service_id' => [
                'required',
                Rule::exists('services', 'id')->where(fn ($query) => $query->where('services.user_id', $tenantId)),
            ],
            'team_member_id' => [
                'nullable',
                Rule::exists('team_members', 'id')->where(fn ($query) => $query->where('team_members.user_id', $tenantId)),
            ],
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'required|string|max:50',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required|string',
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appointment = Appointment::create($validated + ['user_id' => $tenantId]);
        $clientPortal->provisionFor($appointment);

        return back()->with('success', 'Agendamento manual criado com sucesso!');
    }

    public function index(Request $request)
    {
        try {
            $tenantId = $this->tenantId($request);
            $query = Appointment::query()
                ->where('appointments.user_id', $tenantId)
                ->with(['service', 'teamMember', 'review']);

            if ($request->filled('status')) {
                $query->where('status', strtolower(trim((string) $request->status)));
            }

            if ($request->filled('date')) {
                $query->where('appointment_date', $request->date);
            }

            $showAll = ! $request->user()->parent_id || $request->user()->hasPermission('appointments.view_all');
            if (! $showAll) {
                $teamMember = $this->getTeamMember($request);
                if ($teamMember) {
                    $query->where('appointments.team_member_id', $teamMember->id);
                } else {
                    $query->whereNull('appointments.id');
                }
            }

            $appointments = $query->orderBy('appointment_date', 'desc')
                ->orderBy('appointment_time', 'asc')
                ->paginate(15);

            if ($request->expectsJson()) {
                $formattedAppointments = $appointments->getCollection()
                    ->map(function (Appointment $appointment) {
                        return $this->formatAgendaAppointment($appointment);
                    })
                    ->values();

                return response()->json([
                    'appointments' => $formattedAppointments,
                    'appointments_by_date' => $formattedAppointments->groupBy('appointment_date')->mapWithKeys(function ($items, string $date) {
                        return [
                            $date => [
                                'date' => $date,
                                'items' => $items->values(),
                            ],
                        ];
                    })->all(),
                    'pagination' => [
                        'current_page' => $appointments->currentPage(),
                        'last_page' => $appointments->lastPage(),
                        'per_page' => $appointments->perPage(),
                        'total' => $appointments->total(),
                    ],
                ]);
            }

            return Inertia::render('Admin/Appointments/Index', [
                'appointments' => $appointments->items(),
                'pagination' => [
                    'links' => $appointments->linkCollection()->toArray(),
                    'from' => $appointments->firstItem(),
                    'to' => $appointments->lastItem(),
                    'total' => $appointments->total(),
                    'current_page' => $appointments->currentPage(),
                    'last_page' => $appointments->lastPage(),
                    'per_page' => $appointments->perPage(),
                ],
                'status' => $request->status ?? '',
                'date' => $request->date ?? '',
            ]);
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível carregar os agendamentos.');
            }

            throw $e;
        }
    }

    public function show(Request $request, Appointment $appointment)
    {
        try {
            $tenantId = $this->tenantId($request);
            if ((int) $appointment->user_id !== $tenantId) {
                abort(404);
            }

            $appointment->load(['service', 'teamMember']);
            $payload = $this->formatAgendaAppointmentDetail($appointment);

            if ($request->expectsJson()) {
                return response()->json([
                    'appointment' => $payload,
                ]);
            }

            return back()->with('appointment_modal', $payload);
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível carregar os dados do agendamento.');
            }

            throw $e;
        }
    }

    public function updateStatus(
        Request $request,
        Appointment $appointment,
        AppointmentNotificationService $notifications
    ) {
        try {
            $tenantId = $this->tenantId($request);
            if ($appointment->user_id && (int) $appointment->user_id !== $tenantId) {
                abort(404);
            }

            $request->merge([
                'status' => strtolower(trim((string) $request->input('status'))),
            ]);

            $validated = $request->validate([
                'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            ]);

            $previousStatus = $appointment->status;
            $appointment->update([
                'status' => $validated['status'],
            ]);

            \App\Models\AppointmentFlowLog::record(
                $tenantId,
                'status_changed',
                "Status Alterado para " . ucfirst($validated['status']),
                "Agendamento de {$appointment->client_name} teve o status alterado de '{$previousStatus}' para '{$validated['status']}' pelo painel administrativo.",
                $appointment->id,
                'system',
                $validated['status'] === 'confirmed' ? 'success' : ($validated['status'] === 'cancelled' ? 'danger' : 'info'),
                ['previous_status' => $previousStatus, 'new_status' => $validated['status']]
            );

            if ($validated['status'] === 'confirmed' && $previousStatus !== 'confirmed') {
                try {
                    $payment = \App\Models\Payment::where('appointment_id', $appointment->id)->first();
                    app(\App\Services\NotificationDispatcherService::class)->onBookingApproved($appointment->fresh(['service']), $payment);
                } catch (\Throwable $err) {
                    \Illuminate\Support\Facades\Log::warning('Erro ao disparar notificação de aprovação: ' . $err->getMessage());
                }
            }

            if ($validated['status'] === 'cancelled' && $previousStatus !== 'cancelled') {
                try {
                    app(\App\Services\NotificationDispatcherService::class)->onBookingCancelled($appointment->fresh(['service']));
                } catch (\Throwable $err) {
                    \Illuminate\Support\Facades\Log::warning('Erro ao disparar notificação de cancelamento: ' . $err->getMessage());
                }
            }

            if ($validated['status'] === 'completed' && $previousStatus !== 'completed') {
                $notifications->sendCompletion($appointment->fresh(['service', 'tenant', 'teamMember']));
            }

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Status atualizado com sucesso.', [
                    'appointment' => $appointment->load(['service', 'teamMember']),
                    'appointment_detail' => $this->formatAgendaAppointmentDetail($appointment->fresh(['service', 'teamMember'])),
                    'agenda_appointment' => $this->formatAgendaAppointment($appointment->fresh(['service', 'teamMember'])),
                    'calendar_event' => $this->formatCalendarEvent($appointment->fresh(['service', 'teamMember'])),
                ]);
            }

            return back()->with('success', 'Status do agendamento atualizado com sucesso.');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível atualizar o status do agendamento.');
            }

            throw $e;
        }
    }

    public function events(Request $request)
    {
        try {
            $validated = Validator::make(
                $request->only(['start', 'end']),
                [
                    'start' => ['nullable', 'date'],
                    'end' => ['nullable', 'date'],
                ]
            )->validate();

            $tenantId = $this->tenantId($request);
            $query = Appointment::query()
                ->where('appointments.user_id', $tenantId)
                ->with(['service', 'teamMember']);

            if (! empty($validated['start']) && ! empty($validated['end'])) {
                $startDate = Carbon::parse($validated['start'])->toDateString();
                $endDate = Carbon::parse($validated['end'])->toDateString();
                $query->whereBetween('appointment_date', [$startDate, $endDate]);
            }

            $showAll = ! $request->user()->parent_id || $request->user()->hasPermission('appointments.view_all');
            if (! $showAll) {
                $teamMember = $this->getTeamMember($request);
                if ($teamMember) {
                    $query->where('appointments.team_member_id', $teamMember->id);
                } else {
                    $query->whereNull('appointments.id');
                }
            }

            $appointments = $query->get();

            $events = $appointments->map(function ($appointment) {
                $color = match ($appointment->status) {
                    'confirmed' => '#22c55e',
                    'pending' => '#f59e0b',
                    'cancelled' => '#ef4444',
                    'completed' => '#3b82f6',
                    default => '#6b7280',
                };

                $dateStr = $appointment->appointment_date instanceof Carbon
                    ? $appointment->appointment_date->format('Y-m-d')
                    : Carbon::parse($appointment->appointment_date)->format('Y-m-d');

                $teamMemberName = $appointment->teamMember?->name;

                return [
                    'id' => $appointment->id,
                    'title' => $appointment->customer_name.' - '.($appointment->service->name ?? 'Serviço').($teamMemberName ? ' ('.$teamMemberName.')' : ''),
                    'start' => $dateStr.'T'.$appointment->start_time,
                    'end' => $dateStr.'T'.$appointment->end_time,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'customer_name' => $appointment->customer_name,
                        'customer_email' => $appointment->customer_email,
                        'customer_phone' => $appointment->customer_phone,
                        'service_name' => $appointment->service->name ?? '',
                        'service_price' => $appointment->service->price ?? 0,
                        'team_member_name' => $teamMemberName,
                        'team_member' => $appointment->teamMember ? [
                            'id' => $appointment->teamMember->id,
                            'name' => $appointment->teamMember->name,
                            'job_title' => $appointment->teamMember->job_title,
                            'avatar_url' => $appointment->teamMember->avatar_url,
                        ] : null,
                        'status' => $appointment->status,
                        'notes' => $appointment->notes,
                    ],
                ];
            });

            return response()->json([
                'events' => $events,
            ]);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível carregar os eventos dos agendamentos.');
            }

            throw $e;
        }
    }

    private function formatAgendaAppointment(Appointment $appointment): array
    {
        $service = $appointment->service;
        $teamMember = $appointment->teamMember;

        return [
            'id' => $appointment->id,
            'appointment_date' => $appointment->appointment_date instanceof Carbon
                ? $appointment->appointment_date->format('Y-m-d')
                : Carbon::parse($appointment->appointment_date)->format('Y-m-d'),
            'appointment_time' => $appointment->appointment_time,
            'end_time' => $appointment->end_time,
            'client_name' => $appointment->client_name,
            'client_email' => $appointment->client_email,
            'client_phone' => $appointment->client_phone,
            'service_id' => $appointment->service_id,
            'service_name' => $service?->name ?? '',
            'service_price' => $service?->price ?? 0,
            'service_duration_minutes' => $service?->duration_minutes ?? 0,
            'team_member_id' => $appointment->team_member_id,
            'team_member_name' => $teamMember?->name,
            'team_member' => $teamMember ? [
                'id' => $teamMember->id,
                'name' => $teamMember->name,
                'job_title' => $teamMember->job_title,
                'avatar_url' => $teamMember->avatar_url,
            ] : null,
            'status' => $appointment->status,
            'notes' => $appointment->notes,
            'review' => $appointment->review ? [
                'id' => $appointment->review->id,
                'rating' => (int) $appointment->review->rating,
                'comment' => $appointment->review->comment,
                'is_public' => (bool) $appointment->review->is_public,
                'created_at' => $appointment->review->created_at?->format('d/m/Y H:i'),
            ] : null,
            'appointment_datetime' => $appointment->appointment_datetime?->toIso8601String(),
        ];
    }

    private function formatAgendaAppointmentDetail(Appointment $appointment): array
    {
        $service = $appointment->service;
        $teamMember = $appointment->teamMember;

        return [
            'id' => $appointment->id,
            'appointment_date' => $appointment->appointment_date instanceof Carbon
                ? $appointment->appointment_date->format('Y-m-d')
                : Carbon::parse($appointment->appointment_date)->format('Y-m-d'),
            'appointment_time' => $appointment->appointment_time,
            'end_time' => $appointment->end_time,
            'client_name' => $appointment->client_name,
            'client_email' => $appointment->client_email,
            'client_phone' => $appointment->client_phone,
            'service_id' => $appointment->service_id,
            'service_name' => $service?->name ?? '',
            'service_price' => $service?->price ?? 0,
            'service_duration_minutes' => $service?->duration_minutes ?? 0,
            'team_member_id' => $appointment->team_member_id,
            'team_member_name' => $teamMember?->name,
            'team_member' => $teamMember ? [
                'id' => $teamMember->id,
                'name' => $teamMember->name,
                'job_title' => $teamMember->job_title,
                'avatar_url' => $teamMember->avatar_url,
            ] : null,
            'status' => $appointment->status,
            'notes' => $appointment->notes,
            'review' => $appointment->review ? [
                'id' => $appointment->review->id,
                'rating' => (int) $appointment->review->rating,
                'comment' => $appointment->review->comment,
                'is_public' => (bool) $appointment->review->is_public,
                'created_at' => $appointment->review->created_at?->format('d/m/Y H:i'),
            ] : null,
            'appointment_datetime' => $appointment->appointment_datetime?->toIso8601String(),
        ];
    }

    public function toggleReviewPublic(Request $request, \App\Models\AppointmentReview $review)
    {
        $tenantId = $this->tenantId($request);
        abort_unless((int) $review->appointment->user_id === (int) $tenantId, 403);

        $review->update([
            'is_public' => ! $review->is_public,
        ]);

        $message = $review->is_public 
            ? 'Avaliação aprovada para exibição pública na página da empresa!' 
            : 'Avaliação mantida como feedback interno do atendimento.';

        if ($request->expectsJson()) {
            return $this->jsonSuccess($request, $message, [
                'is_public' => $review->is_public,
                'review' => [
                    'id' => $review->id,
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'is_public' => (bool) $review->is_public,
                    'created_at' => $review->created_at?->format('d/m/Y H:i'),
                ],
            ]);
        }

        return back()->with('success', $message);
    }

    private function formatCalendarEvent(Appointment $appointment): array
    {
        $service = $appointment->service;
        $teamMember = $appointment->teamMember;
        $dateStr = $appointment->appointment_date instanceof Carbon
            ? $appointment->appointment_date->format('Y-m-d')
            : Carbon::parse($appointment->appointment_date)->format('Y-m-d');

        return [
            'id' => $appointment->id,
            'title' => $appointment->customer_name.' - '.($service?->name ?? 'Serviço').($teamMember ? ' ('.$teamMember->name.')' : ''),
            'start' => $dateStr.'T'.$appointment->start_time,
            'end' => $dateStr.'T'.$appointment->end_time,
            'extendedProps' => [
                'customer_name' => $appointment->customer_name,
                'customer_email' => $appointment->customer_email,
                'customer_phone' => $appointment->customer_phone,
                'service_name' => $service?->name ?? '',
                'service_price' => $service?->price ?? 0,
                'team_member_name' => $teamMember?->name,
                'team_member' => $teamMember ? [
                    'id' => $teamMember->id,
                    'name' => $teamMember->name,
                    'job_title' => $teamMember->job_title,
                    'avatar_url' => $teamMember->avatar_url,
                ] : null,
                'status' => $appointment->status,
                'notes' => $appointment->notes,
                'review' => $appointment->review ? [
                    'id' => $appointment->review->id,
                    'rating' => (int) $appointment->review->rating,
                    'comment' => $appointment->review->comment,
                    'is_public' => (bool) $appointment->review->is_public,
                ] : null,
            ],
        ];
    }
}
