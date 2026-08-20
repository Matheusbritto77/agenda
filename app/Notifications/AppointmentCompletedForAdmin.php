<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCompletedForAdmin extends Notification
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
        $servicePrice = number_format((float) ($appointment->service?->price ?? 0), 2, ',', '.');

        return (new MailMessage)
            ->subject('Agendamento concluído')
            ->greeting('Novo encerramento de agendamento')
            ->line("Cliente: {$appointment->client_name}")
            ->line("E-mail: {$appointment->client_email}")
            ->line("Telefone: {$appointment->client_phone}")
            ->line("Serviço: {$serviceName}")
            ->line("Data: {$date}")
            ->line("Horário: {$time}")
            ->line("Valor: R$ {$servicePrice}")
            ->action('Abrir agenda administrativa', route('admin.appointments.index'));
    }
}
