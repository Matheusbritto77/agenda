<script setup>
defineProps({
    stats: {
        type: Object,
        default: () => ({
            today_total: 0,
            confirmed_total: 0,
            completed_total: 0,
            week_total: 0,
        }),
    },
});
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Data selecionada -->
        <div class="card p-4 sm:p-5 flex items-center justify-between border shadow-sm group hover:border-indigo-500/40 transition-all">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Na data</span>
                <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">{{ stats.today_total }}</span>
                <p class="text-[11px] text-slate-400">Agendamentos do dia</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl shadow-xs group-hover:scale-105 transition-transform">
                <i class="fa-regular fa-calendar-check"></i>
            </div>
        </div>

        <!-- Confirmados -->
        <div class="card p-4 sm:p-5 flex items-center justify-between border shadow-sm group hover:border-emerald-500/40 transition-all">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Confirmados</span>
                <span class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ stats.confirmed_total }}</span>
                <p class="text-[11px] text-slate-400">Confirmados no dia</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shadow-xs group-hover:scale-105 transition-transform">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <!-- Concluídos -->
        <div class="card p-4 sm:p-5 flex items-center justify-between border shadow-sm group hover:border-blue-500/40 transition-all">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Concluídos</span>
                <span class="text-2xl sm:text-3xl font-black text-blue-600 dark:text-blue-400">{{ stats.completed_total }}</span>
                <p class="text-[11px] text-slate-400">Concluídos no dia</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl shadow-xs group-hover:scale-105 transition-transform">
                <i class="fa-solid fa-flag-checkered"></i>
            </div>
        </div>

        <!-- Esta Semana -->
        <div class="card p-4 sm:p-5 flex items-center justify-between border shadow-sm group hover:border-violet-500/40 transition-all">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Semana</span>
                <span class="text-2xl sm:text-3xl font-black text-violet-600 dark:text-violet-400">{{ stats.week_total }}</span>
                <p class="text-[11px] text-slate-400">Semana selecionada</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center text-xl shadow-xs group-hover:scale-105 transition-transform">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>
    </div>
</template>
