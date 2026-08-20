<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'avatar_url', 'password', 'parent_id', 'role_title', 'must_reset_password', 'subdomain', 'custom_domain', 'active_domain_type', 'role_permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'parent_id' => 'integer',
            'must_reset_password' => 'boolean',
            'role_permissions' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function subUsers(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function publicBookingUrl(string $path = '/'): string
    {
        $scheme = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_SCHEME) ?: 'http';
        $port = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_PORT);
        $baseDomain = strtolower((string) config('app.domain', parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost'));

        $host = $this->active_domain_type === 'custom' && $this->custom_domain
            ? strtolower((string) $this->custom_domain)
            : ($this->subdomain ? strtolower((string) $this->subdomain) . '.' . $baseDomain : $baseDomain);

        $url = "{$scheme}://{$host}";

        if ($port !== null && ! in_array($port, [80, 443], true)) {
            $url .= ':' . $port;
        }

        return $url . '/' . ltrim($path, '/');
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

    public function hasPermission(string $permission): bool
    {
        // Owner (parent_id = null) has all permissions
        if ($this->parent_id === null) {
            return true;
        }

        // Get sub-user's role and permissions
        $member = \App\Models\TeamMember::query()
            ->where('user_id', $this->parent_id)
            ->where('email', $this->email)
            ->first();

        $role = $member ? $member->role_id : 'professional';

        // Admin role has all permissions
        if ($role === 'admin') {
            return true;
        }

        // Fetch customized permissions
        $owner = self::find($this->parent_id);
        $tenantPermissions = $owner ? ($owner->role_permissions ?? []) : [];

        $defaultRolePermissions = [
            'admin' => [
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.view_all',
                'services.view', 'services.create', 'services.edit', 'services.delete', 'services.prices',
                'schedules.view', 'schedules.manage', 'schedules.breaks', 'schedules.blocks',
                'team.view', 'team.create', 'team.edit', 'team.delete',
                'reports.view', 'reports.revenue', 'reports.export',
                'integrations.view', 'integrations.manage',
                'branding.view', 'branding.manage',
                'settings.domain', 'settings.roles',
            ],
            'manager' => [
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.view_all',
                'services.view', 'services.create', 'services.edit', 'services.prices',
                'schedules.view', 'schedules.manage', 'schedules.breaks', 'schedules.blocks',
                'team.view', 'team.create', 'team.edit',
                'reports.view',
                'integrations.view', 'integrations.manage',
                'branding.view', 'branding.manage',
            ],
            'professional' => [
                'appointments.view', 'appointments.create', 'appointments.edit',
                'services.view',
                'schedules.view', 'schedules.breaks',
                'team.view',
            ],
            'receptionist' => [
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.view_all',
                'services.view',
                'schedules.view', 'schedules.manage', 'schedules.blocks',
                'team.view',
            ],
        ];

        $userPermissions = isset($tenantPermissions[$role])
            ? (array) $tenantPermissions[$role]
            : ($defaultRolePermissions[$role] ?? []);

        return in_array($permission, $userPermissions, true);
    }
}
