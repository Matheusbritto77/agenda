<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentReview;
use App\Models\ClientAccount;
use App\Models\CompanyReview;
use App\Models\Service;
use App\Models\TeamMember;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientAreaController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = $this->tenantId($request);
        $teamMemberId = $this->visibleTeamMemberId($request);
        $search = trim((string) $request->input('search', ''));

        $clientsQuery = ClientAccount::query()
            ->whereHas('appointments', fn (Builder $query) => $this->scopeAppointments($query, $tenantId, $teamMemberId))
            ->with(['appointments' => function ($query) use ($tenantId, $teamMemberId): void {
                $this->scopeAppointments($query, $tenantId, $teamMemberId)
                    ->with(['service:id,name,price,duration_minutes', 'teamMember:id,name,job_title,avatar_url', 'review'])
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('appointment_time');
            }]);

        if ($search !== '') {
            $clientsQuery->where(function (Builder $query) use ($search, $tenantId, $teamMemberId): void {
                $query->where('client_accounts.name', 'like', "%{$search}%")
                    ->orWhere('client_accounts.email', 'like', "%{$search}%")
                    ->orWhere('client_accounts.phone', 'like', "%{$search}%")
                    ->orWhereHas('appointments', function (Builder $appointmentQuery) use ($search, $tenantId, $teamMemberId): void {
                        $this->scopeAppointments($appointmentQuery, $tenantId, $teamMemberId)
                            ->where(function (Builder $contactQuery) use ($search): void {
                                $contactQuery->where('client_name', 'like', "%{$search}%")
                                    ->orWhere('client_email', 'like', "%{$search}%")
                                    ->orWhere('client_phone', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $clients = $clientsQuery
            ->orderBy('name')
            ->paginate(12, ['*'], 'clients_page')
            ->withQueryString()
            ->through(fn (ClientAccount $client): array => $this->presentClient($client));

        $reviewsQuery = AppointmentReview::query()
            ->whereHas('appointment', fn (Builder $query) => $this->scopeAppointments($query, $tenantId, $teamMemberId))
            ->with([
                'clientAccount:id,name,email',
                'appointment:id,user_id,client_account_id,service_id,team_member_id,appointment_date,appointment_time,status',
                'appointment.service:id,name',
                'appointment.teamMember:id,name,job_title',
            ]);

        if ($request->filled('review_service')) {
            $serviceId = (int) $request->input('review_service');
            $reviewsQuery->whereHas('appointment', fn (Builder $query) => $query->where('service_id', $serviceId));
        }

        if ($request->filled('review_rating')) {
            $reviewsQuery->where('rating', (int) $request->input('review_rating'));
        }

        if ($request->input('review_visibility') === 'public') {
            $reviewsQuery->public();
        } elseif ($request->input('review_visibility') === 'internal') {
            $reviewsQuery->internal();
        }

        $serviceReviews = $reviewsQuery
            ->latest('appointment_reviews.created_at')
            ->paginate(12, ['*'], 'reviews_page')
            ->withQueryString()
            ->through(fn (AppointmentReview $review): array => $this->presentServiceReview($review));

        $companyReviews = CompanyReview::query()
            ->where('user_id', $tenantId)
            ->with('clientAccount:id,name,email')
            ->latest()
            ->paginate(12, ['*'], 'company_reviews_page')
            ->withQueryString()
            ->through(fn (CompanyReview $review): array => [
                'id' => $review->id,
                'client_name' => $review->clientAccount?->name ?? 'Cliente',
                'client_email' => $review->clientAccount?->email,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'is_public' => (bool) $review->is_public,
                'created_at' => $review->created_at?->format('d/m/Y H:i'),
            ]);

        $appointments = $this->appointmentsQuery($tenantId, $teamMemberId);
        $serviceReviewBase = AppointmentReview::query()
            ->whereHas('appointment', fn (Builder $query) => $this->scopeAppointments($query, $tenantId, $teamMemberId));

        $reviewsCount = (clone $serviceReviewBase)->count();

        return Inertia::render('Admin/ClientArea/Index', [
            'clients' => $clients,
            'serviceReviews' => $serviceReviews,
            'companyReviews' => $companyReviews,
            'services' => Service::query()
                ->where('user_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'review_service' => $request->input('review_service', ''),
                'review_rating' => $request->input('review_rating', ''),
                'review_visibility' => $request->input('review_visibility', ''),
            ],
            'stats' => [
                'clients' => (clone $appointments)->whereNotNull('client_account_id')->distinct()->count('client_account_id'),
                'appointments' => (clone $appointments)->count(),
                'completed' => (clone $appointments)->where('status', 'completed')->count(),
                'service_reviews' => $reviewsCount,
                'internal_reviews' => (clone $serviceReviewBase)->internal()->count(),
                'average_rating' => $reviewsCount > 0 ? round((float) (clone $serviceReviewBase)->avg('rating'), 1) : null,
            ],
            'scopeLabel' => $teamMemberId === null ? 'Toda a empresa' : 'Somente meus atendimentos',
        ]);
    }

    public function updateClient(Request $request, ClientAccount $client): RedirectResponse
    {
        $tenantId = $this->tenantId($request);
        $teamMemberId = $this->visibleTeamMemberId($request);
        $appointments = $this->appointmentsQuery($tenantId, $teamMemberId)
            ->where('client_account_id', $client->id);

        abort_unless((clone $appointments)->exists(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $appointments->update([
            'client_name' => trim($validated['name']),
            'client_phone' => trim((string) ($validated['phone'] ?? '')),
        ]);

        return back()->with('success', 'Dados do cliente atualizados no histórico da empresa.');
    }

    public function toggleServiceReview(Request $request, AppointmentReview $review): RedirectResponse
    {
        $tenantId = $this->tenantId($request);
        $teamMemberId = $this->visibleTeamMemberId($request);

        $allowed = $this->appointmentsQuery($tenantId, $teamMemberId)
            ->whereKey($review->appointment_id)
            ->exists();

        abort_unless($allowed, 404);

        $review->update(['is_public' => ! $review->is_public]);

        return back()->with('success', $review->is_public
            ? 'Avaliação do serviço publicada na página da empresa.'
            : 'Avaliação do serviço mantida apenas como feedback interno.');
    }

    public function toggleCompanyReview(Request $request, CompanyReview $review): RedirectResponse
    {
        abort_unless((int) $review->user_id === $this->tenantId($request), 404);

        $review->update(['is_public' => ! $review->is_public]);

        return back()->with('success', $review->is_public
            ? 'Avaliação da empresa publicada na página pública.'
            : 'Avaliação da empresa ocultada da página pública.');
    }

    private function tenantId(Request $request): int
    {
        $user = $request->user();

        return $user->parent_id ? (int) $user->parent_id : (int) $user->id;
    }

    private function visibleTeamMemberId(Request $request): ?int
    {
        $user = $request->user();

        if (! $user->parent_id || $user->hasPermission('clients.view_all')) {
            return null;
        }

        return TeamMember::query()
            ->where('user_id', $user->parent_id)
            ->whereRaw('LOWER(email) = ?', [strtolower((string) $user->email)])
            ->value('id') ?: -1;
    }

    private function appointmentsQuery(int $tenantId, ?int $teamMemberId): Builder
    {
        return $this->scopeAppointments(Appointment::query(), $tenantId, $teamMemberId);
    }

    private function scopeAppointments(Builder $query, int $tenantId, ?int $teamMemberId): Builder
    {
        $query->where('appointments.user_id', $tenantId);

        if ($teamMemberId !== null) {
            $query->where('appointments.team_member_id', $teamMemberId);
        }

        return $query;
    }

    private function presentClient(ClientAccount $client): array
    {
        $appointments = $client->appointments;
        $latest = $appointments->first();
        $completed = $appointments->where('status', 'completed');

        return [
            'id' => $client->id,
            'name' => $latest?->client_name ?: $client->name,
            'email' => $latest?->client_email ?: $client->email,
            'phone' => $latest?->client_phone ?: $client->phone,
            'account_email' => $client->email,
            'appointments_count' => $appointments->count(),
            'completed_count' => $completed->count(),
            'reviews_count' => $appointments->whereNotNull('review')->count(),
            'total_spent' => (float) $completed->sum(fn (Appointment $appointment): float => (float) ($appointment->service?->price ?? 0)),
            'last_visit' => $latest ? $this->formatAppointmentDate($latest) : null,
            'history' => $appointments->take(12)->map(fn (Appointment $appointment): array => [
                'id' => $appointment->id,
                'service' => $appointment->service?->name ?? 'Serviço removido',
                'professional' => $appointment->teamMember?->name ?? 'Atendimento geral',
                'date' => $this->formatAppointmentDate($appointment),
                'status' => $appointment->status,
                'price' => (float) ($appointment->service?->price ?? 0),
                'review' => $appointment->review ? [
                    'rating' => (int) $appointment->review->rating,
                    'comment' => $appointment->review->comment,
                    'is_public' => (bool) $appointment->review->is_public,
                ] : null,
            ])->values(),
        ];
    }

    private function presentServiceReview(AppointmentReview $review): array
    {
        return [
            'id' => $review->id,
            'client_name' => $review->clientAccount?->name ?? 'Cliente',
            'client_email' => $review->clientAccount?->email,
            'service' => $review->appointment?->service?->name ?? 'Serviço removido',
            'professional' => $review->appointment?->teamMember?->name ?? 'Atendimento geral',
            'rating' => (int) $review->rating,
            'comment' => $review->comment,
            'is_public' => (bool) $review->is_public,
            'appointment_date' => $review->appointment ? $this->formatAppointmentDate($review->appointment) : null,
            'created_at' => $review->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function formatAppointmentDate(Appointment $appointment): string
    {
        $date = $appointment->appointment_date instanceof Carbon
            ? $appointment->appointment_date
            : Carbon::parse($appointment->appointment_date);

        return $date->format('d/m/Y').' às '.substr((string) $appointment->appointment_time, 0, 5);
    }
}
