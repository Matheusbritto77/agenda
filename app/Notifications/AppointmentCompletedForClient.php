<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCompletedForClient extends Notification
{
    use Queueable;

    public function __construct(private readonly Appointment $appointment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment->loadMissing('service');
        $date = $appointment->appointment_date->format('d/m/Y');
        $time = substr($appointment->appointment_time, 0, 5);
        $serviceName = $appointment->service?->name ?? 'Serviço';
        $publicUrl = $appointment->tenant?->publicBookingUrl() ?? url('/');

        return (new MailMessage)
            ->subject('Seu agendamento foi concluído')
            ->greeting("Olá, {$appointment->client_name}")
            ->line('Seu agendamento foi concluído com sucesso.')
            ->line("Serviço: {$serviceName}")
            ->line("Data: {$date}")
            ->line("Horário: {$time}")
            ->line('Obrigado pela preferência.')
            ->action('Ver agendamentos', $publicUrl);
    }
}
