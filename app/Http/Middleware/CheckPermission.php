<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Você não tem permissão para acessar esta página.');
        }

        $hasAny = false;
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasAny = true;
                break;
            }
        }

        if (! $hasAny) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Você não tem permissão para realizar esta ação.'], 403);
            }
            abort(403, 'Você não tem permissão para acessar esta página.');
        }

        return $next($request);
    }
}
