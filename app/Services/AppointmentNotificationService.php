<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ClientAccount;
use App\Notifications\AppointmentCompletedForBusiness;
use App\Notifications\AppointmentCompletedForClient;
use App\Notifications\AppointmentConfirmedForBusiness;
use App\Notifications\AppointmentConfirmedForClient;
use App\Notifications\ClientAccountCreated;
use Illuminate\Notifications\Notification as NotificationContract;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class AppointmentNotificationService
{
    public function sendBookingConfirmation(
        Appointment $appointment,
        ?ClientAccount $clientAccount = null,
        ?string $temporaryPassword = null
    ): void {
        $appointment->loadMissing(['service', 'tenant', 'teamMember']);

        if ($clientAccount) {
            $this->sendSafely($clientAccount, new AppointmentConfirmedForClient($appointment));

            if ($temporaryPassword !== null) {
                $this->sendSafely($clientAccount, new ClientAccountCreated($temporaryPassword));
            }
        }

        $this->sendToBusinessRecipients($appointment, new AppointmentConfirmedForBusiness($appointment));
    }

    public function sendCompletion(Appointment $appointment): void
    {
        $appointment->loadMissing(['service', 'tenant', 'teamMember']);
        $clientEmail = Str::lower(trim((string) $appointment->client_email));

        if (filter_var($clientEmail, FILTER_VALIDATE_EMAIL) !== false) {
            $this->sendOnDemandSafely($clientEmail, new AppointmentCompletedForClient($appointment));
        }

        $this->sendToBusinessRecipients($appointment, new AppointmentCompletedForBusiness($appointment));
    }

    private function sendToBusinessRecipients(Appointment $appointment, NotificationContract $notification): void
    {
        $recipients = collect([
            $appointment->tenant?->email,
            $appointment->teamMember?->email,
        ])
            ->map(fn ($email): string => Str::lower(trim((string) $email)))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique();

        foreach ($recipients as $email) {
            $this->sendOnDemandSafely($email, clone $notification);
        }
    }

    public function sendSafely(object $notifiable, NotificationContract $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function sendOnDemandSafely(string $email, NotificationContract $notification): void
    {
        try {
            Notification::route('mail', $email)->notify($notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
