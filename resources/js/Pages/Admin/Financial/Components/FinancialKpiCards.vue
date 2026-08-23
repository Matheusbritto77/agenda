<script setup>
defineProps({
    stats: {
        type: Object,
        required: true,
    },
    teamMember: {
        type: Object,
        default: null,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
    formatPercent: {
        type: Function,
        required: true,
    },
});
</script>

<template>
    <!-- Professional-Only KPIs -->
    <div v-if="teamMember" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Faturamento Gerado</span>
                <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100">R$ {{ formatCurrency(stats.total_revenue) }}</h3>
                <span class="text-[11px] opacity-70">Serviços prestados por você</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-dollar-sign"></i>
            </div>
        </div>

        <div class="card p-4 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Comissão Padrão</span>
                <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ formatPercent(stats.commission_rate) }}%</h3>
                <span class="text-[11px] opacity-70">Definido no seu cadastro</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-percent"></i>
            </div>
        </div>

        <div class="card p-4 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Seus Ganhos / Repasse</span>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400">R$ {{ formatCurrency(stats.my_commissions) }}</h3>
                <span class="text-[11px] text-emerald-500 font-bold">Líquido a receber</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
        </div>

        <div class="card p-4 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Atendimentos Concluídos</span>
                <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ stats.appointments_count }}</h3>
                <span class="text-[11px] opacity-70">No período selecionado</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30 flex items-center justify-center text-lg shrink-0">
                <i class="fa-regular fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <!-- Admin Executive Financial KPIs -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Inflow / Receitas -->
        <div class="card p-4.5 rounded-2xl relative overflow-hidden flex flex-col justify-between border border-emerald-500/20 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Entradas Totais</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-slate-100 mt-1">
                        R$ {{ formatCurrency(stats.total_revenue) }}
                    </h3>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>
            <div class="pt-3 border-t border-slate-200/60 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-3">
                <span>Agendamentos: <strong>R$ {{ formatCurrency(stats.appointment_revenue) }}</strong></span>
                <span v-if="stats.extra_income > 0">Extras: <strong>+R$ {{ formatCurrency(stats.extra_income) }}</strong></span>
            </div>
        </div>

        <!-- 2. Total Outflow / Saídas Pagas -->
        <div class="card p-4.5 rounded-2xl relative overflow-hidden flex flex-col justify-between border border-rose-500/20 bg-gradient-to-br from-rose-500/5 via-transparent to-transparent">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider block">Saídas & Custos Pagos</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-slate-100 mt-1">
                        R$ {{ formatCurrency(stats.expenses_paid + stats.total_commissions) }}
                    </h3>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-arrow-trend-down"></i>
                </div>
            </div>
            <div class="pt-3 border-t border-slate-200/60 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-3">
                <span>Despesas: <strong>R$ {{ formatCurrency(stats.expenses_paid) }}</strong></span>
                <span>Comissões: <strong>R$ {{ formatCurrency(stats.total_commissions) }}</strong></span>
            </div>
        </div>

        <!-- 3. Net Operational Profit -->
        <div class="card p-4.5 rounded-2xl relative overflow-hidden flex flex-col justify-between border border-indigo-500/20 bg-gradient-to-br from-indigo-500/5 via-transparent to-transparent">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block">Saldo / Lucro Operacional</span>
                    <h3 :class="['text-2xl sm:text-3xl font-black mt-1', stats.net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400']">
                        R$ {{ formatCurrency(stats.net_profit) }}
                    </h3>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div class="pt-3 border-t border-slate-200/60 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-3">
                <span>Resultado líquido retido</span>
                <span class="font-bold text-indigo-500">{{ stats.appointments_count }} atendimentos</span>
            </div>
        </div>

        <!-- 4. Accounts Payable Pending & Overdue -->
        <div class="card p-4.5 rounded-2xl relative overflow-hidden flex flex-col justify-between border border-amber-500/20 bg-gradient-to-br from-amber-500/5 via-transparent to-transparent">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider block">Contas a Pagar (A Vencer / Vencidas)</span>
                    <h3 class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400 mt-1">
                        R$ {{ formatCurrency(stats.total_payable) }}
                    </h3>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <div class="pt-3 border-t border-slate-200/60 dark:border-slate-800/80 flex items-center justify-between text-[11px] mt-3">
                <span class="text-slate-500 dark:text-slate-400">Pendentes: <strong>R$ {{ formatCurrency(stats.expenses_pending) }}</strong></span>
                <span v-if="stats.expenses_overdue > 0" class="text-rose-500 font-bold">Vencidas: R$ {{ formatCurrency(stats.expenses_overdue) }}</span>
                <span v-else class="text-emerald-500 font-bold">0 vencidas</span>
            </div>
        </div>
    </div>
</template>
