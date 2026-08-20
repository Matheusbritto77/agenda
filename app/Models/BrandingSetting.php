<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BrandingSetting extends Model
{
    protected $fillable = [
        'user_id',
        'logo_path',
        'top_menu_color',
        'background_color',
        'primary_color',
        'secondary_color',
        'button_color',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected $appends = [
        'logo_url',
        'banner_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        if (filter_var($this->logo_path, FILTER_VALIDATE_URL)) {
            return $this->logo_path;
        }

        return '/storage/' . ltrim($this->logo_path, '/');
    }

    public function getBannerUrlAttribute(): ?string
    {
        $bannerPath = $this->settings['banner_path'] ?? null;
        if (empty($bannerPath)) {
            return null;
        }

        if (filter_var($bannerPath, FILTER_VALIDATE_URL)) {
            return $bannerPath;
        }

        return '/storage/' . ltrim($bannerPath, '/');
    }
}
