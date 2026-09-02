<?php

namespace App\Services\NotificationSteps;

use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\NotificationSteps\Contracts\NotificationStepInterface;

class BookingPendingApprovalStep extends BaseNotificationStep implements NotificationStepInterface
{
    public function handle(Appointment $appointment, NotificationSetting $settings, User $company, array $context = []): void
    {
        $serviceName = $appointment->service?->name ?? 'Serviço';
        $appointmentDateTime = $this->resolveAppointmentDateTime($appointment);
        $formattedDate = $appointmentDateTime->format('d/m/Y');
        $formattedTime = $appointmentDateTime->format('H:i');
        $companyName = $company->name;

        // 1. Notify Client (Aguardando Aprovação)
        if ($settings->notify_client_on_booking) {
            $clientMessage = "{Olá|Oi|Como vai}, {$appointment->client_name}! {👋|✨}\n\n"
                . "Recebemos sua solicitação de agendamento em *{$companyName}*:\n"
                . "📅 *Data:* {$formattedDate}\n"
                . "⏰ *Horário:* {$formattedTime}\n"
                . "✂️ *Serviço:* {$serviceName}\n\n"
                . "⏳ *Status:* Aguardando confirmação do estabelecimento. Assim que for aprovado, avisaremos você por aqui!";

            $this->dispatchNotification(
                settings: $settings,
                appointment: $appointment,
                recipientPhone: $appointment->client_phone,
                recipientEmail: $appointment->client_email,
                recipientName: $appointment->client_name,
                subject: "Solicitação de Agendamento Recebida - {$companyName}",
                messageBody: $clientMessage,
                messageType: 'booking_created'
            );
        }

        // 2. Notify Staff with SIM / NAO options
        if ($settings->notify_staff_on_booking) {
            $staff = $this->resolveStaffContact($appointment, $company);

            if ($staff['phone'] || $staff['email']) {
                $staffMessage = "🚨 *Novo Pedido de Agendamento Recebido!* (#{$appointment->id})\n\n"
                    . "🏢 *Empresa:* {$companyName}\n"
                    . "👤 *Cliente:* {$appointment->client_name} ({$appointment->client_phone})\n"
                    . "📅 *Data:* {$formattedDate} às {$formattedTime}\n"
                    . "✂️ *Serviço:* {$serviceName}\n\n"
                    . "⚠️ *Ação necessária para aprovação:*\n"
                    . "👉 Marque esta mensagem e responda *SIM* para APROVAR\n"
                    . "👉 Marque esta mensagem e responda *NAO* para RECUSAR\n"
                    . "👉 Marque esta mensagem e responda *REMARCAR* para PEDIR NOVO HORÁRIO\n"
                    . "_(Ou envie SIM {$appointment->id} / NAO {$appointment->id} / REMARCAR {$appointment->id})_";

                $this->dispatchNotification(
                    settings: $settings,
                    appointment: $appointment,
                    recipientPhone: $staff['phone'],
                    recipientEmail: $staff['email'],
                    recipientName: $staff['name'],
                    subject: "Novo Pedido de Agendamento: {$appointment->client_name} - {$serviceName}",
                    messageBody: $staffMessage,
                    messageType: 'booking_created'
                );
            }
        }
    }
}
