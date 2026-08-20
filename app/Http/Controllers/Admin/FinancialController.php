<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->parent_id) {
            $tenantId = (int) $user->parent_id;
            $teamMember = TeamMember::where('user_id', $tenantId)
                ->where('email', $user->email)
                ->first();
        } else {
            $tenantId = (int) $user->id;
            $teamMember = null;
        }

        // Fetch all confirmed/completed appointments to compute financial history
        $allAppointments = Appointment::with(['service', 'teamMember'])
            ->where('user_id', $tenantId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get();

        if ($teamMember) {
            // Professional view
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
                        'status' => $apt->status
                    ];
                }
            }
            
            $stats = [
                'total_revenue' => $totalRevenue,
                'my_commissions' => $totalCommission,
                'commission_rate' => $teamMember->commission_rate ?? 0.00,
                'appointments_count' => $profAppointments->count(),
            ];
            
            return Inertia::render('Admin/Financial/Index', compact('stats', 'history', 'teamMember'));
        } else {
            // Admin view
            $allMembers = TeamMember::where('user_id', $tenantId)->get();
            $membersData = [];
            $grandTotalRevenue = 0.00;
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
                    'count' => $memberApts->count()
                ];
                
                $grandTotalRevenue += $memberRevenue;
                $grandTotalCommission += $memberCommission;
            }
            
            $unassignedApts = $allAppointments->whereNull('team_member_id');
            $unassignedRevenue = 0.00;
            foreach ($unassignedApts as $apt) {
                if ($apt->service) {
                    $unassignedRevenue += (float) $apt->service->price;
                }
            }
            $grandTotalRevenue += $unassignedRevenue;
            
            $stats = [
                'total_revenue' => $grandTotalRevenue,
                'total_commissions' => $grandTotalCommission,
                'net_profit' => $grandTotalRevenue - $grandTotalCommission,
                'members_data' => $membersData,
                'unassigned_revenue' => $unassignedRevenue,
                'appointments_count' => $allAppointments->count(),
            ];
            
            // Build recent global history for audit
            $history = [];
            foreach ($allAppointments->take(50) as $apt) { // Show up to 50 recent ones
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
                    
                    $history[] = [
                        'date' => Carbon::parse($apt->appointment_date)->format('d/m/Y'),
                        'time' => Carbon::parse($apt->appointment_time)->format('H:i'),
                        'client_name' => $apt->client_name,
                        'service_name' => $apt->service->name,
                        'price' => $price,
                        'professional_name' => $member ? $member->name : 'Sem profissional',
                        'rate' => $rate,
                        'earned' => $earned,
                        'status' => $apt->status
                    ];
                }
            }
            
            return Inertia::render('Admin/Financial/Index', compact('stats', 'history', 'teamMember'));
        }
    }
}
