<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Appointment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'team_member_id',
        'client_name',
        'client_email',
        'client_phone',
        'appointment_date',
        'appointment_time',
        'status',
        'payment_status',
        'payment_id',
        'notes',
    ];

    protected $casts = [
        'service_id' => 'integer',
        'team_member_id' => 'integer',
        'appointment_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'customer_name',
        'customer_email',
        'customer_phone',
        'start_time',
        'end_time',
    ];

    public function getCustomerNameAttribute(): string
    {
        return (string) $this->client_name;
    }

    public function getCustomerEmailAttribute(): string
    {
        return (string) $this->client_email;
    }

    public function getCustomerPhoneAttribute(): string
    {
        return (string) $this->client_phone;
    }

    public function getStartTimeAttribute(): string
    {
        return (string) $this->appointment_time;
    }

    public function getEndTimeAttribute(): string
    {
        $start = Carbon::parse($this->appointment_date->format('Y-m-d') . ' ' . $this->appointment_time);
        $duration = $this->service?->duration_minutes ?? 0;

        return $start->copy()->addMinutes($duration)->format('H:i:s');
    }

    public function getAppointmentDatetimeAttribute(): Carbon
    {
        return Carbon::parse($this->appointment_date->format('Y-m-d') . ' ' . $this->appointment_time);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where($query->getModel()->getTable() . '.user_id', $tenantId);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
