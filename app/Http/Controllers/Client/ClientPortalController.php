<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentReview;
use App\Models\ClientAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClientPortalController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var ClientAccount $client */
        $client = Auth::guard('client')->user();

        $allAppointments = Appointment::query()
            ->where('client_account_id', $client->id)
            ->with(['service', 'teamMember', 'tenant.brandingSetting', 'review'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->get();

        $companies = $allAppointments
            ->groupBy('user_id')
            ->map(function ($companyAppointments) use ($client): array {
                $tenant = $companyAppointments->first()->tenant;
                $branding = $tenant?->brandingSetting;
                $completed = $companyAppointments->where('status', 'completed')->count();
                $latestCompleted = $companyAppointments
                    ->where('status', 'completed')
                    ->sortByDesc('appointment_date')
                    ->first();
                $unreviewed = $companyAppointments
                    ->where('status', 'completed')
                    ->whereNull('review')
                    ->sortByDesc('appointment_date')
                    ->first();

                $myCompanyReview = \App\Models\CompanyReview::query()
                    ->where('user_id', $tenant?->id)
                    ->where('client_account_id', $client->id)
                    ->first();

                return [
                    'id' => $tenant?->id,
                    'name' => $tenant?->name ?? 'Empresa',
                    'business_name' => $branding?->settings['business_name'] ?? $tenant?->name ?? 'Empresa',
                    'tagline' => $branding?->settings['tagline'] ?? null,
                    'booking_url' => $tenant?->publicBookingUrl(),
                    'logo_url' => $branding?->logo_url,
                    'banner_url' => $branding?->banner_url,
                    'primary_color' => $branding?->primary_color ?? '#6366f1',
                    'secondary_color' => $branding?->secondary_color ?? '#06b6d4',
                    'services_count' => $completed,
                    'total_appointments_count' => $companyAppointments->count(),
                    'professionals' => $companyAppointments
                        ->pluck('teamMember.name')
                        ->filter()
                        ->unique()
                        ->values(),
                    'badge' => $this->highestBadge($completed),
                    'latest_completed_id' => $latestCompleted?->id,
                    'reviewable_appointment_id' => $unreviewed?->id ?? $latestCompleted?->id,
                    'has_unreviewed' => $unreviewed !== null,
                    'company_review' => $myCompanyReview ? [
                        'id' => $myCompanyReview->id,
                        'rating' => (int) $myCompanyReview->rating,
                        'comment' => $myCompanyReview->comment,
                        'updated_at' => $myCompanyReview->updated_at?->format('d/m/Y'),
                    ] : null,
                ];
            })
            ->values();

        // Determine active company
        $requestedCompanyId = $request->query('empresa') ?? $request->session()->get('client_active_company_id');

        $activeCompany = null;
        if ($requestedCompanyId === 'all') {
            $activeCompany = null;
            $request->session()->put('client_active_company_id', 'all');
        } elseif ($requestedCompanyId) {
            $activeCompany = $companies->firstWhere('id', (int) $requestedCompanyId);
            if ($activeCompany) {
                $request->session()->put('client_active_company_id', $activeCompany['id']);
            }
        }

        // Default to first company if only 1 exists or if no preference stored
        if (! $activeCompany && $requestedCompanyId !== 'all' && $companies->isNotEmpty()) {
            $activeCompany = $companies->first();
            $request->session()->put('client_active_company_id', $activeCompany['id']);
        }

        // Filter appointments based on active company
        $scopedAppointments = $activeCompany
            ? $allAppointments->where('user_id', $activeCompany['id'])->values()
            : $allAppointments;

        $completedCount = $scopedAppointments->where('status', 'completed')->count();

        return Inertia::render('Client/Portal/Dashboard', [
            'client' => [
                'name' => $client->name,
                'email' => $client->email,
            ],
            'activeCompany' => $activeCompany,
            'summary' => [
                'appointments' => $scopedAppointments->count(),
                'completed' => $completedCount,
                'companies' => $companies->count(),
                'reviews' => $scopedAppointments->whereNotNull('review')->count(),
            ],
            'badges' => $this->earnedBadges($completedCount),
            'companies' => $companies->map(fn ($comp) => [
                ...$comp,
                'is_active' => $activeCompany && $comp['id'] === $activeCompany['id'],
            ])->values(),
            'appointments' => $scopedAppointments->map(fn (Appointment $appointment): array => [
                'id' => $appointment->id,
                'company_id' => $appointment->user_id,
                'company' => $appointment->tenant?->name ?? 'Empresa',
                'company_booking_url' => $appointment->tenant?->publicBookingUrl(),
                'company_logo_url' => $appointment->tenant?->brandingSetting?->logo_url,
                'service' => $appointment->service?->name ?? 'Serviço',
                'service_price' => $appointment->service?->formatted_price ?? ('R$ ' . number_format((float) ($appointment->service?->price ?? 0), 2, ',', '.')),
                'duration_minutes' => $appointment->service?->duration_minutes ?? 30,
                'professional' => $appointment->teamMember?->name,
                'professional_job' => $appointment->teamMember?->job_title,
                'professional_avatar' => $appointment->teamMember?->avatar_url,
                'date' => $appointment->appointment_date->format('d/m/Y'),
                'raw_date' => $appointment->appointment_date->format('Y-m-d'),
                'time' => substr((string) $appointment->appointment_time, 0, 5),
                'status' => $appointment->status,
                'notes' => $appointment->notes,
                'can_review' => $appointment->status === 'completed',
                'review' => $appointment->review ? [
                    'rating' => (int) $appointment->review->rating,
                    'comment' => $appointment->review->comment,
                    'updated_at' => $appointment->review->updated_at?->format('d/m/Y'),
                ] : null,
            ])->values(),
        ]);
    }

    public function selectCompany(Request $request, $company = null): RedirectResponse
    {
        if ($company === 'all' || ! $company) {
            $request->session()->put('client_active_company_id', 'all');
        } else {
            $request->session()->put('client_active_company_id', (int) $company);
        }

        return redirect()->route('client.dashboard');
    }

    public function review(Request $request, Appointment $appointment): RedirectResponse
    {
        /** @var ClientAccount $client */
        $client = Auth::guard('client')->user();

        abort_unless((int) $appointment->client_account_id === (int) $client->id, 404);
        abort_unless($appointment->status === 'completed', 422, 'Somente serviços concluídos podem ser avaliados.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        AppointmentReview::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'client_account_id' => $client->id,
                'rating' => $validated['rating'],
                'comment' => $this->sanitizeText($validated['comment'] ?? null),
                'is_public' => false,
            ]
        );

        return back()->with('success', 'Avaliação de atendimento enviada com sucesso para a empresa e profissional!');
    }

    public function reviewCompany(Request $request, \App\Models\User $company): RedirectResponse
    {
        /** @var ClientAccount $client */
        $client = Auth::guard('client')->user();

        $hasAppointment = Appointment::where('client_account_id', $client->id)
            ->where('user_id', $company->id)
            ->exists();

        abort_unless($hasAppointment, 403, 'Você só pode avaliar empresas onde já realizou agendamentos.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        \App\Models\CompanyReview::updateOrCreate(
            [
                'user_id' => $company->id,
                'client_account_id' => $client->id,
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $this->sanitizeText($validated['comment'] ?? null),
                'is_public' => true,
            ]
        );

        return back()->with('success', 'Avaliação da empresa enviada com sucesso! Ela será exibida na página pública do estabelecimento.');
    }

    private function earnedBadges(int $completed): array
    {
        return collect($this->badgeCatalog())
            ->map(fn (array $badge): array => $badge + ['earned' => $completed >= $badge['minimum']])
            ->values()
            ->all();
    }

    private function highestBadge(int $completed): ?array
    {
        return collect($this->badgeCatalog())
            ->filter(fn (array $badge): bool => $completed >= $badge['minimum'])
            ->last();
    }

    private function badgeCatalog(): array
    {
        return [
            ['name' => 'Primeiro encontro', 'minimum' => 1, 'icon' => 'sparkles'],
            ['name' => 'Cliente frequente', 'minimum' => 3, 'icon' => 'star'],
            ['name' => 'Cliente fiel', 'minimum' => 5, 'icon' => 'heart'],
            ['name' => 'Cliente VIP', 'minimum' => 10, 'icon' => 'crown'],
            ['name' => 'Embaixador', 'minimum' => 25, 'icon' => 'trophy'],
        ];
    }
}
