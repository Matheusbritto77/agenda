<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\RoleCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamMember extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'job_title',
        'role_id',
        'commission_rate',
        'service_commissions',
        'email',
        'phone',
        'avatar_url',
        'subdomain',
        'custom_domain',
        'bio',
        'is_active',
        'services',
        'business_hours',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'services' => 'array',
        'business_hours' => 'array',
        'commission_rate' => 'float',
        'service_commissions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getAvatarUrlAttribute(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return $value;
        }
        if (str_starts_with($value, 'storage/')) {
            return '/' . $value;
        }

        return '/storage/' . ltrim($value, '/');
    }

    public function getRoleNameAttribute(): string
    {
        return RoleCatalog::titleFor($this->role_id);
    }

    public function getRoleBadgeColorAttribute(): string
    {
        return RoleCatalog::badgeColorFor($this->role_id);
    }

    public function getRoleIconAttribute(): string
    {
        return RoleCatalog::iconFor($this->role_id);
    }

    public function publicBookingUrl(string $path = '/'): string
    {
        if ($this->subdomain && $this->user) {
            $scheme = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_SCHEME) ?: 'http';
            $port = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_PORT);
            $baseDomain = strtolower((string) config('app.domain', parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost'));

            $url = "{$scheme}://{$this->subdomain}.{$baseDomain}";

            if ($port !== null && ! in_array($port, [80, 443], true)) {
                $url .= ':' . $port;
            }

            return $url . '/' . ltrim($path, '/');
        }

        if ($this->user) {
            return $this->user->publicBookingUrl('?professional=' . ($this->subdomain ?: $this->id));
        }

        return url('/' . ltrim($path, '/'));
    }
}
