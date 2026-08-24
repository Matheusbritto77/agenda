<script setup>
import { computed } from 'vue';

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
    activeCompany: {
        type: Object,
        default: null,
    },
    summary: {
        type: Object,
        required: true,
    },
    companies: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['select-company', 'switch-tab', 'open-company-review']);

const customization = computed(() => props.activeCompany?.portal_customization || props.activeCompany || {});
</script>

<template>
    <section class="space-y-4">
        <div
            v-if="activeCompany"
            class="rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-2xl transition-all"
            :style="{
                background: `linear-gradient(135deg, ${customization.primary_color || '#6366f1'} 0%, ${customization.secondary_color || '#06b6d4'} 100%)`
            }"
        >
            <div v-if="customization.banner_url" class="absolute inset-0 z-0">
                <img :src="customization.banner_url" :alt="`Capa de ${activeCompany.name}`" class="h-full w-full object-cover opacity-30" />
                <div class="absolute inset-0 bg-gradient-to-r from-black/30 via-black/10 to-black/20"></div>
            </div>

            <div class="relative z-10 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-white text-slate-900 overflow-hidden flex items-center justify-center shadow-lg shrink-0">
                            <img v-if="customization.logo_url" :src="customization.logo_url" :alt="activeCompany.name" class="w-full h-full object-cover" />
                            <i v-else class="fa-solid fa-store text-2xl" :style="{ color: customization.primary_color || '#6366f1' }"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-black/25 text-white/95">
                                    Espaço Exclusivo
                                </span>
                                <span v-if="activeCompany.company_review" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400/20 text-amber-200 flex items-center gap-1">
                                    <i class="fa-solid fa-star text-amber-300 text-[9px]"></i>
                                    Sua nota: {{ activeCompany.company_review.rating }}/5
                                </span>
                            </div>
                            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black mt-1 text-white truncate">
                                {{ customization.welcome_title || activeCompany.name }}
                            </h1>
                            <p class="text-xs sm:text-sm text-white/85 mt-0.5 line-clamp-2">
                                {{ customization.welcome_subtitle || 'Acompanhe seus agendamentos, cupons e conquistas de fidelidade neste estabelecimento.' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <a
                            :href="activeCompany.booking_url || '/'"
                            class="px-4 py-2.5 rounded-xl bg-white text-slate-900 hover:bg-white/90 text-xs font-black shadow-lg flex items-center gap-2 transition-transform active:scale-95"
                        >
                            <i class="fa-solid fa-calendar-plus" :style="{ color: customization.primary_color || '#6366f1' }"></i>
                            <span>Novo Agendamento</span>
                        </a>

                        <a
                            v-if="customization.support_whatsapp"
                            :href="`https://wa.me/${customization.support_whatsapp.replace(/\D/g, '')}`"
                            target="_blank"
                            class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg flex items-center gap-2 transition-transform active:scale-95"
                        >
                            <i class="fa-brands fa-whatsapp text-sm"></i>
                            <span>WhatsApp</span>
                        </a>

                        <button
                            v-if="customization.show_reviews !== false"
                            type="button"
                            @click="$emit('open-company-review', activeCompany)"
                            class="px-4 py-2.5 rounded-xl bg-black/25 hover:bg-black/35 text-amber-200 text-xs font-black border border-white/20 shadow-lg flex items-center gap-2 transition-transform active:scale-95"
                        >
                            <i class="fa-solid fa-star text-amber-300"></i>
                            <span>{{ activeCompany.company_review ? 'Editar Avaliação' : 'Avaliar Empresa' }}</span>
                        </button>

                        <button
                            v-if="companies.length > 1"
                            type="button"
                            @click="$emit('select-company', null)"
                            class="px-3 py-2.5 rounded-xl bg-black/20 hover:bg-black/35 text-white text-xs font-bold border border-white/20 transition-all flex items-center gap-1.5"
                            title="Ver histórico de todas as empresas"
                        >
                            <i class="fa-solid fa-arrows-rotate text-[11px]"></i>
                            <span>Ver Todas</span>
                        </button>
                    </div>
                </div>

                <p v-if="customization.custom_instructions" class="text-xs text-white/80 border-t border-white/15 pt-3 flex items-start gap-2">
                    <i class="fa-solid fa-circle-info text-xs mt-0.5 shrink-0"></i>
                    <span>{{ customization.custom_instructions }}</span>
                </p>
            </div>
        </div>

        <div
            v-if="activeCompany && customization.announcement_enabled && customization.announcement"
            class="p-3 rounded-2xl border border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300 text-xs sm:text-sm font-bold flex items-center gap-2.5"
        >
            <i class="fa-solid fa-bullhorn text-amber-500 text-sm shrink-0"></i>
            <span>{{ customization.announcement }}</span>
        </div>

        <div v-if="!activeCompany" class="glass-card-3d rounded-3xl p-6 sm:p-8 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-cyan-400">
                            <i class="fa-solid fa-user-check text-lg"></i>
                        </span>
                        <span class="text-xs font-bold uppercase tracking-wider opacity-60">Visão Geral Global</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black mt-2" style="color: var(--text-heading);">
                        Olá, {{ client.name || 'Cliente' }}! 👋
                    </h1>
                    <p class="text-xs sm:text-sm opacity-70 mt-1">
                        Gerencie todos os seus horários agendados, medalhas de fidelidade e cupons em diferentes empresas parceiras.
                    </p>
                </div>
                <button
                    type="button"
                    @click="$emit('switch-tab', 'companies')"
                    class="btn btn-outline rounded-2xl text-xs font-bold self-start sm:self-center"
                >
                    <i class="fa-solid fa-building mr-1.5 text-indigo-500"></i>
                    {{ companies.length }} {{ companies.length === 1 ? 'Empresa Frequentada' : 'Empresas Frequentadas' }}
                </button>
            </div>
        </div>
    </section>
</template>
