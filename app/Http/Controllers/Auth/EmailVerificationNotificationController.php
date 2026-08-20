<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                if ($request->expectsJson()) {
                    return $this->jsonSuccess($request, 'E-mail já verificado.');
                }

                return redirect()->intended(route('dashboard', absolute: false));
            }

            $request->user()->sendEmailVerificationNotification();

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Link de verificação enviado com sucesso.');
            }

            return back()->with('status', 'verification-link-sent');
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível enviar a verificação de e-mail.');
            }

            throw $e;
        }
    }
}
