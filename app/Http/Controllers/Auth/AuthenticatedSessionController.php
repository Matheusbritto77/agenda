<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => \Illuminate\Support\Facades\Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $request->authenticate();

            $request->session()->regenerate();

            $user = $request->user();

            if ($user?->must_reset_password) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Autenticação realizada com sucesso.',
                        'user' => $user,
                        'must_reset_password' => true,
                        'redirect_to' => route('profile.edit'),
                    ]);
                }

                return redirect()
                    ->away(route('profile.edit'))
                    ->with('warning', 'Você precisa redefinir sua senha antes de continuar.');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Autenticação realizada com sucesso.',
                    'user' => $user,
                ]);
            }

            return redirect()->away(route('admin.dashboard'));
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível autenticar.');
            }

            throw $e;
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        try {
            Auth::guard('web')->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Sessão encerrada com sucesso.');
            }

            return redirect()->away('/');
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível encerrar a sessão.');
            }

            throw $e;
        }
    }
}
