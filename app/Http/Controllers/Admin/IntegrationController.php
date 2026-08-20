<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasPermission('integrations.view')) {
            abort(403, 'Você não tem permissão para acessar esta página.');
        }

        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;

        $paymentSetting = PaymentSetting::query()
            ->where('user_id', $tenantId)
            ->where('gateway', 'mercadopago')
            ->first();

        $credentials = $paymentSetting ? $paymentSetting->credentials : [];
        $settings = $paymentSetting ? $paymentSetting->settings : ['pix_expiration_minutes' => 30];

        $paymentConfig = [
            'is_active' => $paymentSetting ? (bool) $paymentSetting->is_active : false,
            'gateway' => 'mercadopago',
            'access_token' => isset($credentials['access_token']) ? $credentials['access_token'] : '',
            'settings' => $settings,
        ];

        return Inertia::render('Admin/Integrations/Index', [
            'paymentConfig' => $paymentConfig,
        ]);
    }

    public function updatePaymentSettings(Request $request)
    {
        $user = $request->user();
        if (! $user->hasPermission('integrations.manage')) {
            abort(403, 'Você não tem permissão para realizar esta ação.');
        }

        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;

        $validated = Validator::make($request->all(), [
            'gateway' => ['required', 'string', 'in:mercadopago'],
            'is_active' => ['required', 'boolean'],
            'access_token' => ['nullable', 'string', 'max:500'],
            'settings' => ['required', 'array'],
            'settings.pix_expiration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ])->validate();

        $paymentSetting = PaymentSetting::firstOrCreate([
            'user_id' => $tenantId,
            'gateway' => $validated['gateway'],
        ]);

        $credentials = $paymentSetting->credentials ?: [];
        if (isset($validated['access_token'])) {
            $credentials['access_token'] = $validated['access_token'];
        }

        $paymentSetting->update([
            'is_active' => $validated['is_active'],
            'credentials' => $credentials,
            'settings' => $validated['settings'],
        ]);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($request, 'Configurações de pagamento atualizadas com sucesso.', [
                'paymentSetting' => $paymentSetting->fresh(),
            ]);
        }

        return redirect()
            ->route('admin.integrations.index')
            ->with('success', 'Configurações de pagamento atualizadas com sucesso.');
    }
}
