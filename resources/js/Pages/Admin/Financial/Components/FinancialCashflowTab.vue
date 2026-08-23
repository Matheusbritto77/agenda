<script setup>
defineProps({
    stats: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
});

defineEmits(['open-create-transaction', 'export-cashflow', 'switch-tab']);
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Demonstrativo de Resultados -->
            <div class="card p-5 lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-scale-balanced text-indigo-500"></i>
                        <h4 class="font-extrabold text-sm sm:text-base" style="color: var(--text-heading);">
                            Demonstrativo de Resultado do Período (DRE)
                        </h4>
                    </div>
                    <span class="text-xs text-slate-400 font-semibold">Período: {{ filters.period }}</span>
                </div>

                <div class="space-y-3 text-xs sm:text-sm">
                    <!-- Receita Bruta de Serviços -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-scissors text-emerald-600 text-xs"></i>
                            <span class="font-bold text-slate-700 dark:text-slate-200">(+) Receita de Agendamentos / Serviços</span>
                        </div>
                        <strong class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">R$ {{ formatCurrency(stats.appointment_revenue) }}</strong>
                    </div>

                    <!-- Receita de Vendas Extras -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-teal-50/50 dark:bg-teal-950/20 border border-teal-200 dark:border-teal-900/30">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-cart-shopping text-teal-600 text-xs"></i>
                            <span class="font-bold text-slate-700 dark:text-slate-200">(+) Outras Receitas & Vendas Balcão</span>
                        </div>
                        <strong class="font-mono text-teal-600 dark:text-teal-400 font-bold">R$ {{ formatCurrency(stats.extra_income) }}</strong>
                    </div>

                    <!-- Total de Entradas -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-100 dark:bg-slate-800/80 font-bold">
                        <span>(=) Faturamento Bruto Total</span>
                        <strong class="font-mono text-slate-800 dark:text-slate-100">R$ {{ formatCurrency(stats.total_revenue) }}</strong>
                    </div>

                    <!-- Dedução: Comissões -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-users text-rose-600 text-xs"></i>
                            <span class="font-bold text-slate-700 dark:text-slate-200">(-) Repasse de Comissões aos Profissionais</span>
                        </div>
                        <strong class="font-mono text-rose-600 dark:text-rose-400 font-bold">- R$ {{ formatCurrency(stats.total_commissions) }}</strong>
                    </div>

                    <!-- Dedução: Despesas Operacionais Pagas -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-rose-600 text-xs"></i>
                            <span class="font-bold text-slate-700 dark:text-slate-200">(-) Despesas Operacionais Pagas (Luz, Aluguel, etc.)</span>
                        </div>
                        <strong class="font-mono text-rose-600 dark:text-rose-400 font-bold">- R$ {{ formatCurrency(stats.expenses_paid) }}</strong>
                    </div>

                    <!-- Lucro Líquido Final -->
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-trophy text-amber-300"></i>
                            <span class="font-black text-sm uppercase tracking-wider">(=) Lucro Operacional Líquido do Estabelecimento</span>
                        </div>
                        <strong class="text-base sm:text-xl font-black font-mono">R$ {{ formatCurrency(stats.net_profit) }}</strong>
                    </div>
                </div>
            </div>

            <!-- Ações Rápidas & Alertas Financeiros -->
            <div class="space-y-4">
                <div class="card p-5 space-y-4">
                    <h4 class="font-extrabold text-xs uppercase tracking-wider text-slate-400">Ações Rápidas de Caixa</h4>
                    <div class="space-y-2">
                        <button
                            type="button"
                            @click="$emit('open-create-transaction', 'expense')"
                            class="w-full p-3 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-900/30 bg-rose-50/70 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 flex items-center justify-between transition-all cursor-pointer"
                        >
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-plus-circle"></i>
                                <span>Registrar Nova Despesa</span>
                            </span>
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>

                        <button
                            type="button"
                            @click="$emit('open-create-transaction', 'income')"
                            class="w-full p-3 rounded-xl text-xs font-bold border border-emerald-200 dark:border-emerald-900/30 bg-emerald-50/70 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 flex items-center justify-between transition-all cursor-pointer"
                        >
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-plus-circle"></i>
                                <span>Lançar Venda / Receita Extra</span>
                            </span>
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>

                        <button
                            type="button"
                            @click="$emit('export-cashflow')"
                            class="w-full p-3 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 flex items-center justify-between transition-all cursor-pointer shadow-2xs"
                        >
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-file-csv text-emerald-500"></i>
                                <span>Exportar Fluxo de Caixa (CSV)</span>
                            </span>
                            <i class="fa-solid fa-download text-[10px]"></i>
                        </button>
                    </div>
                </div>

                <!-- Alerta de Contas a Pagar -->
                <div class="card p-5 space-y-3 bg-amber-500/5 border-amber-500/20">
                    <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400 font-black text-xs uppercase tracking-wider">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Compromissos a Liquidar</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Você tem <strong>R$ {{ formatCurrency(stats.total_payable) }}</strong> em contas pendentes neste período.
                    </p>
                    <button
                        type="button"
                        @click="$emit('switch-tab', 'expenses')"
                        class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline inline-flex items-center gap-1 cursor-pointer"
                    >
                        <span>Ver contas a pagar</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
