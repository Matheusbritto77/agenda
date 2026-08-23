<script setup>
import { ref } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    logoPreview: {
        type: String,
        default: null,
    },
    bannerPreview: {
        type: String,
        default: null,
    },
    faviconPreview: {
        type: String,
        default: null,
    },
});

const emit = defineEmits([
    'logo-change',
    'remove-logo',
    'banner-change',
    'remove-banner',
    'favicon-change',
    'remove-favicon',
]);

const logoFileInput = ref(null);
const bannerFileInput = ref(null);
const faviconFileInput = ref(null);
const logoError = ref('');
const bannerError = ref('');
const faviconError = ref('');
const maxSizeBytes = 10 * 1024 * 1024; // 10MB
const maxFaviconSizeBytes = 5 * 1024 * 1024; // 5MB

const onLogoSelected = (e) => {
    const file = e.target.files?.[0];
    logoError.value = '';
    if (!file) return;
    if (file.size > maxSizeBytes) {
        logoError.value = 'O arquivo do logotipo deve ter no máximo 10 MB.';
        e.target.value = '';
        return;
    }
    emit('logo-change', file);
};

const onBannerSelected = (e) => {
    const file = e.target.files?.[0];
    bannerError.value = '';
    if (!file) return;
    if (file.size > maxSizeBytes) {
        bannerError.value = 'O arquivo de imagem de capa deve ter no máximo 10 MB.';
        e.target.value = '';
        return;
    }
    emit('banner-change', file);
};

const onFaviconSelected = (e) => {
    const file = e.target.files?.[0];
    faviconError.value = '';
    if (!file) return;
    if (file.size > maxFaviconSizeBytes) {
        faviconError.value = 'O arquivo do favicon deve ter no máximo 5 MB.';
        e.target.value = '';
        return;
    }
    emit('favicon-change', file);
};
</script>

