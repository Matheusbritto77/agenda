<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\Payment;
use App\Models\User;
use App\Services\NotificationSteps\BookingApprovedStep;
use App\Services\NotificationSteps\BookingAutoConfirmedStep;
use App\Services\NotificationSteps\BookingCancelledStep;
use App\Services\NotificationSteps\BookingCompletedStep;
use App\Services\NotificationSteps\BookingPendingApprovalStep;
use App\Services\NotificationSteps\BookingReminderStep;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationDispatcherService
{
    public function __construct(
        protected BookingPendingApprovalStep $pendingApprovalStep,
        protected BookingAutoConfirmedStep $autoConfirmedStep,
        protected BookingApprovedStep $approvedStep,
        protected BookingCancelledStep $cancelledStep,
        protected BookingReminderStep $reminderStep,
        protected BookingCompletedStep $completedStep
    ) {}

    /**
     * Dispatch notifications when a new booking is created
     */
    public function onBookingCreated(Appointment $appointment): void
    {
        try {
            $company = User::find($appointment->user_id);
            if (!$company) return;

            $settings = NotificationSetting::forUser($company->id);
            $requiresApproval = (bool) $settings->require_manual_confirmation;

            // 1. Dispatch Booking Creation Flow (Pending Approval or Auto-Confirmed)
            if ($requiresApproval) {
                $this->pendingApprovalStep->handle($appointment, $settings, $company);
                // ⚠️ Advance reminder will ONLY be scheduled after the appointment is confirmed/approved!
            } else {
                $this->autoConfirmedStep->handle($appointment, $settings, $company);
                // ⏰ Since it was automatically confirmed, schedule the advance reminder now:
                $this->reminderStep->handle($appointment, $settings, $company);
            }
        } catch (Throwable $e) {
            Log::error('[NotificationDispatcher] Error on booking created: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch notification when an appointment is approved / confirmed
     */
    public function onBookingApproved(Appointment $appointment, ?Payment $payment = null): void
    {
        try {
            $company = User::find($appointment->user_id);
            if (!$company) return;

            $settings = NotificationSetting::forUser($company->id);
            $this->approvedStep->handle($appointment, $settings, $company, ['payment' => $payment]);

            // ⏰ Schedule Advance Reminder NOW that the appointment is confirmed / approved!
            $this->reminderStep->handle($appointment, $settings, $company);
        } catch (Throwable $e) {
            Log::error('[NotificationDispatcher] Error on booking approved: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch notification when an appointment is cancelled / rejected
     */
    public function onBookingCancelled(Appointment $appointment, ?string $reason = null): void
    {
        try {
            $company = User::find($appointment->user_id);
            if (!$company) return;

            $settings = NotificationSetting::forUser($company->id);
            $this->cancelledStep->handle($appointment, $settings, $company, ['reason' => $reason]);
        } catch (Throwable $e) {
            Log::error('[NotificationDispatcher] Error on booking cancelled: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch notification when an appointment is completed
     */
    public function onBookingCompleted(Appointment $appointment): void
    {
        try {
            $company = User::find($appointment->user_id);
            if (!$company) return;

            $settings = NotificationSetting::forUser($company->id);
            $this->completedStep->handle($appointment, $settings, $company);
        } catch (Throwable $e) {
            Log::error('[NotificationDispatcher] Error on booking completed: ' . $e->getMessage());
        }
    }
}
