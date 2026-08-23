<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmedForBusiness extends Notification
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
        $status = $appointment->status === 'pending' ? 'recebido' : 'confirmado';
        $adminUrl = rtrim((string) config('app.url'), '/').route('admin.appointments.index', [], false);

        $mail = (new MailMessage)
            ->subject("Novo agendamento {$status} - {$appointment->client_name}")
            ->greeting('Novo agendamento')
            ->line("O agendamento de {$appointment->client_name} foi {$status}.")
            ->line('Serviço: '.($appointment->service?->name ?? 'Serviço'))
            ->line('Data: '.$appointment->appointment_date->format('d/m/Y'))
            ->line('Horário: '.substr((string) $appointment->appointment_time, 0, 5))
            ->line("Telefone do cliente: {$appointment->client_phone}")
            ->line("E-mail do cliente: {$appointment->client_email}");

        if ($appointment->teamMember?->name) {
            $mail->line("Profissional: {$appointment->teamMember->name}");
        }

        return $mail->action('Abrir agenda', $adminUrl);
    }
}
