<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_enabled',
        'whatsapp_enabled',
        'require_manual_confirmation',
        'reminder_enabled',
        'reminder_time_value',
        'reminder_time_unit',
        'notify_client_on_booking',
        'notify_staff_on_booking',
        'notify_client_on_confirmation',
        'notify_client_on_cancellation',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'require_manual_confirmation' => 'boolean',
        'reminder_enabled' => 'boolean',
        'reminder_time_value' => 'integer',
        'notify_client_on_booking' => 'boolean',
        'notify_staff_on_booking' => 'boolean',
        'notify_client_on_confirmation' => 'boolean',
        'notify_client_on_cancellation' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create default notification settings for a tenant
     */
    public static function forUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'email_enabled' => true,
                'whatsapp_enabled' => true,
                'require_manual_confirmation' => false,
                'reminder_enabled' => true,
                'reminder_time_value' => 2,
                'reminder_time_unit' => 'hours',
                'notify_client_on_booking' => true,
                'notify_staff_on_booking' => true,
                'notify_client_on_confirmation' => true,
                'notify_client_on_cancellation' => true,
            ]
        );
    }
}
