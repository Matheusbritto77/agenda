<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentFlowLog;
use App\Models\Payment;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WhatsAppNotificationQueue;
use App\Services\NotificationDispatcherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppInboundWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp messages from agenwpp (SIM/NAO interactive approval)
     */
    public function handle(Request $request, NotificationDispatcherService $dispatcher): JsonResponse
    {
        $payload = $request->validate([
            'phone' => ['required', 'string'],
            'message' => ['required', 'string'],
            'tenant_id' => ['nullable', 'string'],
            'message_id' => ['nullable', 'string'],
        ]);

        $rawMessage = trim($payload['message']);
        $cleanPhone = preg_replace('/\D/', '', $payload['phone']);
        $normalizedText = strtoupper($rawMessage);

        Log::info("[WhatsApp Inbound] Received from {$cleanPhone}: \"{$rawMessage}\"");

        // 1. Identify Intent (Approve or Reject)
        $isApproval = false;
        $isRejection = false;
        $appointmentId = null;

        // Extract potential Appointment ID from message (e.g. "SIM 26", "APROVAR #26", "SIM26")
        if (preg_match('/(?:SIM|S|APROVAR|CONFIRMAR|OK|1)\s*#?(\d+)/i', $rawMessage, $matches)) {
            $isApproval = true;
            $appointmentId = (int) $matches[1];
        } elseif (preg_match('/^(?:SIM|S|APROVAR|CONFIRMAR|CONFIRMADO|OK|1)$/i', $rawMessage)) {
            $isApproval = true;
        } elseif (preg_match('/(?:NAO|NÃO|N|RECUSAR|CANCELAR|2)\s*#?(\d+)/i', $rawMessage, $matches)) {
            $isRejection = true;
            $appointmentId = (int) $matches[1];
        } elseif (preg_match('/^(?:NAO|NÃO|N|RECUSAR|CANCELAR|CANCELADO|2)$/i', $rawMessage)) {
            $isRejection = true;
        }

        if (!$isApproval && !$isRejection) {
            return response()->json([
                'ok' => true,
                'action' => 'ignored',
                'reason' => 'Message is not an approval or rejection command.',
            ]);
        }

        // 2. Locate Target Appointment
        $appointment = null;

        if ($appointmentId) {
            $appointment = Appointment::with(['service', 'teamMember'])->find($appointmentId);
        }

        // If not explicitly provided, find the most recent pending appointment for this recipient
        if (!$appointment) {
            // A) Check recent notification sent to this phone
            $recentQueueItem = WhatsAppNotificationQueue::query()
                ->where(function ($q) use ($cleanPhone) {
                    $q->where('recipient_phone', 'LIKE', "%{$cleanPhone}%")
                      ->orWhere('recipient_phone', 'LIKE', '%' . substr($cleanPhone, -8) . '%');
                })
                ->whereNotNull('appointment_id')
                ->orderBy('id', 'desc')
                ->first();

            if ($recentQueueItem && $recentQueueItem->appointment_id) {
                $candidate = Appointment::with(['service', 'teamMember'])->find($recentQueueItem->appointment_id);
                if ($candidate && $candidate->status === 'pending') {
                    $appointment = $candidate;
                }
            }
        }

        // B) Search by user/team member phone
        if (!$appointment) {
            $userIds = User::where(function ($q) use ($cleanPhone) {
                $q->where('phone', 'LIKE', "%{$cleanPhone}%")
                  ->orWhere('phone', 'LIKE', '%' . substr($cleanPhone, -8) . '%');
            })->pluck('id')->toArray();

            $teamUserIds = TeamMember::where(function ($q) use ($cleanPhone) {
                $q->where('phone', 'LIKE', "%{$cleanPhone}%")
                  ->orWhere('phone', 'LIKE', '%' . substr($cleanPhone, -8) . '%');
            })->pluck('user_id')->toArray();

            $allCompanyIds = array_unique(array_merge($userIds, $teamUserIds));

            if (!empty($allCompanyIds)) {
                $appointment = Appointment::with(['service', 'teamMember'])
                    ->whereIn('user_id', $allCompanyIds)
                    ->where('status', 'pending')
                    ->orderBy('id', 'desc')
                    ->first();
            }
        }

        // C) Fallback: latest pending appointment in database
        if (!$appointment) {
            $appointment = Appointment::with(['service', 'teamMember'])
                ->where('status', 'pending')
                ->orderBy('id', 'desc')
                ->first();
        }

        if (!$appointment) {
            Log::warning("[WhatsApp Inbound] No pending appointment found for response from {$cleanPhone}.");
            return response()->json([
                'ok' => true,
                'action' => 'not_found',
                'reply' => "ℹ️ Não encontramos nenhum pedido de agendamento pendente no momento para este número.",
            ]);
        }

        $serviceName = $appointment->service?->name ?? 'Serviço';
        $formattedDate = $appointment->appointment_date ? Carbon::parse($appointment->appointment_date)->format('d/m/Y') : '';
        $formattedTime = $appointment->appointment_time ? substr($appointment->appointment_time, 0, 5) : '';

        // 3. Process Approval
        if ($isApproval) {
            $appointment->update([
                'status' => 'confirmed',
            ]);

            AppointmentFlowLog::record(
                $appointment->user_id,
                'status_changed',
                'Agendamento Aprovado via WhatsApp',
                "O estabelecimento/profissional respondeu 'SIM' no WhatsApp e aprovou o agendamento de {$appointment->client_name}.",
                $appointment->id,
                'whatsapp',
                'success',
                [
                    'phone' => $cleanPhone,
                    'raw_message' => $rawMessage,
                    'new_status' => 'confirmed',
                ]
            );

            // Dispatch customer confirmation notification
            try {
                $payment = Payment::where('appointment_id', $appointment->id)->first();
                $dispatcher->onBookingApproved($appointment->fresh(['service']), $payment);
            } catch (Throwable $e) {
                Log::error('[WhatsApp Inbound] Error triggering onBookingApproved: ' . $e->getMessage());
            }

            $replyText = "✅ *Agendamento #{$appointment->id} Aprovado com Sucesso!*\n\n"
                . "👤 *Cliente:* {$appointment->client_name}\n"
                . "📅 *Data:* {$formattedDate} às {$formattedTime}\n"
                . "✂️ *Serviço:* {$serviceName}\n\n"
                . "✨ O cliente foi notificado pelo WhatsApp com a confirmação!";

            Log::info("[WhatsApp Inbound] Appointment #{$appointment->id} APPROVED successfully by {$cleanPhone}.");

            return response()->json([
                'ok' => true,
                'action' => 'approved',
                'appointment_id' => $appointment->id,
                'reply' => $replyText,
            ]);
        }

        // 4. Process Rejection
        if ($isRejection) {
            $appointment->update([
                'status' => 'cancelled',
            ]);

            AppointmentFlowLog::record(
                $appointment->user_id,
                'status_changed',
                'Agendamento Recusado via WhatsApp',
                "O estabelecimento/profissional respondeu 'NAO' no WhatsApp e recusou o agendamento de {$appointment->client_name}.",
                $appointment->id,
                'whatsapp',
                'danger',
                [
                    'phone' => $cleanPhone,
                    'raw_message' => $rawMessage,
                    'new_status' => 'cancelled',
                ]
            );

            try {
                $dispatcher->onBookingCancelled($appointment->fresh(['service']), 'Recusado pelo estabelecimento via WhatsApp');
            } catch (Throwable $e) {
                Log::error('[WhatsApp Inbound] Error triggering onBookingCancelled: ' . $e->getMessage());
            }

            $replyText = "🚫 *Agendamento #{$appointment->id} Recusado.*\n\n"
                . "👤 *Cliente:* {$appointment->client_name}\n"
                . "📅 *Data:* {$formattedDate} às {$formattedTime}\n\n"
                . "O cliente foi notificado sobre o cancelamento.";

            Log::info("[WhatsApp Inbound] Appointment #{$appointment->id} REJECTED by {$cleanPhone}.");

            return response()->json([
                'ok' => true,
                'action' => 'rejected',
                'appointment_id' => $appointment->id,
                'reply' => $replyText,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
