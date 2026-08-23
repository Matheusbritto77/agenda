<script setup>
defineProps({
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

defineEmits(['select-company', 'switch-tab']);
</script>

<template>
    <section class="space-y-4">
        <div
            v-if="activeCompany"
            class="rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-2xl transition-all"
            :style="{
                background: `linear-gradient(135deg, ${activeCompany.portal_customization?.primary_color || '#6366f1'} 0%, ${activeCompany.portal_customization?.secondary_color || '#06b6d4'} 100%)`
            }"
        >
            <div class="relative z-10 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-white text-slate-900 overflow-hidden flex items-center justify-center shadow-lg shrink-0">
                            <img v-if="activeCompany.portal_customization?.logo_url || activeCompany.logo_url" :src="activeCompany.portal_customization?.logo_url || activeCompany.logo_url" class="w-full h-full object-cover" />
                            <i v-else class="fa-solid fa-store text-2xl text-indigo-600"></i>
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
                                {{ activeCompany.portal_customization?.welcome_title || activeCompany.name }}
                            </h1>
                            <p class="text-xs sm:text-sm text-white/85 mt-0.5 line-clamp-2">
                                {{ activeCompany.portal_customization?.welcome_subtitle || 'Acompanhe seus agendamentos, cupons e conquistas de fidelidade neste estabelecimento.' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <a
                            :href="`/${activeCompany.slug}`"
                            class="px-4 py-2.5 rounded-xl bg-white text-slate-900 hover:bg-white/90 text-xs font-black shadow-lg flex items-center gap-2 transition-transform active:scale-95"
                        >
                            <i class="fa-solid fa-calendar-plus text-indigo-600"></i>
                            <span>Novo Agendamento</span>
                        </a>

                        <a
                            v-if="activeCompany.portal_customization?.support_whatsapp || activeCompany.phone"
                            :href="`https://wa.me/${(activeCompany.portal_customization?.support_whatsapp || activeCompany.phone).replace(/\\D/g, '')}`"
                            target="_blank"
                            class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg flex items-center gap-2 transition-transform active:scale-95"
                        >
                            <i class="fa-brands fa-whatsapp text-sm"></i>
                            <span>WhatsApp</span>
                        </a>

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

                <div
                    v-if="activeCompany.portal_customization?.announcement_enabled && activeCompany.portal_customization?.announcement"
                    class="p-3 rounded-2xl bg-black/20 backdrop-blur-sm border border-white/20 text-xs sm:text-sm font-medium flex items-center gap-2.5"
                >
                    <i class="fa-solid fa-bullhorn text-amber-300 text-sm shrink-0"></i>
                    <span>{{ activeCompany.portal_customization.announcement }}</span>
                </div>

                <p v-if="activeCompany.portal_customization?.custom_instructions" class="text-xs text-white/80 border-t border-white/15 pt-3 flex items-start gap-2">
                    <i class="fa-solid fa-circle-info text-xs mt-0.5 shrink-0"></i>
                    <span>{{ activeCompany.portal_customization.custom_instructions }}</span>
                </p>
            </div>
        </div>

        <div v-else class="glass-card-3d rounded-3xl p-6 sm:p-8 space-y-4">
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
