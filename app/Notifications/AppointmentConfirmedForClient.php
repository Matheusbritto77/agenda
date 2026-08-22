<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmedForClient extends Notification
{
    use Queueable;

    public function __construct(private readonly Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment->loadMissing(['service', 'teamMember', 'tenant']);
        $companyName = $appointment->tenant?->name ?? config('app.name');
        $professional = $appointment->teamMember?->name;
        $clientAreaUrl = rtrim((string) config('app.url'), '/').route('client.dashboard', [], false);

        $mail = (new MailMessage)
            ->subject("Agendamento confirmado - {$companyName}")
            ->greeting("Olá, {$appointment->client_name}!")
            ->line("Seu agendamento com {$companyName} está confirmado.")
            ->line('Serviço: '.($appointment->service?->name ?? 'Serviço'))
            ->line('Data: '.$appointment->appointment_date->format('d/m/Y'))
            ->line('Horário: '.substr((string) $appointment->appointment_time, 0, 5));

        if ($professional) {
            $mail->line("Profissional: {$professional}");
        }

        return $mail
            ->line('Na área do cliente você acompanha este e outros atendimentos.')
            ->action('Acessar área do cliente', $clientAreaUrl);
    }
}
