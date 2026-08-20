<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);

            $request->user()->update([
                'password' => Hash::make($validated['password']),
                'must_reset_password' => false,
            ]);

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Senha atualizada com sucesso.');
            }

            return back();
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);

            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível atualizar a senha.');
            }

            throw $e;
        }
    }

    /**
     * Show force password change form.
     */
    public function showForceChangeForm(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->must_reset_password) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Admin/Auth/ForcePasswordChange');
    }

    /**
     * Update force password change.
     */
    public function forceChangePassword(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user || ! $user->must_reset_password) {
                return redirect()->route('admin.dashboard');
            }

            $validated = $request->validate([
                'password' => ['required', Password::defaults(), 'confirmed'],
            ]);

            $user->update([
                'password' => Hash::make($validated['password']),
                'must_reset_password' => false,
            ]);

            if ($request->expectsJson()) {
                return $this->jsonSuccess($request, 'Senha obrigatória redefinida com sucesso.');
            }

            return redirect()->route('admin.dashboard')->with('success', 'Senha atualizada com sucesso!');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->jsonValidationError($request, $e);
            }
            throw $e;
        } catch (Throwable $e) {
            $this->reportThrowable($e);
            if ($request->expectsJson()) {
                return $this->jsonError($request, 'Não foi possível atualizar a senha.');
            }
            throw $e;
        }
    }
}
