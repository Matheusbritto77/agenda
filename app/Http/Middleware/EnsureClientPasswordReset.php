<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientPasswordReset
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = Auth::guard('client')->user();

        if (! $client?->must_reset_password || $request->routeIs('client.password.*', 'client.logout')) {
            return $next($request);
        }

        return redirect()
            ->route('client.password.edit')
            ->with('warning', 'Escolha uma nova senha antes de continuar.');
    }
}
