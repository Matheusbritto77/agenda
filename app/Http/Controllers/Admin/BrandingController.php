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
            'company_profile_description' => ['nullable', 'string', 'max:1200'],
            'company_profile_cta_label' => ['nullable', 'string', 'max:40'],
            'company_profile_show_hours' => ['nullable', 'boolean'],
            'company_profile_show_services' => ['nullable', 'boolean'],
            'company_profile_show_professionals' => ['nullable', 'boolean'],
            'company_profile_show_reviews' => ['nullable', 'boolean'],
            'company_profile_reviews_title' => ['nullable', 'string', 'max:60'],
            'company_profile_reviews_subtitle' => ['nullable', 'string', 'max:120'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_button_enabled' => ['nullable', 'boolean'],
            'instagram_handle' => ['nullable', 'string', 'max:100'],
            'company_address' => ['nullable', 'string', 'max:300'],
            'border_radius' => ['nullable', 'string', 'max:20'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            // Booking flow step customizations
            'booking_step_professional_title' => ['nullable', 'string', 'max:100'],
            'booking_step_professional_subtitle' => ['nullable', 'string', 'max:255'],
            'booking_step_professional_allow_any' => ['nullable', 'boolean'],
            'booking_step_service_title' => ['nullable', 'string', 'max:100'],
            'booking_step_service_subtitle' => ['nullable', 'string', 'max:255'],
            'booking_step_service_search_enabled' => ['nullable', 'boolean'],
            'booking_step_datetime_title' => ['nullable', 'string', 'max:100'],
            'booking_step_datetime_subtitle' => ['nullable', 'string', 'max:255'],
            'booking_step_confirm_title' => ['nullable', 'string', 'max:100'],
            'booking_step_confirm_button_label' => ['nullable', 'string', 'max:60'],
            'booking_step_confirm_show_notes' => ['nullable', 'boolean'],
            'booking_step_success_title' => ['nullable', 'string', 'max:100'],
            'booking_step_success_message' => ['nullable', 'string', 'max:500'],
            'booking_step_success_whatsapp_label' => ['nullable', 'string', 'max:60'],
            // Files (allowed up to 10MB)
            'logo_file' => ['nullable', 'image', 'max:10240'],
            'delete_logo' => ['nullable', 'boolean'],
            'banner_file' => ['nullable', 'image', 'max:10240'],
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
            'company_profile_description' => $validated['company_profile_description'] ?? ($currentSettings['company_profile_description'] ?? null),
            'company_profile_cta_label' => $validated['company_profile_cta_label'] ?? ($currentSettings['company_profile_cta_label'] ?? 'Agendar agora'),
            'company_profile_show_hours' => isset($validated['company_profile_show_hours']) ? (bool) $validated['company_profile_show_hours'] : ($currentSettings['company_profile_show_hours'] ?? true),
            'company_profile_show_services' => isset($validated['company_profile_show_services']) ? (bool) $validated['company_profile_show_services'] : ($currentSettings['company_profile_show_services'] ?? true),
            'company_profile_show_professionals' => isset($validated['company_profile_show_professionals']) ? (bool) $validated['company_profile_show_professionals'] : ($currentSettings['company_profile_show_professionals'] ?? true),
            'company_profile_show_reviews' => isset($validated['company_profile_show_reviews']) ? (bool) $validated['company_profile_show_reviews'] : ($currentSettings['company_profile_show_reviews'] ?? true),
            'company_profile_reviews_title' => $validated['company_profile_reviews_title'] ?? ($currentSettings['company_profile_reviews_title'] ?? 'O que os clientes dizem'),
            'company_profile_reviews_subtitle' => $validated['company_profile_reviews_subtitle'] ?? ($currentSettings['company_profile_reviews_subtitle'] ?? 'Avaliações de atendimentos concluídos nesta empresa.'),
            'whatsapp_number' => $validated['whatsapp_number'] ?? ($currentSettings['whatsapp_number'] ?? null),
            'whatsapp_button_enabled' => isset($validated['whatsapp_button_enabled']) ? (bool) $validated['whatsapp_button_enabled'] : ($currentSettings['whatsapp_button_enabled'] ?? false),
            'instagram_handle' => $validated['instagram_handle'] ?? ($currentSettings['instagram_handle'] ?? null),
            'company_address' => $validated['company_address'] ?? ($currentSettings['company_address'] ?? null),
            'border_radius' => $validated['border_radius'] ?? ($currentSettings['border_radius'] ?? 'rounded-2xl'),
            'footer_text' => $validated['footer_text'] ?? ($currentSettings['footer_text'] ?? null),
            // Booking step settings
            'booking_step_professional_title' => $validated['booking_step_professional_title'] ?? ($currentSettings['booking_step_professional_title'] ?? 'Escolha o Profissional'),
            'booking_step_professional_subtitle' => $validated['booking_step_professional_subtitle'] ?? ($currentSettings['booking_step_professional_subtitle'] ?? 'Selecione quem irá lhe atender'),
            'booking_step_professional_allow_any' => isset($validated['booking_step_professional_allow_any']) ? (bool) $validated['booking_step_professional_allow_any'] : ($currentSettings['booking_step_professional_allow_any'] ?? true),
            'booking_step_service_title' => $validated['booking_step_service_title'] ?? ($currentSettings['booking_step_service_title'] ?? 'Escolha o Serviço'),
            'booking_step_service_subtitle' => $validated['booking_step_service_subtitle'] ?? ($currentSettings['booking_step_service_subtitle'] ?? 'Selecione os procedimentos desejados'),
            'booking_step_service_search_enabled' => isset($validated['booking_step_service_search_enabled']) ? (bool) $validated['booking_step_service_search_enabled'] : ($currentSettings['booking_step_service_search_enabled'] ?? true),
            'booking_step_datetime_title' => $validated['booking_step_datetime_title'] ?? ($currentSettings['booking_step_datetime_title'] ?? 'Escolha Data e Horário'),
            'booking_step_datetime_subtitle' => $validated['booking_step_datetime_subtitle'] ?? ($currentSettings['booking_step_datetime_subtitle'] ?? 'Selecione o melhor dia e horário disponível'),
            'booking_step_confirm_title' => $validated['booking_step_confirm_title'] ?? ($currentSettings['booking_step_confirm_title'] ?? 'Dados & Confirmação'),
            'booking_step_confirm_button_label' => $validated['booking_step_confirm_button_label'] ?? ($currentSettings['booking_step_confirm_button_label'] ?? 'Confirmar Agendamento'),
            'booking_step_confirm_show_notes' => isset($validated['booking_step_confirm_show_notes']) ? (bool) $validated['booking_step_confirm_show_notes'] : ($currentSettings['booking_step_confirm_show_notes'] ?? true),
            'booking_step_success_title' => $validated['booking_step_success_title'] ?? ($currentSettings['booking_step_success_title'] ?? 'Agendamento Confirmado!'),
            'booking_step_success_message' => $validated['booking_step_success_message'] ?? ($currentSettings['booking_step_success_message'] ?? 'Um lembrete com os detalhes foi enviado para o seu WhatsApp.'),
            'booking_step_success_whatsapp_label' => $validated['booking_step_success_whatsapp_label'] ?? ($currentSettings['booking_step_success_whatsapp_label'] ?? 'Conversar no WhatsApp'),
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
            \App\Support\StorageHelper::delete($branding->logo_path);
            $updateData['logo_path'] = $request->file('logo_file')->store('branding/logos', 'public');
        } elseif ($request->boolean('delete_logo') === true) {
            \App\Support\StorageHelper::delete($branding->logo_path);
            $updateData['logo_path'] = null;
        }

        // Handle Banner file
        if ($request->hasFile('banner_file')) {
            \App\Support\StorageHelper::delete($currentSettings['banner_path'] ?? null);
            $settingsData['banner_path'] = $request->file('banner_file')->store('branding/banners', 'public');
        } elseif ($request->boolean('delete_banner') === true) {
            \App\Support\StorageHelper::delete($currentSettings['banner_path'] ?? null);
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
