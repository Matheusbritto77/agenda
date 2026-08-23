<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureClientPasswordReset;
use App\Http\Middleware\EnsurePasswordReset;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('cliente*')
                ? route('client.login')
                : route('login')
        );
        $middleware->redirectUsersTo(
            fn (Request $request): string => $request->is('cliente*')
                ? route('client.dashboard')
                : route('admin.dashboard')
        );

        $middleware->alias([
            'must.reset.password' => EnsurePasswordReset::class,
            'ensure.password.changed' => EnsurePasswordReset::class,
            'permission' => CheckPermission::class,
            'client.password.reset' => EnsureClientPasswordReset::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/mercadopago',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, Request $request) {
            if ($request->hasHeader('X-Inertia') || $request->expectsJson() || $request->ajax()) {
                return back()->withErrors([
                    'logo_file' => 'O arquivo enviado é maior do que o permitido pelo servidor. Escolha um arquivo de até 10 MB.',
                    'banner_file' => 'O arquivo enviado é maior do que o permitido pelo servidor. Escolha um arquivo de até 10 MB.',
                    'image_file' => 'O arquivo enviado é maior do que o permitido pelo servidor. Escolha um arquivo de até 10 MB.',
                    'avatar' => 'O arquivo enviado é maior do que o permitido pelo servidor. Escolha um arquivo de até 10 MB.',
                    'file' => 'O arquivo enviado excede o limite máximo permitido pelo servidor.',
                ])->with('warning', 'O arquivo selecionado excede o limite do servidor. O limite máximo é 10 MB.');
            }

            return back()->with('warning', 'O arquivo selecionado excede o limite do servidor. O limite máximo é 10 MB.');
        });
    })->create();
