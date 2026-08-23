<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentReview extends Model
{
    protected $fillable = [
        'appointment_id',
        'client_account_id',
        'rating',
        'comment',
        'is_public',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_public' => 'boolean',
    ];

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('is_public', false);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }
}
