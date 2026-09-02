<?php

namespace App\Services\NotificationSteps;

use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\NotificationSteps\Contracts\NotificationStepInterface;

class BookingCancelledStep extends BaseNotificationStep implements NotificationStepInterface
{
    public function handle(Appointment $appointment, NotificationSetting $settings, User $company, array $context = []): void
    {
        $reason = $context['reason'] ?? null;
        $serviceName = $appointment->service?->name ?? 'Serviço';
        $appointmentDateTime = $this->resolveAppointmentDateTime($appointment);
        $formattedDate = $appointmentDateTime->format('d/m/Y');
        $formattedTime = $appointmentDateTime->format('H:i');
        $companyName = $company->name;

        // 1. Notify Client
        if ($settings->notify_client_on_cancellation) {
            $clientMessage = "{Olá|Oi|Como vai}, {$appointment->client_name}. {⚠️|ℹ️}\n\n"
                . "Seu agendamento em *{$companyName}* foi cancelado:\n"
                . "📅 *Data:* {$formattedDate}\n"
                . "⏰ *Horário:* {$formattedTime}\n"
                . "✂️ *Serviço:* {$serviceName}\n"
                . ($reason ? "\n📝 *Motivo:* {$reason}\n" : "\n")
                . "Caso deseje realizar um novo agendamento, entre em contato ou acesse nossa página.";

            $this->dispatchNotification(
                settings: $settings,
                appointment: $appointment,
                recipientPhone: $appointment->client_phone,
                recipientEmail: $appointment->client_email,
                recipientName: $appointment->client_name,
                subject: "Agendamento Cancelado - {$companyName}",
                messageBody: $clientMessage,
                messageType: 'cancelled'
            );
        }

        // 2. Notify Staff
        $staff = $this->resolveStaffContact($appointment, $company);
        if ($staff['phone'] || $staff['email']) {
            $staffMessage = "🚫 *Agendamento Cancelado!*\n\n"
                . "🏢 *Empresa:* {$companyName}\n"
                . "👤 *Cliente:* {$appointment->client_name}\n"
                . "📅 *Data:* {$formattedDate} às {$formattedTime}\n"
                . "✂️ *Serviço:* {$serviceName}\n"
                . ($reason ? "📝 *Motivo:* {$reason}" : "");

            $this->dispatchNotification(
                settings: $settings,
                appointment: $appointment,
                recipientPhone: $staff['phone'],
                recipientEmail: $staff['email'],
                recipientName: $staff['name'],
                subject: "Agendamento Cancelado: {$appointment->client_name} - {$serviceName}",
                messageBody: $staffMessage,
                messageType: 'cancelled'
            );
        }

        // 3. Cancel any pending scheduled reminders for this appointment
        try {
            \App\Models\WhatsAppNotificationQueue::where('appointment_id', $appointment->id)
                ->where('message_type', 'reminder')
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        } catch (\Throwable) {}
    }
}
