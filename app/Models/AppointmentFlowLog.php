<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentFlowLog extends Model
{
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'appointment_id',
        'event_type',
        'level',
        'channel',
        'title',
        'description',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Quick helper to record an appointment flow event
     */
    public static function record(
        int $userId,
        string $eventType,
        string $title,
        ?string $description = null,
        ?int $appointmentId = null,
        string $channel = 'system',
        string $level = 'info',
        ?array $metadata = null
    ): self {
        try {
            return static::create([
                'user_id' => $userId,
                'appointment_id' => $appointmentId,
                'event_type' => $eventType,
                'level' => $level,
                'channel' => $channel,
                'title' => $title,
                'description' => $description,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to write AppointmentFlowLog: ' . $e->getMessage());
            return new static();
        }
    }
}
