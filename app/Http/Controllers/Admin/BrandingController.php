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
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'delete_logo' => ['nullable', 'boolean'],
        ])->validate();

        $branding = BrandingSetting::firstOrCreate([
            'user_id' => $tenantId,
        ]);

        $updateData = [
            'top_menu_color' => $validated['top_menu_color'] ?? null,
            'background_color' => $validated['background_color'] ?? null,
            'primary_color' => $validated['primary_color'] ?? null,
        ];

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
