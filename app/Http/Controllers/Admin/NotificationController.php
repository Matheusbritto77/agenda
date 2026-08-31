<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user() ?? auth()->user();
        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;

        $settings = NotificationSetting::forUser($tenantId);

        return Inertia::render('Admin/Notifications/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;

        $validated = $request->validate([
            'email_enabled' => ['required', 'boolean'],
            'whatsapp_enabled' => ['required', 'boolean'],
            'require_manual_confirmation' => ['required', 'boolean'],
            'reminder_enabled' => ['required', 'boolean'],
            'reminder_time_value' => ['required', 'integer', 'min:1', 'max:168'],
            'reminder_time_unit' => ['required', 'string', 'in:minutes,hours,days'],
            'notify_client_on_booking' => ['required', 'boolean'],
            'notify_staff_on_booking' => ['required', 'boolean'],
            'notify_client_on_confirmation' => ['required', 'boolean'],
            'notify_client_on_cancellation' => ['required', 'boolean'],
        ]);

        $settings = NotificationSetting::forUser($tenantId);
        $settings->update($validated);

        return redirect()->back()->with('success', 'Configurações de notificações salvas com sucesso!');
    }
}
