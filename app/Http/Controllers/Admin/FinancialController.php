<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\FinancialTransaction;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class FinancialController extends Controller
{
    private function resolveTenantId(Request $request): int
    {
        $user = $request->user();
        return $user->parent_id ? (int) $user->parent_id : (int) $user->id;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = $this->resolveTenantId($request);

        $teamMember = null;
        if ($user->parent_id) {
            $teamMember = TeamMember::where('user_id', $tenantId)
                ->where('email', $user->email)
                ->first();
        }

        // Determine date range filter
        $period = $request->input('period', 'this_month');
        $now = Carbon::now();

        switch ($period) {
            case 'last_month':
                $startDate = $now->copy()->subMonth()->startOfMonth()->toDateString();
                $endDate = $now->copy()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'last_30_days':
                $startDate = $now->copy()->subDays(30)->toDateString();
                $endDate = $now->copy()->toDateString();
                break;
            case 'this_year':
                $startDate = $now->copy()->startOfYear()->toDateString();
                $endDate = $now->copy()->endOfYear()->toDateString();
                break;
            case 'custom':
                $startDate = $request->input('start_date', $now->copy()->startOfMonth()->toDateString());
                $endDate = $request->input('end_date', $now->copy()->endOfMonth()->toDateString());
                break;
            case 'all':
                $startDate = null;
                $endDate = null;
                break;
            case 'this_month':
            default:
                $period = 'this_month';
                $startDate = $now->copy()->startOfMonth()->toDateString();
                $endDate = $now->copy()->endOfMonth()->toDateString();
                break;
        }

        // Appointments query within period
        $appointmentsQuery = Appointment::with(['service', 'teamMember'])
            ->where('user_id', $tenantId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        if ($startDate && $endDate) {
            $appointmentsQuery->whereBetween('appointment_date', [$startDate, $endDate]);
        }

        $allAppointments = $appointmentsQuery->get();

        // If logged in as a team member without full admin privileges, show professional specific view
        if ($teamMember && $user->role !== 'admin' && ! $user->hasPermission('reports.revenue')) {
            $profAppointments = $allAppointments->where('team_member_id', $teamMember->id);
            $totalRevenue = 0.00;
            $totalCommission = 0.00;

            $history = [];
            foreach ($profAppointments as $apt) {
                if ($apt->service) {
                    $price = (float) $apt->service->price;
                    $rate = (float) ($teamMember->commission_rate ?? 0.00);
                    if ($teamMember->service_commissions && isset($teamMember->service_commissions[$apt->service_id])) {
                        $rate = (float) $teamMember->service_commissions[$apt->service_id];
                    }
                    $earned = ($price * $rate) / 100;

                    $totalRevenue += $price;
                    $totalCommission += $earned;

                    $history[] = [
                        'date' => Carbon::parse($apt->appointment_date)->format('d/m/Y'),
                        'time' => Carbon::parse($apt->appointment_time)->format('H:i'),
                        'client_name' => $apt->client_name,
                        'service_name' => $apt->service->name,
                        'price' => $price,
                        'rate' => $rate,
                        'earned' => $earned,
                        'status' => $apt->status,
                    ];
                }
            }

            $stats = [
                'total_revenue' => $totalRevenue,
                'my_commissions' => $totalCommission,
                'commission_rate' => $teamMember->commission_rate ?? 0.00,
                'appointments_count' => $profAppointments->count(),
            ];

            return Inertia::render('Admin/Financial/Index', [
                'stats' => $stats,
                'history' => $history,
                'teamMember' => $teamMember,
                'filters' => [
                    'period' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ]);
        }

        // ADMIN FULL VIEW
        $allMembers = TeamMember::where('user_id', $tenantId)->get();
        $membersData = [];
        $grandTotalApptRevenue = 0.00;
        $grandTotalCommission = 0.00;

        foreach ($allMembers as $member) {
            $memberApts = $allAppointments->where('team_member_id', $member->id);
            $memberRevenue = 0.00;
            $memberCommission = 0.00;

            foreach ($memberApts as $apt) {
                if ($apt->service) {
                    $price = (float) $apt->service->price;
                    $rate = (float) ($member->commission_rate ?? 0.00);
                    if ($member->service_commissions && isset($member->service_commissions[$apt->service_id])) {
                        $rate = (float) $member->service_commissions[$apt->service_id];
                    }
                    $memberRevenue += $price;
                    $memberCommission += ($price * $rate) / 100;
                }
            }

            $membersData[] = [
                'member' => $member,
                'revenue' => $memberRevenue,
                'commission' => $memberCommission,
                'rate' => $member->commission_rate ?? 0.00,
                'count' => $memberApts->count(),
            ];

            $grandTotalApptRevenue += $memberRevenue;
            $grandTotalCommission += $memberCommission;
        }

        $unassignedApts = $allAppointments->whereNull('team_member_id');
        $unassignedRevenue = 0.00;
        foreach ($unassignedApts as $apt) {
            if ($apt->service) {
                $unassignedRevenue += (float) $apt->service->price;
            }
        }
        $grandTotalApptRevenue += $unassignedRevenue;

        // Build recent appointments history
        $appointmentHistory = [];
        foreach ($allAppointments->take(100) as $apt) {
            if ($apt->service) {
                $price = (float) $apt->service->price;
                $rate = 0.00;
                $earned = 0.00;
                $member = $apt->teamMember;

                if ($member) {
                    $rate = (float) ($member->commission_rate ?? 0.00);
                    if ($member->service_commissions && isset($member->service_commissions[$apt->service_id])) {
                        $rate = (float) $member->service_commissions[$apt->service_id];
                    }
                    $earned = ($price * $rate) / 100;
                }

                $appointmentHistory[] = [
                    'id' => $apt->id,
                    'date' => Carbon::parse($apt->appointment_date)->format('d/m/Y'),
                    'time' => Carbon::parse($apt->appointment_time)->format('H:i'),
                    'raw_date' => $apt->appointment_date,
                    'client_name' => $apt->client_name,
                    'service_name' => $apt->service->name,
                    'price' => $price,
                    'professional_name' => $member ? $member->name : 'Faturamento Direto',
                    'rate' => $rate,
                    'earned' => $earned,
                    'status' => $apt->status,
                ];
            }
        }

        // Fetch Financial Transactions (Contas a Pagar, Despesas e Entradas extras)
        $transactionsQuery = FinancialTransaction::with('teamMember')
            ->where('user_id', $tenantId)
            ->orderBy('due_date', 'desc')
            ->orderBy('id', 'desc');

        if ($startDate && $endDate) {
            $transactionsQuery->whereBetween('due_date', [$startDate, $endDate]);
        }

        $transactions = $transactionsQuery->get();

        // Calculate Totals
        $expensesPaid = 0.00;
        $expensesPending = 0.00;
        $expensesOverdue = 0.00;
        $extraIncomePaid = 0.00;

        foreach ($transactions as $t) {
            $amt = (float) $t->amount;
            if ($t->type === 'expense') {
                if ($t->status === 'paid') {
                    $expensesPaid += $amt;
                } elseif ($t->is_overdue) {
                    $expensesOverdue += $amt;
                } else {
                    $expensesPending += $amt;
                }
            } elseif ($t->type === 'income') {
                if ($t->status === 'paid') {
                    $extraIncomePaid += $amt;
                }
            }
        }

        $totalRevenue = $grandTotalApptRevenue + $extraIncomePaid;
        $totalCosts = $expensesPaid + $grandTotalCommission;
        $netProfit = $totalRevenue - $totalCosts;
        $totalPayable = $expensesPending + $expensesOverdue;

        $stats = [
            'total_revenue' => $totalRevenue,
            'appointment_revenue' => $grandTotalApptRevenue,
            'extra_income' => $extraIncomePaid,
            'total_commissions' => $grandTotalCommission,
            'expenses_paid' => $expensesPaid,
            'expenses_pending' => $expensesPending,
            'expenses_overdue' => $expensesOverdue,
            'total_payable' => $totalPayable,
            'net_profit' => $netProfit,
            'appointments_count' => $allAppointments->count(),
            'unassigned_revenue' => $unassignedRevenue,
            'members_data' => $membersData,
        ];

        return Inertia::render('Admin/Financial/Index', [
            'stats' => $stats,
            'history' => $appointmentHistory,
            'transactions' => $transactions,
            'teamMembers' => $allMembers,
            'teamMember' => $teamMember,
            'filters' => [
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:expense,income'],
            'category' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:pending,paid,cancelled'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'team_member_id' => ['nullable', 'integer', 'exists:team_members,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'paid' && empty($validated['paid_at'])) {
            $validated['paid_at'] = Carbon::now()->toDateString();
        } elseif ($validated['status'] === 'pending') {
            $validated['paid_at'] = null;
        }

        $validated['user_id'] = $tenantId;

        FinancialTransaction::create($validated);

        return redirect()->route('admin.financial.index')
            ->with('success', 'Lançamento financeiro cadastrado com sucesso!');
    }

    public function updateTransaction(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $tenantId = $this->resolveTenantId($request);
        if ($transaction->user_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:expense,income'],
            'category' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:pending,paid,cancelled'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'team_member_id' => ['nullable', 'integer', 'exists:team_members,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'paid' && empty($validated['paid_at'])) {
            $validated['paid_at'] = Carbon::now()->toDateString();
        } elseif ($validated['status'] === 'pending') {
            $validated['paid_at'] = null;
        }

        $transaction->update($validated);

        return redirect()->route('admin.financial.index')
            ->with('success', 'Lançamento financeiro atualizado com sucesso!');
    }

    public function destroyTransaction(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $tenantId = $this->resolveTenantId($request);
        if ($transaction->user_id !== $tenantId) {
            abort(403);
        }

        $transaction->delete();

        return redirect()->route('admin.financial.index')
            ->with('success', 'Lançamento excluído com sucesso!');
    }

    public function toggleTransactionStatus(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $tenantId = $this->resolveTenantId($request);
        if ($transaction->user_id !== $tenantId) {
            abort(403);
        }

        if ($transaction->status === 'paid') {
            $transaction->update([
                'status' => 'pending',
                'paid_at' => null,
            ]);
            $msg = 'Conta marcada como Pendente.';
        } else {
            $transaction->update([
                'status' => 'paid',
                'paid_at' => Carbon::now()->toDateString(),
            ]);
            $msg = 'Conta baixada como Paga com sucesso!';
        }

        return redirect()->route('admin.financial.index')
            ->with('success', $msg);
    }
}
