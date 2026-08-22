<?php

namespace App\Http\Middleware;

use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = Auth::guard('web')->user();
        $canManageRoles = false;
        $userRole = 'admin';
        $userPermissions = [];

        if ($user) {
            if ($user->parent_id) {
                $member = TeamMember::query()
                    ->where('user_id', $user->parent_id)
                    ->where('email', $user->email)
                    ->first();

                $userRole = $member ? $member->role_id : 'professional';
                $canManageRoles = ($userRole === 'admin');
            } else {
                $canManageRoles = true;
            }

            // Load stored permissions config using the User model single source of truth
            $allPermissionsList = [
                'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.cancel', 'appointments.view_all',
                'services.view', 'services.create', 'services.edit', 'services.delete', 'services.prices',
                'schedules.view', 'schedules.manage', 'schedules.breaks', 'schedules.blocks',
                'team.view', 'team.create', 'team.edit', 'team.delete',
                'reports.view', 'reports.revenue', 'reports.export',
                'integrations.view', 'integrations.manage',
                'branding.view', 'branding.manage',
                'settings.domain', 'settings.roles',
            ];
            foreach ($allPermissionsList as $perm) {
                if ($user->hasPermission($perm)) {
                    $userPermissions[] = $perm;
                }
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'canManageRoles' => $canManageRoles,
                'role' => $userRole,
                'permissions' => $userPermissions,
            ],
            'clientAuth' => [
                'user' => Auth::guard('client')->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'publicBookingUrl' => $user ? $user->publicBookingUrl() : null,
        ];
    }
}
