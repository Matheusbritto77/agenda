<?php

namespace App\Services\NotificationSteps;

use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\NotificationSteps\Contracts\NotificationStepInterface;

class BookingAutoConfirmedStep extends BaseNotificationStep implements NotificationStepInterface
{
    public function handle(Appointment $appointment, NotificationSetting $settings, User $company, array $context = []): void
    {
        $serviceName = $appointment->service?->name ?? 'Serviço';
        $appointmentDateTime = $this->resolveAppointmentDateTime($appointment);
        $formattedDate = $appointmentDateTime->format('d/m/Y');
        $formattedTime = $appointmentDateTime->format('H:i');
        $companyName = $company->name;

        // 1. Notify Client (Confirmado automaticamente)
        if ($settings->notify_client_on_booking) {
            $clientMessage = "{Olá|Oi|Como vai}, {$appointment->client_name}! {✅|✨}\n\n"
                . "Seu agendamento em *{$companyName}* está confirmado com sucesso!\n"
                . "📅 *Data:* {$formattedDate}\n"
                . "⏰ *Horário:* {$formattedTime}\n"
                . "✂️ *Serviço:* {$serviceName}\n\n"
                . "Esperamos você! Se precisar reagendar, entre em contato.";

            $this->dispatchNotification(
                settings: $settings,
                appointment: $appointment,
                recipientPhone: $appointment->client_phone,
                recipientEmail: $appointment->client_email,
                recipientName: $appointment->client_name,
                subject: "Agendamento Confirmado - {$companyName}",
                messageBody: $clientMessage,
                messageType: 'booking_created'
            );
        }

        // 2. Notify Staff
        if ($settings->notify_staff_on_booking) {
            $staff = $this->resolveStaffContact($appointment, $company);

            if ($staff['phone'] || $staff['email']) {
                $staffMessage = "🚨 *Novo Agendamento Confirmado!*\n\n"
                    . "🏢 *Empresa:* {$companyName}\n"
                    . "👤 *Cliente:* {$appointment->client_name} ({$appointment->client_phone})\n"
                    . "📅 *Data:* {$formattedDate} às {$formattedTime}\n"
                    . "✂️ *Serviço:* {$serviceName}";

                $this->dispatchNotification(
                    settings: $settings,
                    appointment: $appointment,
                    recipientPhone: $staff['phone'],
                    recipientEmail: $staff['email'],
                    recipientName: $staff['name'],
                    subject: "Novo Agendamento: {$appointment->client_name} - {$serviceName}",
                    messageBody: $staffMessage,
                    messageType: 'booking_created'
                );
            }
        }
    }
}
