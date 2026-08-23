<script setup>
defineProps({
    badges: {
        type: Array,
        default: () => [],
    },
    activeCompany: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <div class="space-y-6">
        <section class="rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-cyan-600 p-6 sm:p-8 text-white shadow-xl shadow-indigo-600/20 relative overflow-hidden">
            <div class="relative z-10 space-y-2">
                <span class="text-xs font-black uppercase tracking-[0.2em] text-white/80">Programa de Conquistas & Fidelidade</span>
                <h2 class="text-2xl sm:text-3xl font-black">
                    {{ activeCompany ? ('Suas Conquistas em ' + activeCompany.name) : 'Suas Medalhas de Fidelidade' }}
                </h2>
                <p class="text-xs sm:text-sm text-white/80 max-w-xl">
                    A cada atendimento concluído, você acumula progresso para desbloquear novos níveis de cliente VIP e benefícios exclusivos.
                </p>
            </div>
        </section>

        <div v-if="activeCompany && activeCompany.show_loyalty_badges === false" class="rounded-3xl border border-dashed border-slate-300 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 p-12 text-center space-y-4">
            <div class="w-16 h-16 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mx-auto text-2xl">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Programa de Medalhas Indisponível</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    O estabelecimento <strong>{{ activeCompany.name }}</strong> optou por não exibir o programa de medalhas de fidelidade no momento.
                </p>
            </div>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            <div
                v-for="badge in badges"
                :key="badge.name"
                :class="[
                    'rounded-3xl border p-5 transition-all relative overflow-hidden flex flex-col justify-between space-y-4',
                    badge.earned
                        ? 'border-amber-400/40 bg-white dark:bg-slate-900 shadow-md ring-2 ring-amber-400/20'
                        : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40'
                ]"
            >
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-3xl p-2 rounded-2xl bg-amber-500/10 w-fit">
                            {{ {sparkles:'✨', star:'⭐', heart:'💜', crown:'👑', trophy:'🏆', gem:'💎'}[badge.icon] || '🎖️' }}
                        </div>
                        <span
                            :class="[
                                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black',
                                badge.earned ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                            ]"
                        >
                            <i :class="badge.earned ? 'fa-solid fa-check' : 'fa-solid fa-lock'" class="text-[9px]"></i>
                            <span>{{ badge.earned ? 'Desbloqueado' : 'Bloqueado' }}</span>
                        </span>
                    </div>

                    <div>
                        <h4 class="font-black text-sm text-slate-900 dark:text-white">{{ badge.name }}</h4>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block mt-0.5">
                            Meta: {{ badge.minimum }} atendimento(s)
                        </span>
                    </div>

                    <!-- Reward info -->
                    <div v-if="badge.reward" class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-[11px] font-extrabold text-amber-800 dark:text-amber-300">
                        <i class="fa-solid fa-gift text-xs mr-1 text-amber-500"></i>
                        <span>{{ badge.reward }}</span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-1">
                        <div class="flex justify-between text-[10px] font-bold text-slate-400">
                            <span>Progresso</span>
                            <span>{{ badge.progress_percent || (badge.earned ? 100 : 0) }}%</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="badge.earned ? 'bg-emerald-500' : 'bg-indigo-600'"
                                :style="{ width: `${badge.progress_percent || (badge.earned ? 100 : 0)}%` }"
                            ></div>
                        </div>
                        <p v-if="!badge.earned && badge.remaining" class="text-[10px] text-indigo-600 dark:text-cyan-400 font-bold">
                            Falta(m) apenas {{ badge.remaining }} atendimento(s)!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
