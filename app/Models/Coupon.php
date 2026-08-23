<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_spend',
        'max_uses',
        'uses_count',
        'client_account_id',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'expires_at' => 'date',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'formatted_discount',
        'is_valid',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function getFormattedDiscountAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return (float) $this->discount_value . '% OFF';
        }

        return 'R$ ' . number_format((float) $this->discount_value, 2, ',', '.') . ' OFF';
    }

    public function getIsValidAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && Carbon::parse($this->expires_at)->endOfDay()->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $servicePrice): float
    {
        if (! $this->is_valid) {
            return 0;
        }

        if ($this->min_spend !== null && $servicePrice < (float) $this->min_spend) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            $discount = ($servicePrice * (float) $this->discount_value) / 100;
        } else {
            $discount = (float) $this->discount_value;
        }

        return min($servicePrice, round($discount, 2));
    }
}
