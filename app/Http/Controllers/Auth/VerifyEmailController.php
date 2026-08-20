<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Throwable;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse|JsonResponse
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                if ($request->expectsJson()) {
                    return $this->jsonSuccess($request, 'E-mail já verificado.', [
                        'verified' => true,
                    ]);
                }

                return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
            }

            if ($request->user()->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'E-mail verificado com sucesso.', [
                    'verified' => true,
                ]);
            }

            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível verificar o e-mail.');
            }

            throw $e;
        }
    }
}
