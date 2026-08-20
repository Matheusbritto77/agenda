<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
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

    public function getRoleNameAttribute(): string
    {
        return match ($this->role_id) {
            'admin' => 'Administrador',
            'manager' => 'Gerente',
            'receptionist' => 'Recepcionista',
            default => 'Profissional / Especialista',
        };
    }

    public function getRoleBadgeColorAttribute(): string
    {
        return match ($this->role_id) {
            'admin' => 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border-indigo-500/30',
            'manager' => 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30',
            'receptionist' => 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30',
            default => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
        };
    }

    public function getRoleIconAttribute(): string
    {
        return match ($this->role_id) {
            'admin' => 'fa-solid fa-shield-halved',
            'manager' => 'fa-solid fa-user-tie',
            'receptionist' => 'fa-solid fa-headset',
            default => 'fa-solid fa-user-check',
        };
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
