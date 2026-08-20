<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class BrandingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasPermission('branding.view')) {
            abort(403, 'Você não tem permissão para acessar esta página.');
        }

        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;

        $branding = BrandingSetting::query()
            ->where('user_id', $tenantId)
            ->first();

        return Inertia::render('Admin/Branding/Index', [
            'branding' => $branding,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if (! $user->hasPermission('branding.manage')) {
            abort(403, 'Você não tem permissão para realizar esta ação.');
        }

        $tenantId = $user->parent_id ? (int) $user->parent_id : (int) $user->id;

        $validated = Validator::make($request->all(), [
            'top_menu_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'background_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'primary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'button_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'card_bg_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'text_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'button_text_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_button_enabled' => ['nullable', 'boolean'],
            'instagram_handle' => ['nullable', 'string', 'max:100'],
            'border_radius' => ['nullable', 'string', 'max:20'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'delete_logo' => ['nullable', 'boolean'],
            'banner_file' => ['nullable', 'image', 'max:4096'],
            'delete_banner' => ['nullable', 'boolean'],
        ])->validate();

        $branding = BrandingSetting::firstOrCreate([
            'user_id' => $tenantId,
        ]);

        $currentSettings = $branding->settings ?? [];

        $settingsData = array_merge($currentSettings, [
            'card_bg_color' => $validated['card_bg_color'] ?? ($currentSettings['card_bg_color'] ?? null),
            'text_color' => $validated['text_color'] ?? ($currentSettings['text_color'] ?? null),
            'button_text_color' => $validated['button_text_color'] ?? ($currentSettings['button_text_color'] ?? null),
            'business_name' => $validated['business_name'] ?? ($currentSettings['business_name'] ?? null),
            'tagline' => $validated['tagline'] ?? ($currentSettings['tagline'] ?? null),
            'whatsapp_number' => $validated['whatsapp_number'] ?? ($currentSettings['whatsapp_number'] ?? null),
            'whatsapp_button_enabled' => isset($validated['whatsapp_button_enabled']) ? (bool) $validated['whatsapp_button_enabled'] : ($currentSettings['whatsapp_button_enabled'] ?? false),
            'instagram_handle' => $validated['instagram_handle'] ?? ($currentSettings['instagram_handle'] ?? null),
            'border_radius' => $validated['border_radius'] ?? ($currentSettings['border_radius'] ?? 'rounded-2xl'),
            'footer_text' => $validated['footer_text'] ?? ($currentSettings['footer_text'] ?? null),
        ]);

        $updateData = [
            'top_menu_color' => $validated['top_menu_color'] ?? null,
            'background_color' => $validated['background_color'] ?? null,
            'primary_color' => $validated['primary_color'] ?? null,
            'secondary_color' => $validated['secondary_color'] ?? null,
            'button_color' => $validated['button_color'] ?? null,
        ];

        // Handle Logo file
        if ($request->hasFile('logo_file')) {
            if ($branding->logo_path && ! filter_var($branding->logo_path, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($branding->logo_path);
            }
            $updateData['logo_path'] = $request->file('logo_file')->store('branding', 'public');
        } elseif ($request->boolean('delete_logo') === true) {
            if ($branding->logo_path && ! filter_var($branding->logo_path, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($branding->logo_path);
            }
            $updateData['logo_path'] = null;
        }

        // Handle Banner file
        if ($request->hasFile('banner_file')) {
            $oldBanner = $currentSettings['banner_path'] ?? null;
            if ($oldBanner && ! filter_var($oldBanner, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($oldBanner);
            }
            $settingsData['banner_path'] = $request->file('banner_file')->store('branding/banners', 'public');
        } elseif ($request->boolean('delete_banner') === true) {
            $oldBanner = $currentSettings['banner_path'] ?? null;
            if ($oldBanner && ! filter_var($oldBanner, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($oldBanner);
            }
            $settingsData['banner_path'] = null;
        }

        $updateData['settings'] = $settingsData;

        $branding->update($updateData);

        if ($request->expectsJson()) {
            return $this->jsonSuccess($request, 'Identidade visual atualizada com sucesso.', [
                'branding' => $branding->fresh(),
            ]);
        }

        return redirect()
            ->route('admin.branding.index')
            ->with('success', 'Identidade visual atualizada com sucesso.');
    }
}
