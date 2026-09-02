<?php

namespace App\Services\NotificationSteps;

use App\Models\Appointment;
use App\Models\AppointmentFlowLog;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Models\WhatsAppNotificationQueue;
use App\Services\NotificationSteps\Contracts\NotificationStepInterface;
use App\Support\PhoneHelper;
use Carbon\Carbon;

class BookingReminderStep extends BaseNotificationStep implements NotificationStepInterface
{
    public function handle(Appointment $appointment, NotificationSetting $settings, User $company, array $context = []): void
    {
        $clientCleanPhone = PhoneHelper::normalize($appointment->client_phone);

        if (!$settings->reminder_enabled || !$settings->whatsapp_enabled || !$clientCleanPhone) {
            return;
        }

        $serviceName = $appointment->service?->name ?? 'Serviço';
        $appointmentDateTime = $this->resolveAppointmentDateTime($appointment);
        $formattedDate = $appointmentDateTime->format('d/m/Y');
        $formattedTime = $appointmentDateTime->format('H:i');
        $companyName = $company->name;

        $reminderTime = $this->calculateReminderTime(
            $appointmentDateTime,
            $settings->reminder_time_value,
            $settings->reminder_time_unit
        );

        if ($reminderTime->isFuture()) {
            $reminderBody = "⏰ *Lembrete de Agendamento - {$companyName}*\n\n"
                . "{Olá|Oi|Como vai}, {$appointment->client_name}! {Lembramos|Passando para lembrar} que você tem um horário marcado:\n"
                . "📅 *Data:* {$formattedDate}\n"
                . "⏰ *Horário:* {$formattedTime}\n"
                . "✂️ *Serviço:* {$serviceName}\n\n"
                . "{Podemos confirmar sua presença?|Esperamos por você!}";

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

            AppointmentFlowLog::record(
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

    protected function calculateReminderTime(Carbon $appointmentTime, int $value, string $unit): Carbon
    {
        $time = $appointmentTime->copy();
        return match ($unit) {
            'minutes' => $time->subMinutes($value),
            'hours' => $time->subHours($value),
            'days' => $time->subDays($value),
            default => $time->subHours(2),
        };
    }
}
