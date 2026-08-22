<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientAuthController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('client')->check()) {
            return redirect()->route('client.dashboard');
        }

        return Inertia::render('Client/Portal/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('client')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos.',
            ]);
        }

        $request->session()->regenerate();
        $client = Auth::guard('client')->user();

        return redirect()->intended(
            $client->must_reset_password
                ? route('client.password.edit')
                : route('client.dashboard')
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }
}
