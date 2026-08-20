<script setup>
import { computed } from 'vue';

const props = defineProps({
    companyProfile: {
        type: Object,
        required: true,
    },
    showProfessionalStep: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['start-booking']);

const coverImage = computed(() => props.companyProfile.banner_url || props.companyProfile.logo_url || null);

const statusClasses = computed(() => {
    return props.companyProfile.status?.is_open_now
        ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/25'
        : 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/25';
});

const primaryServices = computed(() => props.companyProfile.services_preview || []);
const hours = computed(() => props.companyProfile.hours_summary || []);
</script>

<template>
    <section class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-950 shadow-sm">
            <div class="relative min-h-[260px] sm:min-h-[360px] flex items-end">
                <img
                    v-if="coverImage"
                    :src="coverImage"
                    :alt="companyProfile.business_name"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div v-else class="absolute inset-0 bg-slate-900"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/55 to-slate-950/10"></div>

                <div class="relative z-10 w-full p-5 sm:p-8 text-white">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">
                        <div class="min-w-0 space-y-4">
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="companyProfile.logo_url"
                                    :src="companyProfile.logo_url"
                                    :alt="companyProfile.business_name"
                                    class="h-14 w-14 rounded-2xl object-cover border border-white/25 bg-white/10"
                                />
                                <div v-else class="h-14 w-14 rounded-2xl border border-white/20 bg-white/10 flex items-center justify-center">
                                    <i class="fa-solid fa-store text-xl"></i>
                                </div>

                                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-extrabold" :class="statusClasses">
                                    <i class="fa-solid fa-circle text-[7px]"></i>
                                    {{ companyProfile.status?.label }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <p v-if="companyProfile.tagline" class="text-xs sm:text-sm font-bold uppercase tracking-wider text-white/75">
                                    {{ companyProfile.tagline }}
                                </p>
                                <h1 class="text-3xl sm:text-5xl font-black leading-tight">
                                    {{ companyProfile.business_name }}
                                </h1>
                                <p class="max-w-2xl text-sm sm:text-base leading-relaxed text-white/82">
                                    {{ companyProfile.description || 'Confira os servicos, horarios e profissionais disponiveis para agendar seu atendimento.' }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="$emit('start-booking')"
                            class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-black bg-white text-slate-950 hover:bg-slate-100 transition-all shadow-lg shrink-0"
                        >
                            <i class="fa-solid fa-calendar-check"></i>
                            <span>Agendar agora</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-0 border-t border-slate-200 dark:border-slate-800 md:grid-cols-3">
                <div class="p-5 border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-800">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-200">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Agora</p>
                            <p class="text-sm font-black text-slate-900 dark:text-white">
                                {{ companyProfile.status?.label }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Hoje: {{ companyProfile.status?.today_summary }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-5 border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-800">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-200">
                            <i class="fa-solid fa-scissors"></i>
                        </div>
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Servicos</p>
                            <p class="text-sm font-black text-slate-900 dark:text-white">
                                {{ companyProfile.services_count }} disponiveis
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Escolha o servico na proxima etapa.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-5">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-200">
                            <i class="fa-solid fa-user-group"></i>
                        </div>
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Profissionais</p>
                            <p class="text-sm font-black text-slate-900 dark:text-white">
                                {{ showProfessionalStep ? `${companyProfile.professionals_count} para escolher` : 'Atendimento direto' }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ showProfessionalStep ? 'Selecao feita ao agendar.' : 'Sem etapa de profissional.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-[1.15fr_0.85fr]">
            <section class="rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-950 p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Servicos em destaque</h2>
                        <p class="text-xs text-slate-500">Resumo dos atendimentos disponiveis.</p>
                    </div>
                    <button
                        type="button"
                        @click="$emit('start-booking')"
                        class="h-10 w-10 rounded-xl inline-flex items-center justify-center bg-slate-900 text-white dark:bg-white dark:text-slate-950 hover:opacity-90 transition-opacity"
                        aria-label="Agendar agora"
                    >
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>

                <div v-if="primaryServices.length" class="divide-y divide-slate-200 dark:divide-slate-800">
                    <div v-for="service in primaryServices" :key="service.id" class="py-3 first:pt-0 last:pb-0 flex items-center gap-3">
                        <img
                            v-if="service.image_url"
                            :src="service.image_url"
                            :alt="service.name"
                            class="h-12 w-12 rounded-xl object-cover bg-slate-100 dark:bg-slate-900"
                        />
                        <div v-else class="h-12 w-12 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-900 text-slate-500">
                            <i class="fa-solid fa-scissors"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-extrabold text-slate-900 dark:text-white truncate">{{ service.name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ service.duration_minutes }} min</p>
                        </div>
                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ service.formatted_price }}</p>
                    </div>
                </div>

                <p v-else class="text-sm text-slate-500">
                    Nenhum servico ativo cadastrado no momento.
                </p>
            </section>

            <section class="rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-950 p-5 shadow-sm">
                <h2 class="text-base font-black text-slate-900 dark:text-white mb-4">Resumo de horario</h2>

                <div class="space-y-2">
                    <div
                        v-for="day in hours"
                        :key="day.day"
                        class="flex items-center justify-between gap-3 text-sm"
                    >
                        <span class="font-bold text-slate-600 dark:text-slate-300">{{ day.day }}</span>
                        <span class="text-right text-xs font-semibold" :class="day.is_open ? 'text-slate-900 dark:text-white' : 'text-slate-400'">
                            {{ day.summary }}
                        </span>
                    </div>
                </div>
            </section>
        </div>
    </section>
</template>
