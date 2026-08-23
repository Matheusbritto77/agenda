<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyReview extends Model
{
    protected $fillable = [
        'user_id',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }
}
