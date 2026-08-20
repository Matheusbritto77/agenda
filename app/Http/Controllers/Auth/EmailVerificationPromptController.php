<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response|JsonResponse
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                if ($request->expectsJson()) {
                    return $this->jsonSuccess($request, 'E-mail já verificado.', [
                        'verified' => true,
                    ]);
                }

                return redirect()->intended(route('dashboard', absolute: false));
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'E-mail pendente de verificação.',
                    'verified' => false,
                    'status' => session('status'),
                ]);
            }

            return Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível carregar a verificação de e-mail.');
            }

            throw $e;
        }
    }
}
