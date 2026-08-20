<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordReset
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_reset_password) {
            return $next($request);
        }

        if ($this->allowsPasswordResetRoutes($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Você precisa redefinir sua senha antes de continuar.',
                'must_reset_password' => true,
                'redirect_to' => route('admin.force-password-change.show'),
            ], 409);
        }

        return redirect()
            ->route('admin.force-password-change.show')
            ->with('warning', 'Você precisa redefinir sua senha antes de continuar.');
    }

    private function allowsPasswordResetRoutes(Request $request): bool
    {
        return $request->routeIs(
            'admin.force-password-change.show',
            'admin.force-password-change.submit',
            'logout',
            'verification.notice',
            'verification.send',
            'password.confirm'
        );
    }
}
