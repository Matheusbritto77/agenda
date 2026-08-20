<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\BlockedTimeSlot;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PublicBookingController extends Controller
{
    private const OPENING_TIME = '08:00';
    private const CLOSING_TIME = '18:00';
    private const SLOT_STEP_MINUTES = 15;
    private const BOOKABLE_STATUSES = ['pending', 'confirmed'];

    public function landing(Request $request): Response
    {
        return Inertia::render('Welcome')->toResponse($request);
    }

    public function index(Request $request)
    {
        if ($this->shouldRenderLandingPage($request)) {
            return $this->landing($request);
        }

        $context = $this->resolveBookingContext($request);

        if ($request->expectsJson()) {
            return response()->json([
                'has_team' => $context['hasTeam'],
                'services' => $context['services'],
                'blocked_slots' => $context['blockedSlots'],
                'team_members' => $context['teamMembers'],
                'professionals' => $context['teamMembers'],
                'selected_professional' => $context['selectedProfessional'],
                'is_owner_page' => $context['isOwnerPage'],
                'payment_enabled' => $context['paymentEnabled'],
                'payment_gateway' => $context['paymentGateway'],
                'branding' => $context['branding'],
            ]);
        }

        return Inertia::render('Client/Booking', [
            'services' => $context['services'],
            'blockedSlots' => $context['blockedSlots'],
            'teamMembers' => $context['teamMembers'],
            'professionals' => $context['teamMembers'],
            'selectedProfessional' => $context['selectedProfessional'],
            'hasTeam' => $context['hasTeam'],
            'isOwnerPage' => $context['isOwnerPage'],
            'paymentEnabled' => $context['paymentEnabled'],
            'paymentGateway' => $context['paymentGateway'],
            'branding' => $context['branding'],
            'company' => [
                'id' => $context['company']->id,
                'name' => $context['company']->name,
                'email' => $context['company']->email,
            ],
            'tenant' => [
                'id' => $context['tenant']->id,
                'name' => $context['tenant']->name,
                'email' => $context['tenant']->email,
            ],
        ]);
    }

    public function booking(Request $request)
    {
        $context = $this->resolveBookingContext($request);

        if ($request->expectsJson()) {
            return response()->json([
                'has_team' => $context['hasTeam'],
                'services' => $context['services'],
                'blocked_slots' => $context['blockedSlots'],
                'team_members' => $context['teamMembers'],
                'professionals' => $context['teamMembers'],
                'selected_professional' => $context['selectedProfessional'],
                'is_owner_page' => $context['isOwnerPage'],
                'payment_enabled' => $context['paymentEnabled'],
                'payment_gateway' => $context['paymentGateway'],
                'branding' => $context['branding'],
                'company' => [
                    'id' => $context['company']->id,
                    'name' => $context['company']->name,
                    'email' => $context['company']->email,
                ],
                'tenant' => [
                    'id' => $context['tenant']->id,
                    'name' => $context['tenant']->name,
                    'email' => $context['tenant']->email,
                ],
            ]);
        }

        return Inertia::render('Client/Booking', [
            'services' => $context['services'],
            'blockedSlots' => $context['blockedSlots'],
            'teamMembers' => $context['teamMembers'],
            'professionals' => $context['teamMembers'],
            'selectedProfessional' => $context['selectedProfessional'],
            'hasTeam' => $context['hasTeam'],
            'isOwnerPage' => $context['isOwnerPage'],
            'paymentEnabled' => $context['paymentEnabled'],
            'paymentGateway' => $context['paymentGateway'],
            'branding' => $context['branding'],
            'company' => [
                'id' => $context['company']->id,
                'name' => $context['company']->name,
                'email' => $context['company']->email,
            ],
            'tenant' => [
                'id' => $context['tenant']->id,
                'name' => $context['tenant']->name,
                'email' => $context['tenant']->email,
            ],
        ]);
    }

    private function resolveBookingContext(Request $request): array
    {
        $tenant = $this->resolveTenant($request);
        $company = $tenant->parent ?? $tenant;
        $selectedProfessional = $this->resolveSelectedProfessional($request, $tenant, $company);
        $schedulingUser = $selectedProfessional instanceof User ? $selectedProfessional : $tenant;
        $teamMembers = $this->publicTeamMembersForTenant($company);
        $hasTeam = $tenant->parent_id === null && $teamMembers->isNotEmpty();
        $isOwnerPage = $tenant->parent_id === null;

        $services = $this->publicServicesQuery($company)->latest()->get();

        $paymentSetting = \App\Models\PaymentSetting::query()
            ->where('user_id', $company->id)
            ->where('gateway', 'mercadopago')
            ->first();

        $paymentEnabled = $paymentSetting ? (bool) $paymentSetting->is_active : false;
        $paymentGateway = $paymentSetting ? $paymentSetting->gateway : 'mercadopago';

        $branding = \App\Models\BrandingSetting::query()
            ->where('user_id', $company->id)
            ->first();

        return [
            'tenant' => $tenant,
            'company' => $company,
            'selectedProfessional' => $this->presentProfessional($selectedProfessional),
            'schedulingUser' => $schedulingUser,
            'teamMembers' => $hasTeam ? $teamMembers : collect(),
            'hasTeam' => $hasTeam,
            'isOwnerPage' => $isOwnerPage,
            'services' => $services,
            'blockedSlots' => $this->publicBlockedSlotsForTenant($schedulingUser),
            'paymentEnabled' => $paymentEnabled,
            'paymentGateway' => $paymentGateway,
            'branding' => $branding,
        ];
    }

    private function publicTeamMembersForTenant(User $company): Collection
    {
        $userMembers = $company->teamMembers()->orderBy('role_title')->orderBy('name')->get();

        if ($userMembers->isNotEmpty()) {
            return $userMembers
                ->map(fn (User $member): array => $this->presentProfessional($member))
                ->values();
        }

        return TeamMember::query()
            ->where('user_id', $company->id)
            ->where('is_active', true)
            ->orderBy('job_title')
            ->orderBy('name')
            ->get()
            ->map(fn (TeamMember $member): array => $this->presentProfessional($member))
            ->values();
    }

    private function resolveSelectedProfessional(Request $request, User $tenant, User $company): User|TeamMember|null
    {
        if ($tenant->parent_id !== null) {
            return $tenant;
        }

        $param = $request->attributes->get('selectedProfessional')
            ?? $request->input('team_member_id')
            ?? $request->input('professional')
            ?? $request->input('professional_id');

        if ($param instanceof User && (int) $param->parent_id === (int) $company->id) {
            return $param;
        }

        if ($param instanceof TeamMember && (int) $param->user_id === (int) $company->id) {
            return $param;
        }

        if (is_numeric($param)) {
            return TeamMember::query()->where('user_id', $company->id)->whereKey((int) $param)->first()
                ?? $company->teamMembers()->whereKey((int) $param)->first();
        }

        if (is_string($param) && $param !== '') {
            $normalized = strtolower(trim($param));

            $teamMemberMatch = TeamMember::query()
                ->where('user_id', $company->id)
                ->where(function ($query) use ($normalized): void {
                    $query->whereRaw('LOWER(subdomain) = ?', [$normalized])
                        ->orWhereRaw('LOWER(custom_domain) = ?', [$normalized])
                        ->orWhereRaw('LOWER(name) = ?', [$normalized]);
                })
                ->first();

            if ($teamMemberMatch instanceof TeamMember) {
                return $teamMemberMatch;
            }

            return $company->teamMembers()
                ->where(function ($query) use ($normalized): void {
                    $query->whereRaw('LOWER(subdomain) = ?', [$normalized])
                        ->orWhereRaw('LOWER(custom_domain) = ?', [$normalized])
                        ->orWhereRaw('LOWER(name) = ?', [$normalized])
                        ->orWhereRaw('LOWER(email) = ?', [$normalized]);
                })
                ->first();
        }

        return null;
    }

    private function presentProfessional(User|TeamMember|null $member): ?array
    {
        if ($member === null) {
            return null;
        }

        if ($member instanceof TeamMember) {
            return [
                'id' => $member->id,
                'name' => $member->name,
                'job_title' => $member->job_title ?? 'Especialista',
                'role_title' => $member->job_title,
                'avatar_url' => $member->avatar_url,
                'subdomain' => $member->subdomain,
                'custom_domain' => $member->custom_domain,
                'active_domain_type' => null,
                'services' => $member->services ?? [],
                'bio' => $member->bio,
                'public_booking_url' => $member->publicBookingUrl(),
            ];
        }

        return [
            'id' => $member->id,
            'name' => $member->name,
            'job_title' => $member->role_title ?? 'Especialista',
            'role_title' => $member->role_title,
            'avatar_url' => null,
            'subdomain' => $member->subdomain,
            'custom_domain' => $member->custom_domain,
            'active_domain_type' => $member->active_domain_type,
            'services' => [],
            'bio' => null,
            'public_booking_url' => $member->publicBookingUrl(),
        ];
    }

    private function publicBlockedSlotsForTenant(?User $tenant)
    {
        return BlockedTimeSlot::query()
            ->active()
            ->where(function ($query) use ($tenant): void {
                if ($tenant === null) {
                    $query->whereNull('blocked_time_slots.user_id');
                    return;
                }
                $query->where('blocked_time_slots.user_id', $tenant->id);
            })
            ->get()
            ->map(function ($block) {
                return [
                    'id' => $block->id,
                    'starts_at' => $block->starts_at->toIso8601String(),
                    'ends_at' => $block->ends_at->toIso8601String(),
                    'start_date' => $block->starts_at->toDateString(),
                    'end_date' => $block->ends_at->toDateString(),
                    'start_time' => $block->starts_at->format('H:i'),
                    'end_time' => $block->ends_at->format('H:i'),
                    'reason' => $block->reason ?? 'Bloqueio / Feriado',
                ];
            });
    }

    public function store(Request $request, BookingAvailabilityService $availabilityService)
    {
        Log::info('PublicBookingController::store: Start processing booking', [
            'host' => $request->getHost(),
            'method' => $request->method(),
            'input' => $request->except(['password', 'password_confirmation']),
        ]);

        try {
            $tenant = $this->resolveTenant($request);
            $company = $tenant->parent ?? $tenant;
            $selectedProfessional = $this->resolveSelectedProfessional($request, $tenant, $company);
            $schedulingUser = $selectedProfessional instanceof User ? $selectedProfessional : $tenant;
            $request->merge($this->normalizeBookingInput($request));

            Log::info('PublicBookingController::store: Tenant and context resolved', [
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
                'scheduling_user_id' => $schedulingUser->id,
                'selected_professional_id' => $selectedProfessional?->id,
                'selected_professional_type' => $selectedProfessional ? get_class($selectedProfessional) : null,
            ]);

            $validated = $request->validate([
                'service_id' => [
                    'required',
                    Rule::exists('services', 'id')->where(fn ($query) => $query->where('services.user_id', $company->id)),
                ],
                'team_member_id' => [
                    'nullable',
                    Rule::exists('team_members', 'id')->where(fn ($query) => $query->where('team_members.user_id', $company->id)),
                ],
                'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
                'appointment_time' => ['required', 'date_format:H:i'],
                'client_name' => ['required_without:customer_name', 'nullable', 'string', 'max:255'],
                'customer_name' => ['required_without:client_name', 'nullable', 'string', 'max:255'],
                'client_email' => ['required_without:customer_email', 'nullable', 'string', 'email', 'max:255'],
                'customer_email' => ['required_without:client_email', 'nullable', 'string', 'email', 'max:255'],
                'client_phone' => ['required_without:customer_phone', 'nullable', 'string', 'max:20'],
                'customer_phone' => ['required_without:client_phone', 'nullable', 'string', 'max:20'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);

            $service = $this->publicServicesQuery($company)->findOrFail($validated['service_id']);

            $isAvailable = $availabilityService->isSlotAvailable($service, $validated['appointment_date'], $validated['appointment_time'], $selectedProfessional);

            Log::info('PublicBookingController::store: Slot availability check', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'date' => $validated['appointment_date'],
                'time' => $validated['appointment_time'],
                'is_available' => $isAvailable,
            ]);

            if (! $isAvailable) {
                throw ValidationException::withMessages([
                    'appointment_time' => 'O horário selecionado não está mais disponível.',
                ]);
            }

            $payNow = $request->boolean('pay_now') || $request->input('payment_method') === 'pix';

            $appointment = Appointment::create([
                'service_id' => $service->id,
                'team_member_id' => $validated['team_member_id'] ?? ($selectedProfessional instanceof TeamMember ? $selectedProfessional->id : null),
                'client_name' => $validated['client_name'] ?? $validated['customer_name'],
                'client_email' => $validated['client_email'] ?? $validated['customer_email'],
                'client_phone' => $validated['client_phone'] ?? $validated['customer_phone'],
                'appointment_date' => $validated['appointment_date'],
                'appointment_time' => $validated['appointment_time'],
                'status' => 'confirmed',
                'payment_status' => $payNow ? 'pending' : 'none',
                'notes' => $validated['notes'] ?? null,
                'user_id' => $schedulingUser->id,
            ]);

            Log::info('PublicBookingController::store: Appointment successfully created', [
                'appointment_id' => $appointment->id,
                'client_name' => $appointment->client_name,
                'date' => $appointment->appointment_date,
                'time' => $appointment->appointment_time,
                'user_id' => $appointment->user_id,
                'team_member_id' => $appointment->team_member_id,
            ]);

            $message = sprintf(
                'Agendamento confirmado para %s em %s às %s.',
                $appointment->client_name,
                $service->name,
                Carbon::parse($appointment->appointment_date)->format('d/m/Y') . ' ' . $appointment->appointment_time
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'appointment' => $appointment->load('service'),
                    'available_slots' => $availabilityService->slotsFor($service, $validated['appointment_date'])['slots'],
                    'customer_name' => $appointment->client_name,
                ], 201);
            }

            return redirect()
                ->route('booking.index')
                ->with('booking_success', [
                    'id' => $appointment->id,
                    'customer_name' => $appointment->client_name,
                    'service_name' => $service->name,
                    'datetime' => Carbon::parse($appointment->appointment_date)->format('d/m/Y') . ' ' . $appointment->appointment_time,
                ])
                ->with('success', $message);
        } catch (ValidationException $e) {
            Log::warning('PublicBookingController::store: ValidationException', [
                'host' => $request->getHost(),
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            Log::error('PublicBookingController::store: Throwable error', [
                'host' => $request->getHost(),
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'input' => $request->all(),
            ]);

            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível concluir o agendamento.');
            }

            throw $e;
        }
    }

    public function storeBooking(Request $request, BookingAvailabilityService $availabilityService)
    {
        return $this->store($request, $availabilityService);
    }

    public function availableSlots(
        Request $request,
        BookingAvailabilityService $availabilityService
    )
    {
        try {
            $tenant = $this->resolveTenant($request);
            $company = $tenant->parent ?? $tenant;
            $selectedProfessional = $this->resolveSelectedProfessional($request, $tenant, $company);
            $schedulingUser = $selectedProfessional instanceof User ? $selectedProfessional : $tenant;
            $service = $this->resolveServiceFromRequest($request, $company);
            $date = $this->resolveDateFromRequest($request);
            $slots = $availabilityService->slotsFor($service, $date->toDateString(), $selectedProfessional)['slots'];

            Log::info('PublicBookingController::availableSlots: Slots fetched', [
                'host' => $request->getHost(),
                'tenant_id' => $tenant->id,
                'service_id' => $service->id,
                'date' => $date->toDateString(),
                'professional_id' => $selectedProfessional?->id,
                'slots_count' => count($slots),
            ]);

            $blockedSlots = BlockedTimeSlot::query()
                ->active()
                ->where('user_id', $schedulingUser->id)
                ->overlapping($date->copy()->startOfDay(), $date->copy()->endOfDay())
                ->orderBy('starts_at')
                ->get()
                ->map(function (BlockedTimeSlot $blockedTimeSlot): array {
                    return [
                        'starts_at' => $blockedTimeSlot->starts_at->format('Y-m-d H:i'),
                        'ends_at' => $blockedTimeSlot->ends_at->format('Y-m-d H:i'),
                        'reason' => $blockedTimeSlot->reason,
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'service_id' => $service->id,
                'service_name' => $service->name,
                'date' => $date->toDateString(),
                'duration_minutes' => (int) $service->duration_minutes,
                'opening_time' => self::OPENING_TIME,
                'closing_time' => self::CLOSING_TIME,
                'slots' => $slots,
                'blocked_slots' => $blockedSlots,
                'blocked_dates' => $availabilityService->slotsFor($service, $date->toDateString(), $selectedProfessional)['blocked_dates'],
            ]);
        } catch (ValidationException $e) {
            Log::warning('PublicBookingController::availableSlots: ValidationException', [
                'host' => $request->getHost(),
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            Log::error('PublicBookingController::availableSlots: Throwable error', [
                'host' => $request->getHost(),
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'input' => $request->all(),
            ]);

            $this->reportThrowable($e);

            return $this->jsonError($request, 'Não foi possível carregar os horários disponíveis.');
        }
    }

    private function resolveServiceFromRequest(Request $request, User $company): Service
    {
        $serviceParam = $request->route('service') ?? $request->input('service_id');

        if ($serviceParam instanceof Service) {
            $service = $serviceParam;
        } elseif ($serviceParam !== null && $serviceParam !== '') {
            $service = $this->publicServicesQuery($company)->findOrFail($serviceParam);
        } else {
            throw ValidationException::withMessages([
                'service_id' => 'O serviço é obrigatório.',
            ]);
        }

        if ($service->user_id !== null && (int) $service->user_id !== (int) $company->id) {
            abort(404, 'Serviço indisponível.');
        }

        if (! $service->is_active) {
            abort(404, 'Serviço indisponível.');
        }

        return $service;
    }

    private function resolveTenant(Request $request): User
    {
        $tenant = $request->attributes->get('bookingTenant') ?? (app()->bound('bookingTenant') ? app('bookingTenant') : null);

        if (! $tenant instanceof User) {
            abort(404, 'Estabelecimento não encontrado.');
        }

        return $tenant;
    }

    private function shouldRenderLandingPage(Request $request): bool
    {
        $host = strtolower($request->getHost());
        $baseDomain = strtolower((string) config('app.domain', 'localhost'));
        $appUrlHost = strtolower((string) (parse_url((string) config('app.url', ''), PHP_URL_HOST) ?: ''));

        return $host !== '' && (
            in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || $host === $baseDomain
            || $host === $appUrlHost
        );
    }

    private function resolveDateFromRequest(Request $request): Carbon
    {
        $dateInput = $request->input('date', $request->input('appointment_date'));

        if (! is_string($dateInput) || $dateInput === '') {
            throw ValidationException::withMessages([
                'date' => 'A data é obrigatória.',
            ]);
        }

        Validator::make(
            ['date' => $dateInput],
            [
                'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            ],
            [],
            [
                'date' => 'data',
            ]
        )->validate();

        return Carbon::createFromFormat('Y-m-d', $dateInput)->startOfDay();
    }

    private function normalizeBookingInput(Request $request): array
    {
        return [
            'client_name' => $this->sanitizeText($request->input('client_name')),
            'customer_name' => $this->sanitizeText($request->input('customer_name')),
            'client_email' => $this->sanitizeEmail($request->input('client_email')),
            'customer_email' => $this->sanitizeEmail($request->input('customer_email')),
            'client_phone' => $this->sanitizeText($request->input('client_phone')),
            'customer_phone' => $this->sanitizeText($request->input('customer_phone')),
            'notes' => $this->sanitizeText($request->input('notes')),
        ];
    }

    private function publicServicesQuery(User $tenant)
    {
        return Service::query()
            ->where('services.user_id', $tenant->id)
            ->where('is_active', true);
    }
}
