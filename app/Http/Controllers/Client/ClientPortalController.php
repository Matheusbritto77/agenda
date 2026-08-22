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
    public function index(): Response
    {
        /** @var ClientAccount $client */
        $client = Auth::guard('client')->user();

        $appointments = Appointment::query()
            ->where('client_account_id', $client->id)
            ->with(['service', 'teamMember', 'tenant.brandingSetting', 'review'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->get();

        $completedCount = $appointments->where('status', 'completed')->count();
        $companies = $appointments
            ->groupBy('user_id')
            ->map(function ($companyAppointments): array {
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

                return [
                    'id' => $tenant?->id,
                    'name' => $tenant?->name ?? 'Empresa',
                    'booking_url' => $tenant?->publicBookingUrl(),
                    'logo_url' => $branding?->logo_url,
                    'banner_url' => $branding?->banner_url,
                    'services_count' => $completed,
                    'professionals' => $companyAppointments
                        ->pluck('teamMember.name')
                        ->filter()
                        ->unique()
                        ->values(),
                    'badge' => $this->highestBadge($completed),
                    'latest_completed_id' => $latestCompleted?->id,
                    'reviewable_appointment_id' => $unreviewed?->id ?? $latestCompleted?->id,
                    'has_unreviewed' => $unreviewed !== null,
                ];
            })
            ->values();

        return Inertia::render('Client/Portal/Dashboard', [
            'client' => [
                'name' => $client->name,
                'email' => $client->email,
            ],
            'summary' => [
                'appointments' => $appointments->count(),
                'completed' => $completedCount,
                'companies' => $companies->count(),
                'reviews' => $appointments->whereNotNull('review')->count(),
            ],
            'badges' => $this->earnedBadges($completedCount),
            'companies' => $companies,
            'appointments' => $appointments->map(fn (Appointment $appointment): array => [
                'id' => $appointment->id,
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
            ]
        );

        return back()->with('success', 'Avaliação salva. Obrigado por compartilhar sua experiência!');
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
