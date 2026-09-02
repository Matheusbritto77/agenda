<?php

namespace App\Services\NotificationSteps;

use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\Payment;
use App\Models\User;
use App\Services\NotificationSteps\Contracts\NotificationStepInterface;

class BookingApprovedStep extends BaseNotificationStep implements NotificationStepInterface
{
    public function handle(Appointment $appointment, NotificationSetting $settings, User $company, array $context = []): void
    {
        /** @var Payment|null $payment */
        $payment = $context['payment'] ?? Payment::where('appointment_id', $appointment->id)->first();

        $serviceName = $appointment->service?->name ?? 'Serviço';
        $appointmentDateTime = $this->resolveAppointmentDateTime($appointment);
        $formattedDate = $appointmentDateTime->format('d/m/Y');
        $formattedTime = $appointmentDateTime->format('H:i');
        $companyName = $company->name;

        // 1. Notify Client upon approval
        if ($settings->notify_client_on_confirmation) {
            if ($payment && $payment->status === 'pending' && $payment->pix_qr_code) {
                $clientMessage = "{🎉|✨} *Agendamento Aprovado - Quase Concluído!*\n\n"
                    . "{Olá|Oi|Como vai}, {$appointment->client_name}! Seu agendamento em *{$companyName}* foi aprovado pelo profissional:\n"
                    . "📅 *Data:* {$formattedDate} às {$formattedTime}\n"
                    . "✂️ *Serviço:* {$serviceName}\n"
                    . "💰 *Valor:* R$ " . number_format((float) $payment->amount, 2, ',', '.') . "\n\n"
                    . "🔑 *Pague via PIX para garantir seu horário:*\n"
                    . "Copie o código abaixo e cole no app do seu banco:\n\n"
                    . "`{$payment->pix_qr_code}`\n\n"
                    . "Assim que o pagamento for identificado, seu horário estará 100% garantido!";

                $messageType = 'pix_payment';
            } else {
                $clientMessage = "{🎉|✨} *Agendamento Confirmado!*\n\n"
                    . "{Olá|Oi|Como vai}, {$appointment->client_name}! O seu agendamento em *{$companyName}* foi aprovado com sucesso:\n"
                    . "📅 *Data:* {$formattedDate}\n"
                    . "⏰ *Horário:* {$formattedTime}\n"
                    . "✂️ *Serviço:* {$serviceName}\n\n"
                    . "{Esperamos você! Obrigado pela preferência.|Contamos com você!}";

                $messageType = 'confirmed';
            }

            $this->dispatchNotification(
                settings: $settings,
                appointment: $appointment,
                recipientPhone: $appointment->client_phone,
                recipientEmail: $appointment->client_email,
                recipientName: $appointment->client_name,
                subject: "Agendamento Aprovado - {$companyName}",
                messageBody: $clientMessage,
                messageType: $messageType
            );
        }

        // 2. Notify Staff about confirmed appointment
        $staff = $this->resolveStaffContact($appointment, $company);
        if ($staff['phone'] || $staff['email']) {
            $staffMessage = "✅ *Agendamento Confirmado!*\n\n"
                . "🏢 *Empresa:* {$companyName}\n"
                . "👤 *Cliente:* {$appointment->client_name} ({$appointment->client_phone})\n"
                . "📅 *Data:* {$formattedDate} às {$formattedTime}\n"
                . "✂️ *Serviço:* {$serviceName}\n\n"
                . "✨ Horário aprovado e confirmado na sua agenda.";

            $this->dispatchNotification(
                settings: $settings,
                appointment: $appointment,
                recipientPhone: $staff['phone'],
                recipientEmail: $staff['email'],
                recipientName: $staff['name'],
                subject: "Agendamento Confirmado: {$appointment->client_name} - {$serviceName}",
                messageBody: $staffMessage,
                messageType: 'confirmed'
            );
        }
    }
}
