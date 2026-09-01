<?php

namespace App\Services;

use App\Mail\AppointmentNotificationMail;
use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\Payment;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WhatsAppNotificationQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationDispatcherService
{
    /**
     * Dispatch notifications when a new booking is created
     */
    public function onBookingCreated(Appointment $appointment): void
    {
        try {
            $company = User::find($appointment->user_id);
            if (!$company) return;

            $settings = NotificationSetting::forUser($company->id);
            $serviceName = $appointment->service?->name ?? 'Serviço';
            $appointmentDateTime = $this->parseAppointmentDateTime($appointment);
            $formattedDate = $appointmentDateTime->format('d/m/Y');
            $formattedTime = $appointmentDateTime->format('H:i');
            $companyName = $company->name;

            $requiresApproval = (bool) $settings->require_manual_confirmation;

            // 1. Notify Client
            if ($settings->notify_client_on_booking) {
                if ($requiresApproval) {
                    $clientMessage = "{Olá|Oi|Como vai}, {$appointment->client_name}! {👋|✨}\n\nRecebemos sua solicitação de agendamento em *{$companyName}*:\n📅 *Data:* {$formattedDate}\n⏰ *Horário:* {$formattedTime}\n✂️ *Serviço:* {$serviceName}\n\n⏳ *Status:* Aguardando confirmação do estabelecimento. Assim que for aprovado, avisaremos você por aqui!";
                } else {
                    $clientMessage = "{Olá|Oi|Como vai}, {$appointment->client_name}! {✅|✨}\n\nSeu agendamento em *{$companyName}* está confirmado com sucesso!\n📅 *Data:* {$formattedDate}\n⏰ *Horário:* {$formattedTime}\n✂️ *Serviço:* {$serviceName}\n\nEsperamos você! Se precisar reagendar, entre em contato.";
                }

                $this->dispatchNotification(
                    settings: $settings,
                    appointment: $appointment,
                    recipientPhone: $appointment->client_phone,
                    recipientEmail: $appointment->client_email,
                    recipientName: $appointment->client_name,
                    subject: $requiresApproval ? "Solicitação de Agendamento Recebida - {$companyName}" : "Agendamento Confirmado - {$companyName}",
                    messageBody: $clientMessage,
                    messageType: 'booking_created'
                );
            } else {
                \App\Models\AppointmentFlowLog::record(
                    $company->id,
                    'notification_skipped',
                    'Notificação do Cliente Ignorada',
                    "Notificação para {$appointment->client_name} não enviada pois 'notify_client_on_booking' está desativado.",
                    $appointment->id,
                    'system',
                    'info'
                );
            }

            // 2. Notify Staff / Company
            if ($settings->notify_staff_on_booking) {
                $staffMember = $appointment->team_member_id ? TeamMember::find($appointment->team_member_id) : null;
                $branding = \App\Models\BrandingSetting::where('user_id', $company->id)->first();
                $staffPhone = $staffMember?->phone 
                    ?: ($branding?->settings['whatsapp_number'] ?? null)
                    ?: $company->phone;
                $staffEmail = $staffMember?->email ?: $company->email;
                $staffName = $staffMember?->name ?: $companyName;

                if ($staffPhone || $staffEmail) {
                    if ($requiresApproval) {
                        $staffMessage = "🚨 *Novo Pedido de Agendamento Recebido!*\n\n🏢 *Empresa:* {$companyName}\n👤 *Cliente:* {$appointment->client_name} ({$appointment->client_phone})\n📅 *Data:* {$formattedDate} às {$formattedTime}\n✂️ *Serviço:* {$serviceName}\n\n⚠️ *Ação necessária para aprovação:*\n👉 Responda *SIM* para APROVAR\n👉 Responda *NAO* para RECUSAR\n_(Ou gerencie diretamente pelo painel administrativo do Agendae)_";
                    } else {
                        $staffMessage = "🚨 *Novo Agendamento Confirmado!*\n\n🏢 *Empresa:* {$companyName}\n👤 *Cliente:* {$appointment->client_name} ({$appointment->client_phone})\n📅 *Data:* {$formattedDate} às {$formattedTime}\n✂️ *Serviço:* {$serviceName}";
                    }

                    $this->dispatchNotification(
                        settings: $settings,
                        appointment: $appointment,
                        recipientPhone: $staffPhone,
                        recipientEmail: $staffEmail,
                        recipientName: $staffName,
                        subject: $requiresApproval ? "Novo Pedido de Agendamento: {$appointment->client_name} - {$serviceName}" : "Novo Agendamento: {$appointment->client_name} - {$serviceName}",
                        messageBody: $staffMessage,
                        messageType: 'booking_created'
                    );
                }
            } else {
                \App\Models\AppointmentFlowLog::record(
                    $company->id,
                    'notification_skipped',
                    'Notificação da Equipe Ignorada',
                    "Notificação para a equipe não enviada pois 'notify_staff_on_booking' está desativado.",
                    $appointment->id,
                    'system',
                    'info'
                );
            }

            // 3. Schedule Advance Confirmation Reminder
            $clientCleanPhone = \App\Support\PhoneHelper::normalize($appointment->client_phone);
            if ($settings->reminder_enabled && $settings->whatsapp_enabled && $clientCleanPhone) {
                $reminderTime = $this->calculateReminderTime(
                    $appointmentDateTime,
                    $settings->reminder_time_value,
                    $settings->reminder_time_unit
                );

                if ($reminderTime->isFuture()) {
                    $reminderBody = "⏰ *Lembrete de Agendamento - {$companyName}*\n\n{Olá|Oi|Como vai}, {$appointment->client_name}! {Lembramos|Passando para lembrar} que você tem um horário marcado:\n📅 *Data:* {$formattedDate}\n⏰ *Horário:* {$formattedTime}\n✂️ *Serviço:* {$serviceName}\n\n{Podemos confirmar sua presença?|Esperamos por você!}";

                    $queueItem = WhatsAppNotificationQueue::create([
                        'user_id' => $company->id,
                        'appointment_id' => $appointment->id,
                        'recipient_phone' => $clientCleanPhone,
                        'recipient_name' => $appointment->client_name,
                        'message_type' => 'reminder',
                        'message_body' => $reminderBody,
                        'status' => 'pending',
                        'scheduled_for' => $reminderTime,
                    ]);

                    \App\Models\AppointmentFlowLog::record(
                        $company->id,
                        'reminder_scheduled',
                        'Lembrete de WhatsApp Programado',
                        "Lembrete para {$appointment->client_name} ({$clientCleanPhone}) agendado para {$reminderTime->format('d/m/Y H:i')}.",
                        $appointment->id,
                        'whatsapp',
                        'info',
                        ['queue_id' => $queueItem->id, 'scheduled_for' => $reminderTime->toIso8601String()]
                    );
                }
            }
        } catch (Throwable $e) {
            Log::error('[NotificationDispatcher] Error on booking created: ' . $e->getMessage());
            \App\Models\AppointmentFlowLog::record(
                $appointment->user_id,
                'flow_error',
                'Erro no Processamento de Notificações',
                $e->getMessage(),
                $appointment->id,
                'system',
                'error'
            );
        }
    }

    /**
     * Dispatch notification when an appointment is approved / confirmed
     */
    public function onBookingApproved(Appointment $appointment, ?Payment $payment = null): void
    {
        try {
            $company = User::find($appointment->user_id);
            if (!$company) return;

            $settings = NotificationSetting::forUser($company->id);
            if (!$settings->notify_client_on_confirmation) {
                \App\Models\AppointmentFlowLog::record(
                    $company->id,
                    'notification_skipped',
                    'Notificação de Confirmação Ignorada',
                    "Notificação de aprovação para {$appointment->client_name} não enviada pois 'notify_client_on_confirmation' está desativado.",
                    $appointment->id,
                    'system',
                    'info'
                );
                return;
            }

            $serviceName = $appointment->service?->name ?? 'Serviço';
            $appointmentDateTime = $this->parseAppointmentDateTime($appointment);
            $formattedDate = $appointmentDateTime->format('d/m/Y');
            $formattedTime = $appointmentDateTime->format('H:i');
            $companyName = $company->name;

            // If payment is pending and PIX code is available
            if ($payment && $payment->status === 'pending' && $payment->pix_qr_code) {
                $message = "{🎉|✨} *Agendamento Aprovado - Quase Concluído!*\n\n{Olá|Oi|Como vai}, {$appointment->client_name}! Seu agendamento em *{$companyName}* foi aprovado pelo profissional:\n📅 *Data:* {$formattedDate} às {$formattedTime}\n✂️ *Serviço:* {$serviceName}\n💰 *Valor:* R$ " . number_format((float) $payment->amount, 2, ',', '.') . "\n\n🔑 *Pague via PIX para garantir seu horário:*\nCopie o código abaixo e cole no app do seu banco:\n\n`{$payment->pix_qr_code}`\n\nAssim que o pagamento for identificado, seu horário estará 100% garantido!";
            } else {
                $message = "{🎉|✨} *Agendamento Confirmado!*\n\n{Olá|Oi|Como vai}, {$appointment->client_name}! O seu agendamento em *{$companyName}* foi aprovado com sucesso:\n📅 *Data:* {$formattedDate}\n⏰ *Horário:* {$formattedTime}\n✂️ *Serviço:* {$serviceName}\n\n{Esperamos você! Obrigado pela preferência.|Contamos com você!}";
            }

            $this->dispatchNotification(
                settings: $settings,
                appointment: $appointment,
                recipientPhone: $appointment->client_phone,
                recipientEmail: $appointment->client_email,
                recipientName: $appointment->client_name,
                subject: "Agendamento Aprovado - {$companyName}",
                messageBody: $message,
                messageType: $payment ? 'pix_payment' : 'confirmed'
            );
        } catch (Throwable $e) {
            Log::error('[NotificationDispatcher] Error on booking approved: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch notification when an appointment is cancelled / rejected
     */
    public function onBookingCancelled(Appointment $appointment, ?string $reason = null): void
    {
        try {
            $company = User::find($appointment->user_id);
            if (!$company) return;

            $settings = NotificationSetting::forUser($company->id);
            if (!$settings->notify_client_on_cancellation) return;

            $serviceName = $appointment->service?->name ?? 'Serviço';
            $appointmentDateTime = $this->parseAppointmentDateTime($appointment);
            $formattedDate = $appointmentDateTime->format('d/m/Y');
            $formattedTime = $appointmentDateTime->format('H:i');
            $companyName = $company->name;

            $message = "⚠️ *Aviso de Agendamento Cancelado*\n\nOlá, {$appointment->client_name}! Informamos que o seu agendamento em *{$companyName}* ({$serviceName} em {$formattedDate} às {$formattedTime}) foi cancelado." . ($reason ? "\n\n*Motivo:* {$reason}" : "") . "\n\nPara agendar um novo horário, acesse nossa página.";

            $this->dispatchNotification(
                settings: $settings,
                appointment: $appointment,
                recipientPhone: $appointment->client_phone,
                recipientEmail: $appointment->client_email,
                recipientName: $appointment->client_name,
                subject: "Agendamento Cancelado - {$companyName}",
                messageBody: $message,
                messageType: 'cancelled'
            );
        } catch (Throwable $e) {
            Log::error('[NotificationDispatcher] Error on booking cancelled: ' . $e->getMessage());
        }
    }

    /**
     * Internal helper to dispatch via email and enqueue in WhatsApp queue
     */
    private function dispatchNotification(
        NotificationSetting $settings,
        Appointment $appointment,
        ?string $recipientPhone,
        ?string $recipientEmail,
        string $recipientName,
        string $subject,
        string $messageBody,
        string $messageType
    ): void {
        // 1. Enqueue WhatsApp if enabled
        $cleanPhone = \App\Support\PhoneHelper::normalize($recipientPhone);
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
                    'scheduled_for' => Carbon::now(),
                ]);

                \App\Models\AppointmentFlowLog::record(
                    $appointment->user_id,
                    'whatsapp_enqueued',
                    "Notificação WhatsApp Enfileirada ({$messageType})",
                    "Mensagem para {$recipientName} ({$cleanPhone}) inserida na fila do WhatsApp com status pendente.",
                    $appointment->id,
                    'whatsapp',
                    'success',
                    [
                        'queue_id' => $queueItem->id,
                        'recipient_phone' => $cleanPhone,
                        'message_type' => $messageType,
                    ]
                );

                Log::info("[NotificationDispatcher] WhatsApp enqueued successfully [ID: {$queueItem->id}] to {$cleanPhone} ({$messageType})");
            } catch (Throwable $queueErr) {
                Log::error("[NotificationDispatcher] WhatsApp queue insert error: " . $queueErr->getMessage());
                \App\Models\AppointmentFlowLog::record(
                    $appointment->user_id,
                    'whatsapp_queue_error',
                    'Erro ao Enfileirar WhatsApp',
                    $queueErr->getMessage(),
                    $appointment->id,
                    'whatsapp',
                    'error',
                    ['recipient_phone' => $cleanPhone]
                );
            }
        } elseif (!$settings->whatsapp_enabled) {
            \App\Models\AppointmentFlowLog::record(
                $appointment->user_id,
                'whatsapp_skipped',
                'WhatsApp Desativado',
                "Disparo para {$recipientName} ({$cleanPhone}) ignorado pois o canal WhatsApp está desativado nas configurações.",
                $appointment->id,
                'whatsapp',
                'info'
            );
        }

        // 2. Send Email if enabled
        if ($settings->email_enabled && $recipientEmail) {
            try {
                Mail::to($recipientEmail)->queue(new AppointmentNotificationMail(
                    subject: $subject,
                    recipientName: $recipientName,
                    messageBody: $messageBody,
                    appointment: $appointment
                ));

                \App\Models\AppointmentFlowLog::record(
                    $appointment->user_id,
                    'email_dispatched',
                    "E-mail Enfileirado ({$messageType})",
                    "E-mail de notificação para {$recipientName} ({$recipientEmail}) enfileirado com assunto: {$subject}.",
                    $appointment->id,
                    'email',
                    'success',
                    ['recipient_email' => $recipientEmail, 'subject' => $subject]
                );
            } catch (Throwable $mailErr) {
                Log::warning('[NotificationDispatcher] Email dispatch error: ' . $mailErr->getMessage());
                \App\Models\AppointmentFlowLog::record(
                    $appointment->user_id,
                    'email_error',
                    'Erro no Envio de E-mail',
                    $mailErr->getMessage(),
                    $appointment->id,
                    'email',
                    'error',
                    ['recipient_email' => $recipientEmail]
                );
            }
        } elseif (!$settings->email_enabled) {
            \App\Models\AppointmentFlowLog::record(
                $appointment->user_id,
                'email_skipped',
                'E-mail Desativado',
                "Envio de e-mail para {$recipientName} ({$recipientEmail}) ignorado pois o canal E-mail está desativado.",
                $appointment->id,
                'email',
                'info'
            );
        }
    }

    private function calculateReminderTime(Carbon $appointmentDateTime, int $value, string $unit): Carbon
    {
        $time = $appointmentDateTime->copy();
        return match ($unit) {
            'minutes' => $time->subMinutes($value),
            'days' => $time->subDays($value),
            default => $time->subHours($value),
        };
    }

    /**
     * Safely parse appointment date and time into a Carbon instance
     */
    private function parseAppointmentDateTime(Appointment $appointment): Carbon
    {
        $dateStr = $appointment->appointment_date instanceof Carbon
            ? $appointment->appointment_date->format('Y-m-d')
            : substr((string) $appointment->appointment_date, 0, 10);

        $timeStr = substr((string) $appointment->appointment_time, 0, 5) ?: '00:00';

        return Carbon::parse("{$dateStr} {$timeStr}");
    }
}
