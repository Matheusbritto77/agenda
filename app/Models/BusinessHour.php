<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessHour extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'day_of_week',
        'opens_at',
        'closes_at',
        'label',
        'slot_duration_minutes',
        'break_opens_at',
        'break_closes_at',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'opens_at' => 'string',
        'closes_at' => 'string',
        'slot_duration_minutes' => 'integer',
        'break_opens_at' => 'string',
        'break_closes_at' => 'string',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDayOfWeek(Builder $query, int $dayOfWeek): Builder
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where($query->getModel()->getTable() . '.user_id', $tenantId);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