<template>
    <div class="space-y-5">
        <!-- Business Name -->
        <div class="form-group mb-0">
            <label class="form-label text-xs font-bold block" for="business_name">Nome do Estabelecimento</label>
            <input
                type="text"
                id="business_name"
                v-model="form.business_name"
                class="form-control text-xs sm:text-sm rounded-xl"
                placeholder="Ex: Barbearia Don Corleone, Clínica Estética Lumina"
            />
            <p class="text-[11px] text-slate-400 mt-1">Exibido no topo e rodapé da página pública.</p>
        </div>

        <!-- Tagline / Slogan -->
        <div class="form-group mb-0">
            <label class="form-label text-xs font-bold block" for="tagline">Slogan ou Mensagem de Destaque</label>
            <input
                type="text"
                id="tagline"
                v-model="form.tagline"
                class="form-control text-xs sm:text-sm rounded-xl"
                placeholder="Ex: Agende seu horário com os melhores especialistas da cidade."
            />
            <p class="text-[11px] text-slate-400 mt-1">Frase que aparece em destaque logo no início do agendamento.</p>
        </div>

        <!-- Favicon Upload & Browser Preview -->
        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <label class="form-label text-xs font-bold block mb-0">Favicon da Página Pública</label>
                    <p class="text-[11px] text-slate-400 mt-0.5">Ícone exibido na aba do navegador e nos favoritos da sua página pública de agendamentos.</p>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30">
                    Aba do Navegador
                </span>
            </div>

            <!-- Realistic Browser Tab Mockup -->
            <div class="p-3 rounded-xl bg-slate-200/70 dark:bg-slate-950/70 border border-slate-300/60 dark:border-slate-800 flex items-center gap-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm max-w-xs">
                    <div class="w-4 h-4 rounded overflow-hidden flex items-center justify-center shrink-0">
                        <img v-if="faviconPreview" :src="faviconPreview" class="w-full h-full object-contain" alt="Favicon preview" />
                        <img v-else src="/favicon.svg" class="w-full h-full object-contain" alt="Default favicon" />
                    </div>
                    <span class="text-[11px] font-bold truncate text-slate-800 dark:text-slate-200">
                        {{ form.business_name || 'Agendamento Online' }} - Agendae
                    </span>
                    <i class="fa-solid fa-xmark text-[9px] text-slate-400 ml-auto"></i>
                </div>
                <span class="text-[10px] text-slate-400 hidden sm:inline italic">
                    (Pré-visualização da aba no navegador)
                </span>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <div class="w-14 h-14 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white flex items-center justify-center overflow-hidden shadow-inner shrink-0 p-2">
                    <img v-if="faviconPreview" :src="faviconPreview" class="object-contain w-full h-full" alt="Favicon preview" />
                    <img v-else src="/favicon.svg" class="object-contain w-full h-full opacity-60" alt="Default favicon" />
                </div>
                <div class="space-y-1.5">
                    <input
                        type="file"
                        ref="faviconFileInput"
                        @change="onFaviconSelected"
                        accept="image/png,image/svg+xml,image/x-icon,image/vnd.microsoft.icon,image/webp,image/jpeg"
                        class="hidden"
                        id="favicon_file_input"
                    />
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="$refs.faviconFileInput.click()"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all cursor-pointer shadow-sm"
                        >
                            <i class="fa-solid fa-upload text-[11px] mr-1"></i>
                            {{ faviconPreview ? 'Trocar Favicon' : 'Escolher Favicon' }}
                        </button>
                        <button
                            v-if="faviconPreview"
                            type="button"
                            @click="$emit('remove-favicon')"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-900/30 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-950/40 transition-all cursor-pointer"
                        >
                            <i class="fa-solid fa-trash text-[11px] mr-1"></i>
                            Restaurar Padrão
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-400 leading-tight">
                        PNG, SVG, ICO ou WEBP (Recomendado: 32x32px, 64x64px ou quadrado, máx: 5MB).
                    </p>
                    <p v-if="faviconError" class="text-xs text-rose-500 font-bold mt-1">{{ faviconError }}</p>
                    <p v-if="form.errors?.favicon_file" class="text-xs text-rose-500 font-bold mt-1">{{ form.errors.favicon_file }}</p>
                </div>
            </div>
        </div>

        <!-- Logo Upload -->
        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-3">
            <label class="form-label text-xs font-bold block mb-0">Logotipo Oficial do Estabelecimento</label>
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white flex items-center justify-center overflow-hidden shadow-inner shrink-0">
                    <img v-if="logoPreview" :src="logoPreview" class="object-contain w-full h-full p-1" alt="Logo preview" />
                    <i v-else class="fa-solid fa-image text-2xl text-slate-300"></i>
                </div>
                <div class="space-y-2">
                    <input
                        type="file"
                        ref="logoFileInput"
                        @change="onLogoSelected"
                        accept="image/*"
                        class="hidden"
                        id="logo_file_input"
                    />
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="$refs.logoFileInput.click()"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all cursor-pointer shadow-sm"
                        >
                            <i class="fa-solid fa-upload text-[11px] mr-1"></i>
                            Escolher logotipo
                        </button>
                        <button
                            v-if="logoPreview"
                            type="button"
                            @click="$emit('remove-logo')"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-900/30 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-950/40 transition-all cursor-pointer"
                        >
                            <i class="fa-solid fa-trash text-[11px] mr-1"></i>
                            Remover
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-400 leading-tight">
                        PNG, JPG ou WEBP (Máx: 10MB). Quando você define um logotipo, a marca "Agendae" é ocultada automaticamente.
                    </p>
                    <p v-if="logoError" class="text-xs text-rose-500 font-bold mt-1">{{ logoError }}</p>
                    <p v-if="form.errors?.logo_file" class="text-xs text-rose-500 font-bold mt-1">{{ form.errors.logo_file }}</p>
                </div>
            </div>
        </div>

        <!-- Banner / Cover Upload -->
        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-3">
            <label class="form-label text-xs font-bold block mb-0">Banner / Imagem de Capa do Agendamento (Opcional)</label>
            <div class="space-y-3">
                <div v-if="bannerPreview" class="h-28 sm:h-32 w-full rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 relative group">
                    <img :src="bannerPreview" class="w-full h-full object-cover" alt="Banner preview" />
                    <button
                        type="button"
                        @click="$emit('remove-banner')"
                        class="absolute top-2 right-2 p-2 rounded-lg bg-rose-600 text-white shadow-md opacity-90 hover:opacity-100 transition-opacity"
                        title="Remover Capa"
                    >
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <input
                        type="file"
                        ref="bannerFileInput"
                        @change="onBannerSelected"
                        accept="image/*"
                        class="hidden"
                        id="banner_file_input"
                    />
                    <button
                        type="button"
                        @click="$refs.bannerFileInput.click()"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all cursor-pointer shadow-sm"
                    >
                        <i class="fa-solid fa-image text-[11px] mr-1"></i>
                        {{ bannerPreview ? 'Trocar imagem de capa' : 'Adicionar capa de cabeçalho' }}
                    </button>
                    <span class="text-[10px] text-slate-400">Recomendado: 1200x400px (Máx: 10MB).</span>
                </div>
                <p v-if="bannerError" class="text-xs text-rose-500 font-bold mt-1">{{ bannerError }}</p>
                <p v-if="form.errors?.banner_file" class="text-xs text-rose-500 font-bold mt-1">{{ form.errors.banner_file }}</p>
            </div>
        </div>
    </div>
</template>
