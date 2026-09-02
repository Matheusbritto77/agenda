<?php

namespace App\Services\NotificationSteps;

use App\Models\Appointment;
use App\Models\AppointmentFlowLog;
use App\Models\BrandingSetting;
use App\Models\NotificationSetting;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WhatsAppNotificationQueue;
use App\Support\PhoneHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

abstract class BaseNotificationStep
{
    /**
     * Resolve appointment DateTime
     */
    protected function resolveAppointmentDateTime(Appointment $appointment): Carbon
    {
        $dateStr = $appointment->appointment_date instanceof Carbon 
            ? $appointment->appointment_date->format('Y-m-d') 
            : (string) $appointment->appointment_date;
        $timeStr = (string) $appointment->appointment_time;

        try {
            return Carbon::parse("{$dateStr} {$timeStr}");
        } catch (Throwable) {
            return Carbon::now();
        }
    }

    /**
     * Resolve staff / professional contact details
     */
    protected function resolveStaffContact(Appointment $appointment, User $company): array
    {
        $staffMember = $appointment->team_member_id ? TeamMember::find($appointment->team_member_id) : null;
        $branding = BrandingSetting::where('user_id', $company->id)->first();

        $phone = $staffMember?->phone 
            ?: ($branding?->settings['whatsapp_number'] ?? null)
            ?: $company->phone;

        $email = $staffMember?->email ?: $company->email;
        $name = $staffMember?->name ?: $company->name;

        return [
            'member' => $staffMember,
            'phone' => $phone,
            'email' => $email,
            'name' => $name,
        ];
    }

    /**
     * Dispatch notification to WhatsApp Queue and/or Email
     */
    protected function dispatchNotification(
        NotificationSetting $settings,
        Appointment $appointment,
        ?string $recipientPhone,
        ?string $recipientEmail,
        ?string $recipientName,
        string $subject,
        string $messageBody,
        string $messageType = 'general'
    ): void {
        $cleanPhone = PhoneHelper::normalize($recipientPhone);

        // 1. Enqueue WhatsApp notification if enabled
        if ($settings->whatsapp_enabled && $cleanPhone) {
            try {
                $queueItem = WhatsAppNotificationQueue::create([
                    'user_id' => $appointment->user_id,
                    'appointment_id' => $appointment->id,
                    'recipient_phone' => $cleanPhone,
                    'recipient_name' => $recipientName,
                    'message_type' => $messageType,
                    'message_body' => $messageBody,
                    'status' => 'pending',
                    'scheduled_for' => now(),
                ]);

                AppointmentFlowLog::record(
                    $appointment->user_id,
                    'whatsapp_enqueued',
                    'Mensagem de WhatsApp Enfileirada',
                    "Notificação ({$messageType}) para {$recipientName} ({$cleanPhone}) enviada para fila do WhatsApp.",
                    $appointment->id,
                    'whatsapp',
                    'info',
                    [
                        'queue_id' => $queueItem->id,
                        'message_type' => $messageType,
                        'recipient_phone' => $cleanPhone,
                    ]
                );
            } catch (Throwable $e) {
                Log::error('[BaseNotificationStep] Error enqueuing WhatsApp message: ' . $e->getMessage());
                AppointmentFlowLog::record(
                    $appointment->user_id,
                    'flow_error',
                    'Erro ao Enfileirar WhatsApp',
                    $e->getMessage(),
                    $appointment->id,
                    'whatsapp',
                    'error'
                );
            }
        }

        // 2. Send Email notification if enabled
        if ($settings->email_enabled && $recipientEmail) {
            try {
                Mail::raw($messageBody, function ($mail) use ($recipientEmail, $recipientName, $subject) {
                    $mail->to($recipientEmail, $recipientName)->subject($subject);
                });

                AppointmentFlowLog::record(
                    $appointment->user_id,
                    'email_sent',
                    'E-mail Disparado com Sucesso',
                    "Notificação por e-mail enviada para {$recipientEmail}.",
                    $appointment->id,
                    'email',
                    'info'
                );
            } catch (Throwable $e) {
                Log::error('[BaseNotificationStep] Error sending Email: ' . $e->getMessage());
            }
        }
    }
}
