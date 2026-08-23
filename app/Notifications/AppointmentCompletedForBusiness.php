<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCompletedForBusiness extends Notification
{
    use Queueable;

    public function __construct(private readonly Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment->loadMissing(['service', 'teamMember']);
        $servicePrice = number_format((float) ($appointment->service?->price ?? 0), 2, ',', '.');
        $adminUrl = rtrim((string) config('app.url'), '/').route('admin.appointments.index', [], false);

        $mail = (new MailMessage)
            ->subject("Agendamento concluído - {$appointment->client_name}")
            ->greeting('Agendamento concluído')
            ->line("Cliente: {$appointment->client_name}")
            ->line("E-mail: {$appointment->client_email}")
            ->line("Telefone: {$appointment->client_phone}")
            ->line('Serviço: '.($appointment->service?->name ?? 'Serviço'))
            ->line('Data: '.$appointment->appointment_date->format('d/m/Y'))
            ->line('Horário: '.substr((string) $appointment->appointment_time, 0, 5))
            ->line("Valor: R$ {$servicePrice}");

        if ($appointment->teamMember?->name) {
            $mail->line("Profissional: {$appointment->teamMember->name}");
        }

        return $mail->action('Abrir agenda', $adminUrl);
    }
}
