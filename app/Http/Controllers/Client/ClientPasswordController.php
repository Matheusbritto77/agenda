<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ClientPasswordController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Client/Portal/Password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:client'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $client = Auth::guard('client')->user();
        $client->update([
            'password' => $validated['password'],
            'must_reset_password' => false,
        ]);

        $request->session()->regenerate();

        return redirect()
            ->route('client.dashboard')
            ->with('success', 'Senha atualizada com sucesso.');
    }
}
