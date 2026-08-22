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
});

const emit = defineEmits(['logo-change', 'remove-logo', 'banner-change', 'remove-banner']);

const logoFileInput = ref(null);
const bannerFileInput = ref(null);

const onLogoSelected = (e) => {
    emit('logo-change', e.target.files[0]);
};

const onBannerSelected = (e) => {
    emit('banner-change', e.target.files[0]);
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
            </div>
        </div>
    </div>
</template>
