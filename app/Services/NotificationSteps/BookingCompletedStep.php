<?php

namespace App\Services\NotificationSteps;

use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\NotificationSteps\Contracts\NotificationStepInterface;

class BookingCompletedStep extends BaseNotificationStep implements NotificationStepInterface
{
    public function handle(Appointment $appointment, NotificationSetting $settings, User $company, array $context = []): void
    {
        $serviceName = $appointment->service?->name ?? 'Serviço';
        $companyName = $company->name;

        if ($settings->whatsapp_enabled && !empty($appointment->client_phone)) {
            $clientMessage = "✨ *Obrigado pela visita - {$companyName}!*\n\n"
                . "{Olá|Oi}, {$appointment->client_name}! Agradecemos a preferência pelo serviço *{$serviceName}*.\n\n"
                . "Esperamos que tenha tido uma excelente experiência! Até o próximo agendamento.";

            $this->dispatchNotification(
                settings: $settings,
                appointment: $appointment,
                recipientPhone: $appointment->client_phone,
                recipientEmail: $appointment->client_email,
                recipientName: $appointment->client_name,
                subject: "Obrigado pela visita - {$companyName}",
                messageBody: $clientMessage,
                messageType: 'completed'
            );
        }
    }
}
