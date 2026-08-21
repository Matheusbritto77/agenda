<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BrandingIdentityTab from './Components/BrandingIdentityTab.vue';
import BrandingColorsTab from './Components/BrandingColorsTab.vue';
import BrandingContactTab from './Components/BrandingContactTab.vue';
import BrandingCompanyProfileTab from './Components/BrandingCompanyProfileTab.vue';
import BrandingLivePreview from './Components/BrandingLivePreview.vue';

const props = defineProps({
    branding: {
        type: Object,
        default: null,
    },
});

const defaultColors = {
    top_menu_color: '#ffffff',
    background_color: '#f8fafc',
    primary_color: '#6366f1',
    secondary_color: '#4f46e5',
    card_bg_color: '#ffffff',
    text_color: '#0f172a',
    button_text_color: '#ffffff',
};

const activeTab = ref('identity');

const form = useForm({
    top_menu_color: props.branding?.top_menu_color || defaultColors.top_menu_color,
    background_color: props.branding?.background_color || defaultColors.background_color,
    primary_color: props.branding?.primary_color || defaultColors.primary_color,
    secondary_color: props.branding?.secondary_color || defaultColors.secondary_color,
    card_bg_color: props.branding?.settings?.card_bg_color || defaultColors.card_bg_color,
    text_color: props.branding?.settings?.text_color || defaultColors.text_color,
    button_text_color: props.branding?.settings?.button_text_color || defaultColors.button_text_color,
    business_name: props.branding?.settings?.business_name || '',
    tagline: props.branding?.settings?.tagline || '',
    company_profile_description: props.branding?.settings?.company_profile_description || '',
    company_profile_cta_label: props.branding?.settings?.company_profile_cta_label || 'Agendar agora',
    company_profile_show_hours: props.branding?.settings?.company_profile_show_hours ?? true,
    company_profile_show_services: props.branding?.settings?.company_profile_show_services ?? true,
    company_profile_show_professionals: props.branding?.settings?.company_profile_show_professionals ?? true,
    whatsapp_number: props.branding?.settings?.whatsapp_number || '',
    whatsapp_button_enabled: props.branding?.settings?.whatsapp_button_enabled ?? true,
    instagram_handle: props.branding?.settings?.instagram_handle || '',
    border_radius: props.branding?.settings?.border_radius || 'rounded-2xl',
    footer_text: props.branding?.settings?.footer_text || '',
    logo_file: null,
    delete_logo: false,
    banner_file: null,
    delete_banner: false,
});

const saveSuccess = ref(false);
const logoPreview = ref(props.branding?.logo_url || null);
const bannerPreview = ref(props.branding?.banner_url || null);

const handleLogoChange = (file) => {
    if (file) {
        form.logo_file = file;
        form.delete_logo = false;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const handleRemoveLogo = () => {
    form.logo_file = null;
    logoPreview.value = null;
    form.delete_logo = true;
};

const handleBannerChange = (file) => {
    if (file) {
        form.banner_file = file;
        form.delete_banner = false;
        const reader = new FileReader();
        reader.onload = (e) => {
            bannerPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const handleRemoveBanner = () => {
    form.banner_file = null;
    bannerPreview.value = null;
    form.delete_banner = true;
};

const resetToDefault = () => {
    form.top_menu_color = defaultColors.top_menu_color;
    form.background_color = defaultColors.background_color;
    form.primary_color = defaultColors.primary_color;
    form.secondary_color = defaultColors.secondary_color;
    form.card_bg_color = defaultColors.card_bg_color;
    form.text_color = defaultColors.text_color;
    form.button_text_color = defaultColors.button_text_color;
    form.border_radius = 'rounded-2xl';
    form.company_profile_cta_label = 'Agendar agora';
    form.company_profile_show_hours = true;
    form.company_profile_show_services = true;
    form.company_profile_show_professionals = true;
};

const submit = () => {
    form.post(route('admin.branding.update'), {
        preserveScroll: true,
        onSuccess: () => {
            saveSuccess.value = true;
            setTimeout(() => {
                saveSuccess.value = false;
            }, 3500);
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Identidade Visual & Personalização - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Identidade Visual & Cores</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Personalize logotipo, banner, cores, canais de contato e layout da sua página pública</p>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <div v-if="saveSuccess" class="p-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 text-xs sm:text-sm font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-3 shadow-sm">
                <i class="fa-solid fa-circle-check text-base text-emerald-500"></i>
                <div>
                    <p class="font-bold">Personalização salva com sucesso!</p>
                    <p class="text-xs opacity-80">As alterações já foram sincronizadas com sua página pública.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-6 xl:col-span-7 space-y-6">
                    <div class="card p-5 sm:p-7 space-y-6 shadow-sm">
                        <!-- Navigation Tabs Header -->
                        <div class="flex items-center gap-2 border-b pb-4 flex-wrap" style="border-color: var(--border);">
                            <button
                                type="button"
                                @click="activeTab = 'identity'"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                                    activeTab === 'identity'
                                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <i class="fa-solid fa-store text-xs"></i>
                                <span>1. Marca & Mídia</span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'colors'"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                                    activeTab === 'colors'
                                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <i class="fa-solid fa-palette text-xs"></i>
                                <span>2. Cores & Estilo</span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'contact'"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                                    activeTab === 'contact'
                                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <i class="fa-solid fa-comments text-xs"></i>
                                <span>3. Contato & Rodapé</span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'company-profile'"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2',
                                    activeTab === 'company-profile'
                                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                            >
                                <i class="fa-solid fa-building-user text-xs"></i>
                                <span>4. Perfil da Empresa</span>
                            </button>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <BrandingIdentityTab
                                v-show="activeTab === 'identity'"
                                :form="form"
                                :logo-preview="logoPreview"
                                :banner-preview="bannerPreview"
                                @logo-change="handleLogoChange"
                                @remove-logo="handleRemoveLogo"
                                @banner-change="handleBannerChange"
                                @remove-banner="handleRemoveBanner"
                            />

                            <BrandingColorsTab
                                v-show="activeTab === 'colors'"
                                :form="form"
                            />

                            <BrandingContactTab
                                v-show="activeTab === 'contact'"
                                :form="form"
                            />

                            <BrandingCompanyProfileTab
                                v-show="activeTab === 'company-profile'"
                                :form="form"
                            />

                            <div class="pt-5 border-t flex items-center justify-between gap-3" style="border-color: var(--border);">
                                <button
                                    type="button"
                                    @click="resetToDefault"
                                    class="px-4 py-2.5 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all cursor-pointer"
                                >
                                    Restaurar Padrões
                                </button>

                                <button
                                    type="submit"
                                    class="btn btn-primary py-2.5 px-6 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30"
                                    :disabled="form.processing"
                                >
                                    <i class="fa-solid fa-floppy-disk text-xs mr-1"></i>
                                    <span>{{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-6 xl:col-span-5">
                    <BrandingLivePreview
                        :form="form"
                        :logo-preview="logoPreview"
                        :banner-preview="bannerPreview"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
