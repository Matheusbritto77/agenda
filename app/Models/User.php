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

#[Fillable(['name', 'email', 'avatar_url', 'password', 'parent_id', 'role_title', 'must_reset_password', 'subdomain', 'custom_domain', 'active_domain_type', 'role_permissions', 'custom_roles'])]
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
            'custom_roles' => 'array',
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
        return \App\Support\StorageHelper::url($value);
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

        return in_array($permission, $this->rolePermissionsFor($role), true);
    }

    /**
     * @return array<string, array{name: string, description: string, badge_color: string, icon: string}>
     */
    public function roleDefinitions(): array
    {
        return \App\Support\RoleCatalog::all($this->tenantOwner()?->custom_roles ?? $this->custom_roles ?? []);
    }

    /**
     * @return array<int, string>
     */
    public function roleIds(): array
    {
        return \App\Support\RoleCatalog::ids($this->tenantOwner()?->custom_roles ?? $this->custom_roles ?? []);
    }

    /**
     * @return array<int, string>
     */
    public function rolePermissionsFor(string $roleId): array
    {
        $owner = $this->tenantOwner();
        $tenantPermissions = $owner?->role_permissions ?? $this->role_permissions ?? [];
        $defaultRolePermissions = self::defaultRolePermissions();

        return isset($tenantPermissions[$roleId])
            ? (array) $tenantPermissions[$roleId]
            : ($defaultRolePermissions[$roleId] ?? []);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function defaultRolePermissions(): array
    {
        return [
            'admin' => [
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.view_all',
                'clients.view', 'clients.edit', 'clients.reviews', 'clients.view_all',
                'services.view', 'services.create', 'services.edit', 'services.delete', 'services.prices',
                'schedules.view', 'schedules.manage', 'schedules.breaks', 'schedules.blocks',
                'team.view', 'team.create', 'team.edit', 'team.delete',
                'reports.view', 'reports.view_all', 'reports.revenue', 'reports.revenue_all', 'reports.export',
                'integrations.view', 'integrations.manage',
                'branding.view', 'branding.manage',
                'notifications.view', 'notifications.manage',
                'settings.domain', 'settings.roles',
            ],
            'manager' => [
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.view_all',
                'clients.view', 'clients.edit', 'clients.reviews', 'clients.view_all',
                'services.view', 'services.create', 'services.edit', 'services.prices',
                'schedules.view', 'schedules.manage', 'schedules.breaks', 'schedules.blocks',
                'team.view', 'team.create', 'team.edit',
                'reports.view', 'reports.view_all', 'reports.revenue', 'reports.export',
                'integrations.view', 'integrations.manage',
                'branding.view', 'branding.manage',
                'notifications.view', 'notifications.manage',
            ],
            'professional' => [
                'appointments.view', 'appointments.create', 'appointments.edit',
                'clients.view', 'clients.edit', 'clients.reviews',
                'services.view',
                'schedules.view', 'schedules.breaks',
                'team.view',
                'reports.view', 'reports.revenue',
            ],
            'receptionist' => [
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.view_all',
                'clients.view', 'clients.edit', 'clients.view_all',
                'services.view',
                'schedules.view', 'schedules.manage', 'schedules.blocks',
                'team.view',
                'notifications.view',
            ],
        ];
    }

    public function tenantOwner(): ?self
    {
        if ($this->parent_id === null) {
            return $this;
        }

        return self::query()->find($this->parent_id);
    }

    public function brandingSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\BrandingSetting::class);
    }
}
