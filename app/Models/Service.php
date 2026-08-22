<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use BelongsToTenant, HasFactory;

    protected $appends = [
        'formatted_price',
        'image_url',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'slot_duration_minutes',
        'is_active',
        'image_path',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'slot_duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'image_path' => 'string',
    ];

    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format((float)$this->price, 2, ',', '.');
    }

    public function getImageUrlAttribute(): ?string
    {
        return \App\Support\StorageHelper::url($this->image_path);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where($query->getModel()->getTable() . '.user_id', $tenantId);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
