<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\TeamMember;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminAppointmentController extends Controller
{
    /**
     * Store a manually created appointment for the admin dashboard.
     */
    public function store(Request $request, BookingAvailabilityService $availabilityService): RedirectResponse|JsonResponse
    {
        try {
            $tenantId = (int) $request->user()->id;
            $request->merge([
                'client_name' => $this->sanitizeText($request->input('client_name')),
                'client_email' => $this->sanitizeEmail($request->input('client_email')),
                'client_phone' => $this->sanitizeText($request->input('client_phone')),
                'notes' => $this->sanitizeText($request->input('notes')),
                'status' => $this->normalizeStatus($request->input('status')),
            ]);

            $validated = $request->validate([
                'client_name' => ['required', 'string', 'max:255'],
                'client_email' => ['nullable', 'string', 'email', 'max:255'],
                'client_phone' => ['nullable', 'string', 'max:20'],
                'service_id' => [
                    'required',
                    'integer',
                    Rule::exists('services', 'id')->where(fn ($query) => $query->where('services.user_id', $tenantId)),
                ],
                'team_member_id' => [
                    'nullable',
                    Rule::exists('team_members', 'id')->where(fn ($query) => $query->where('team_members.user_id', $tenantId)),
                ],
                'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
                'appointment_time' => ['required', 'date_format:H:i'],
                'status' => ['required', Rule::in(['pending', 'confirmed'])],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);

            $service = Service::query()
                ->where('services.user_id', $tenantId)
                ->where('is_active', true)
                ->findOrFail($validated['service_id']);

            $teamMember = ! empty($validated['team_member_id'])
                ? TeamMember::query()->where('user_id', $tenantId)->find($validated['team_member_id'])
                : null;

            if (! $availabilityService->isSlotAvailable($service, $validated['appointment_date'], $validated['appointment_time'], $teamMember)) {
                throw ValidationException::withMessages([
                    'appointment_time' => 'O horário selecionado não está mais disponível.',
                ]);
            }

            $appointment = Appointment::create([
                'service_id' => $service->id,
                'team_member_id' => $validated['team_member_id'] ?? null,
                'client_name' => $validated['client_name'],
                'client_email' => $validated['client_email'] ?? null,
                'client_phone' => $validated['client_phone'] ?? null,
                'appointment_date' => $validated['appointment_date'],
                'appointment_time' => $validated['appointment_time'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'user_id' => $tenantId,
            ]);

            if ($request->expectsJson()) {
                $appointment = $appointment->load('service');

                return response()->json([
                    'message' => 'Agendamento interno criado com sucesso.',
                    'appointment' => $appointment,
                    'appointment_detail' => $this->formatAppointmentDetail($appointment),
                    'agenda_appointment' => $this->formatAgendaAppointment($appointment),
                    'calendar_event' => $this->formatCalendarEvent($appointment),
                ], 201);
            }

            return redirect()
                ->route('admin.appointments.index')
                ->with('success', 'Agendamento interno criado com sucesso.');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível criar o agendamento interno.');
            }

            throw $e;
        }
    }

    private function normalizeStatus(mixed $status): ?string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            'pendente' => 'pending',
            'confirmado' => 'confirmed',
            'pending', 'confirmed' => $status,
            default => null,
        };
    }

    private function formatAgendaAppointment(Appointment $appointment): array
    {
        $service = $appointment->service;

        return [
            'id' => $appointment->id,
            'appointment_date' => $appointment->appointment_date instanceof Carbon
                ? $appointment->appointment_date->format('Y-m-d')
                : Carbon::parse($appointment->appointment_date)->format('Y-m-d'),
            'appointment_time' => $appointment->appointment_time,
            'end_time' => $appointment->end_time,
            'client_name' => $appointment->client_name,
            'service_name' => $service?->name ?? '',
            'status' => $appointment->status,
        ];
    }

    private function formatAppointmentDetail(Appointment $appointment): array
    {
        $service = $appointment->service;

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
            'status' => $appointment->status,
            'notes' => $appointment->notes,
            'appointment_datetime' => $appointment->appointment_datetime?->toIso8601String(),
        ];
    }

    private function formatCalendarEvent(Appointment $appointment): array
    {
        $service = $appointment->service;
        $dateStr = $appointment->appointment_date instanceof Carbon
            ? $appointment->appointment_date->format('Y-m-d')
            : Carbon::parse($appointment->appointment_date)->format('Y-m-d');

        return [
            'id' => $appointment->id,
            'title' => $appointment->client_name . ' - ' . ($service?->name ?? 'Serviço'),
            'start' => $dateStr . 'T' . $appointment->appointment_time,
            'end' => $dateStr . 'T' . $appointment->end_time,
            'extendedProps' => [
                'client_name' => $appointment->client_name,
                'client_email' => $appointment->client_email,
                'client_phone' => $appointment->client_phone,
                'service_name' => $service?->name ?? '',
                'service_price' => $service?->price ?? 0,
                'status' => $appointment->status,
                'notes' => $appointment->notes,
            ],
        ];
    }
}
