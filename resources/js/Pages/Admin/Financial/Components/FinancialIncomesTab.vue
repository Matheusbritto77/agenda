<script setup>
defineProps({
    history: {
        type: Array,
        default: () => [],
    },
    extraIncomesList: {
        type: Array,
        default: () => [],
    },
    categoryLabels: {
        type: Object,
        required: true,
    },
    hasPermission: {
        type: Function,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
});

defineEmits(['open-create-transaction', 'export-appointments']);
</script>

<template>
    <div class="space-y-4">
        <div class="card p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <h4 class="font-extrabold text-sm" style="color: var(--text-heading);">
                    Receitas de Agendamentos & Vendas Avulsas
                </h4>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="$emit('open-create-transaction', 'income')"
                    class="btn btn-outline py-1.5 px-3 text-xs font-bold rounded-xl inline-flex items-center gap-1.5 cursor-pointer hover:border-emerald-500 hover:text-emerald-600"
                >
                    <i class="fa-solid fa-plus text-xs text-emerald-500"></i>
                    <span>Lançar Entrada Avulsa</span>
                </button>
                <button
                    v-if="hasPermission('reports.export')"
                    type="button"
                    @click="$emit('export-appointments')"
                    class="btn btn-outline py-1.5 px-3 text-xs flex items-center gap-1.5 rounded-xl hover:text-emerald-500 hover:border-emerald-500"
                >
                    <i class="fa-solid fa-file-csv text-emerald-500"></i>
                    <span>Exportar CSV</span>
                </button>
            </div>
        </div>

        <!-- Incomes Table -->
        <div class="card p-0 overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th>Data / Hora</th>
                            <th>Origem / Descrição</th>
                            <th>Profissional / Responsável</th>
                            <th>Tipo / Categoria</th>
                            <th class="text-right">Valor Recebido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Extra Manual Incomes -->
                        <tr v-for="inc in extraIncomesList" :key="'inc-' + inc.id" class="bg-emerald-500/5 hover:bg-emerald-500/10">
                            <td>
                                <span class="font-bold text-xs">{{ inc.due_date }}</span>
                            </td>
                            <td>
                                <div class="space-y-0.5">
                                    <span class="font-bold text-xs text-emerald-700 dark:text-emerald-300">
                                        {{ inc.title }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 block">Venda Balcão / Entrada Extra</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                                    {{ inc.team_member?.name || 'Geral' }}
                                </span>
                            </td>
                            <td>
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                    {{ categoryLabels[inc.category] || inc.category }}
                                </span>
                            </td>
                            <td class="text-right">
                                <strong class="font-mono text-sm font-black text-emerald-600 dark:text-emerald-400">
                                    + R$ {{ formatCurrency(inc.amount) }}
                                </strong>
                            </td>
                        </tr>

                        <!-- Appointments Incomes -->
                        <template v-if="history.length > 0">
                            <tr v-for="row in history" :key="'apt-' + row.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30">
                                <td>
                                    <span class="text-xs opacity-80">{{ row.date }} às {{ row.time }}</span>
                                </td>
                                <td>
                                    <span class="font-bold text-xs" style="color: var(--text-heading);">
                                        {{ row.client_name }} - {{ row.service_name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ row.professional_name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                                        Serviço / Agendamento
                                    </span>
                                </td>
                                <td class="text-right">
                                    <strong class="font-mono text-sm font-bold text-slate-800 dark:text-slate-100">
                                        R$ {{ formatCurrency(row.price) }}
                                    </strong>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="history.length === 0 && extraIncomesList.length === 0">
                            <td colspan="5" class="text-center py-12 opacity-60 text-xs">
                                Nenhum faturamento registrado no período selecionado.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
