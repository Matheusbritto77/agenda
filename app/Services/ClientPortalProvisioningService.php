<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ClientAccount;
use App\Notifications\AppointmentConfirmedForClient;
use App\Notifications\ClientAccountCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class ClientPortalProvisioningService
{
    public function provisionFor(Appointment $appointment): ?ClientAccount
    {
        $email = Str::lower(trim((string) $appointment->client_email));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        [$account, $temporaryPassword] = DB::transaction(function () use ($appointment, $email): array {
            $account = ClientAccount::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();
            $temporaryPassword = null;

            if (! $account) {
                $temporaryPassword = Str::random(12);
                $account = ClientAccount::create([
                    'name' => $appointment->client_name,
                    'email' => $email,
                    'phone' => $appointment->client_phone,
                    'password' => Hash::make($temporaryPassword),
                    'must_reset_password' => true,
                ]);
            } elseif (! $account->phone && $appointment->client_phone) {
                $account->update(['phone' => $appointment->client_phone]);
            }

            Appointment::query()
                ->whereRaw('LOWER(client_email) = ?', [$email])
                ->whereNull('client_account_id')
                ->update(['client_account_id' => $account->id]);

            return [$account, $temporaryPassword];
        });

        $appointment->refresh();
        $this->sendSafely($account, new AppointmentConfirmedForClient($appointment));

        if ($temporaryPassword !== null) {
            $this->sendSafely($account, new ClientAccountCreated($temporaryPassword));
        }

        return $account;
    }

    private function sendSafely(ClientAccount $account, object $notification): void
    {
        try {
            $account->notify($notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
