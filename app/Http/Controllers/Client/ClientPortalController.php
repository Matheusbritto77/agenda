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

                $bSettings = $branding?->settings ?? [];
                $bannerUrl = ! empty($bSettings['portal_banner_path'])
                    ? \App\Support\StorageHelper::url($bSettings['portal_banner_path'])
                    : $branding?->banner_url;
                $logoUrl = ! empty($bSettings['portal_logo_path'])
                    ? \App\Support\StorageHelper::url($bSettings['portal_logo_path'])
                    : $branding?->logo_url;
                $primaryColor = $bSettings['portal_primary_color'] ?? $branding?->primary_color ?? '#6366f1';
                $secondaryColor = $bSettings['portal_secondary_color'] ?? $branding?->secondary_color ?? '#06b6d4';

                return [
                    'id' => $tenant?->id,
                    'name' => $tenant?->name ?? 'Empresa',
                    'business_name' => $bSettings['business_name'] ?? $tenant?->name ?? 'Empresa',
                    'welcome_title' => $bSettings['portal_welcome_title'] ?? null,
                    'welcome_subtitle' => $bSettings['portal_welcome_subtitle'] ?? null,
                    'announcement' => $bSettings['portal_announcement'] ?? null,
                    'announcement_enabled' => (bool) ($bSettings['portal_announcement_enabled'] ?? false),
                    'show_loyalty_badges' => (bool) ($bSettings['portal_show_loyalty_badges'] ?? true),
                    'show_reviews' => (bool) ($bSettings['portal_show_reviews'] ?? true),
                    'show_professionals' => (bool) ($bSettings['portal_show_professionals'] ?? true),
                    'show_service_prices' => (bool) ($bSettings['portal_show_service_prices'] ?? true),
                    'support_whatsapp' => $bSettings['portal_support_whatsapp'] ?? ($bSettings['whatsapp_number'] ?? null),
                    'custom_instructions' => $bSettings['portal_custom_instructions'] ?? null,
                    'tagline' => $bSettings['tagline'] ?? null,
                    'booking_url' => $tenant?->publicBookingUrl(),
                    'logo_url' => $logoUrl,
                    'banner_url' => $bannerUrl,
                    'primary_color' => $primaryColor,
                    'secondary_color' => $secondaryColor,
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

        $activeCompanyId = $activeCompany ? $activeCompany['id'] : null;

        $couponsQuery = \App\Models\Coupon::query()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now()->toDateString());
            })
            ->where(function ($q) use ($client): void {
                $q->whereNull('client_account_id')
                    ->orWhere('client_account_id', $client->id);
            });

        if ($activeCompanyId) {
            $couponsQuery->where('user_id', $activeCompanyId);
        } else {
            $companyIds = $companies->pluck('id')->filter()->all();
            if (! empty($companyIds)) {
                $couponsQuery->whereIn('user_id', $companyIds);
            }
        }

        $coupons = $couponsQuery->with('user:id,name')->get()->map(fn (\App\Models\Coupon $c): array => [
            'id' => $c->id,
            'code' => $c->code,
            'description' => $c->description,
            'discount_type' => $c->discount_type,
            'discount_value' => (float) $c->discount_value,
            'formatted_discount' => $c->formatted_discount,
            'min_spend' => $c->min_spend !== null ? (float) $c->min_spend : null,
            'expires_at' => $c->expires_at?->format('d/m/Y'),
            'company_id' => $c->user_id,
            'company_name' => $c->user?->name ?? 'Empresa',
            'is_exclusive' => $c->client_account_id === $client->id,
        ])->values();

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
                'coupons' => $coupons->count(),
                'reviews' => $scopedAppointments->whereNotNull('review')->count(),
            ],
            'badges' => $this->earnedBadges($completedCount, $activeCompany),
            'coupons' => $coupons,
            'companies' => $companies->map(fn ($comp) => [
                ...$comp,
                'is_active' => $activeCompany && $comp['id'] === $activeCompany['id'],
            ])->values(),
            'appointments' => $scopedAppointments->map(function (Appointment $appointment): array {
                $tenantBranding = $appointment->tenant?->brandingSetting;
                $tSettings = $tenantBranding?->settings ?? [];
                $showReviews = (bool) ($tSettings['portal_show_reviews'] ?? true);
                $showProfessionals = (bool) ($tSettings['portal_show_professionals'] ?? true);
                $showPrices = (bool) ($tSettings['portal_show_service_prices'] ?? true);

                return [
                    'id' => $appointment->id,
                    'company_id' => $appointment->user_id,
                    'company' => $appointment->tenant?->name ?? 'Empresa',
                    'company_booking_url' => $appointment->tenant?->publicBookingUrl(),
                    'company_logo_url' => ! empty($tSettings['portal_logo_path']) ? \App\Support\StorageHelper::url($tSettings['portal_logo_path']) : $tenantBranding?->logo_url,
                    'service' => $appointment->service?->name ?? 'Serviço',
                    'service_price' => $appointment->service?->formatted_price ?? ('R$ ' . number_format((float) ($appointment->service?->price ?? 0), 2, ',', '.')),
                    'duration_minutes' => $appointment->service?->duration_minutes ?? 30,
                    'professional' => $appointment->teamMember?->name,
                    'professional_job' => $appointment->teamMember?->job_title,
                    'professional_avatar' => $appointment->teamMember?->avatar_url,
                    'show_professionals' => $showProfessionals,
                    'show_service_prices' => $showPrices,
                    'show_reviews' => $showReviews,
                    'date' => $appointment->appointment_date->format('d/m/Y'),
                    'raw_date' => $appointment->appointment_date->format('Y-m-d'),
                    'time' => substr((string) $appointment->appointment_time, 0, 5),
                    'status' => $appointment->status,
                    'notes' => $appointment->notes,
                    'can_review' => $appointment->status === 'completed' && $showReviews,
                    'review' => $appointment->review ? [
                        'rating' => (int) $appointment->review->rating,
                        'comment' => $appointment->review->comment,
                        'updated_at' => $appointment->review->updated_at?->format('d/m/Y'),
                    ] : null,
                ];
            })->values(),
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

    private function earnedBadges(int $completed, ?array $activeCompany = null): array
    {
        $catalog = $this->badgeCatalog($activeCompany);

        return collect($catalog)
            ->map(function (array $badge) use ($completed): array {
                $min = (int) ($badge['minimum'] ?? 1);
                $earned = $completed >= $min;
                $progressPercent = min(100, (int) round(($completed / max(1, $min)) * 100));
                $remaining = max(0, $min - $completed);

                return $badge + [
                    'minimum' => $min,
                    'earned' => $earned,
                    'progress_percent' => $progressPercent,
                    'remaining' => $remaining,
                ];
            })
            ->values()
            ->all();
    }

    private function highestBadge(int $completed, ?array $activeCompany = null): ?array
    {
        return collect($this->badgeCatalog($activeCompany))
            ->filter(fn (array $badge): bool => $completed >= (int) ($badge['minimum'] ?? 1))
            ->last();
    }

    private function badgeCatalog(?array $activeCompany = null): array
    {
        if ($activeCompany && ! empty($activeCompany['id'])) {
            $branding = \App\Models\BrandingSetting::where('user_id', $activeCompany['id'])->first();
            if (! empty($branding?->settings['loyalty_tiers']) && is_array($branding->settings['loyalty_tiers'])) {
                return $branding->settings['loyalty_tiers'];
            }
        }

        return [
            ['name' => 'Primeiro Encontro', 'minimum' => 1, 'icon' => 'sparkles', 'color' => '#6366f1', 'reward' => 'Boas-vindas VIP'],
            ['name' => 'Cliente Frequente', 'minimum' => 3, 'icon' => 'star', 'color' => '#06b6d4', 'reward' => 'Prioridade nos horários'],
            ['name' => 'Cliente Fiel', 'minimum' => 5, 'icon' => 'heart', 'color' => '#ec4899', 'reward' => '10% de desconto'],
            ['name' => 'Cliente VIP', 'minimum' => 10, 'icon' => 'crown', 'color' => '#f59e0b', 'reward' => 'Brinde / Serviço Cortesia'],
            ['name' => 'Embaixador', 'minimum' => 25, 'icon' => 'trophy', 'color' => '#8b5cf6', 'reward' => 'Tratamento Especial Gratuito'],
        ];
    }
}
