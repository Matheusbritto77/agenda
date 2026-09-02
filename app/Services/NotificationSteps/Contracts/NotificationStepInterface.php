<?php

namespace App\Services\NotificationSteps\Contracts;

use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\User;

interface NotificationStepInterface
{
    /**
     * Execute the notification step
     */
    public function handle(Appointment $appointment, NotificationSetting $settings, User $company, array $context = []): void;
}
