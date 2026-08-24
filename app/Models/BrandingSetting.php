<?php

namespace App\Models;

use App\Support\StorageHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'favicon_url',
        'public_favicon_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return StorageHelper::url($this->logo_path);
    }

    public function getBannerUrlAttribute(): ?string
    {
        $bannerPath = $this->settings['banner_path'] ?? null;

        return StorageHelper::url($bannerPath);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        $faviconPath = $this->settings['favicon_path'] ?? null;

        return StorageHelper::url($faviconPath);
    }

    public function getPublicFaviconUrlAttribute(): ?string
    {
        if (! $this->favicon_url) {
            return null;
        }

        $version = $this->updated_at?->getTimestamp()
            ?? substr(sha1((string) ($this->settings['favicon_path'] ?? 'favicon')), 0, 12);

        return '/company-favicon?v='.$version;
    }
}
