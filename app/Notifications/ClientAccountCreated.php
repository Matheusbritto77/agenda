<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientAccountCreated extends Notification
{
    use Queueable;

    public function __construct(private readonly string $temporaryPassword) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = rtrim((string) config('app.url'), '/').route('client.login', [], false);

        return (new MailMessage)
            ->subject('Seu acesso à área do cliente')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Criamos sua conta na área do cliente para reunir seu histórico em todas as empresas que usam o Agendae.')
            ->line("E-mail de acesso: {$notifiable->email}")
            ->line("Senha temporária: {$this->temporaryPassword}")
            ->line('Por segurança, você deverá escolher uma nova senha no primeiro acesso.')
            ->action('Entrar na área do cliente', $loginUrl);
    }
}
