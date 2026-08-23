<script setup>
defineProps({
    expensesList: {
        type: Array,
        default: () => [],
    },
    expenseStatusFilter: {
        type: String,
        default: 'all',
    },
    categoryLabels: {
        type: Object,
        required: true,
    },
    paymentMethodLabels: {
        type: Object,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
});

defineEmits([
    'update:expenseStatusFilter',
    'open-create-transaction',
    'open-edit-transaction',
    'open-delete-transaction',
    'toggle-status',
]);
</script>

<template>
    <div class="space-y-4">
        <div class="card p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Filter by status -->
                <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                    <button
                        type="button"
                        @click="$emit('update:expenseStatusFilter', 'all')"
                        :class="[
                            'px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer',
                            expenseStatusFilter === 'all'
                                ? 'bg-white dark:bg-slate-900 text-indigo-600 shadow-xs'
                                : 'text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        Todas
                    </button>
                    <button
                        type="button"
                        @click="$emit('update:expenseStatusFilter', 'pending')"
                        :class="[
                            'px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer',
                            expenseStatusFilter === 'pending'
                                ? 'bg-white dark:bg-slate-900 text-amber-600 shadow-xs'
                                : 'text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        ⏳ Pendentes
                    </button>
                    <button
                        type="button"
                        @click="$emit('update:expenseStatusFilter', 'overdue')"
                        :class="[
                            'px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer',
                            expenseStatusFilter === 'overdue'
                                ? 'bg-white dark:bg-slate-900 text-rose-600 shadow-xs'
                                : 'text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        🚨 Vencidas
                    </button>
                    <button
                        type="button"
                        @click="$emit('update:expenseStatusFilter', 'paid')"
                        :class="[
                            'px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer',
                            expenseStatusFilter === 'paid'
                                ? 'bg-white dark:bg-slate-900 text-emerald-600 shadow-xs'
                                : 'text-slate-500 hover:text-slate-800'
                        ]"
                    >
                        ✅ Pagas
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="$emit('open-create-transaction', 'expense')"
                    class="btn btn-primary py-1.5 px-3.5 text-xs font-bold rounded-xl inline-flex items-center gap-1.5 cursor-pointer"
                >
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Adicionar Despesa</span>
                </button>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="card p-0 overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th>Descrição / Conta</th>
                            <th>Categoria</th>
                            <th>Vencimento</th>
                            <th>Forma de Pagto</th>
                            <th>Status / Situação</th>
                            <th>Valor (R$)</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="expensesList.length > 0">
                            <tr v-for="t in expensesList" :key="t.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30">
                                <td>
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-xs sm:text-sm" style="color: var(--text-heading);">
                                            {{ t.title }}
                                        </span>
                                        <div v-if="t.notes" class="text-[11px] text-slate-400 truncate max-w-xs">
                                            {{ t.notes }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                        {{ categoryLabels[t.category] || t.category }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-xs font-semibold" :class="{ 'text-rose-500 font-bold': t.computed_status === 'overdue' }">
                                        {{ t.due_date }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ paymentMethodLabels[t.payment_method] || t.payment_method || '-' }}
                                    </span>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        @click="$emit('toggle-status', t)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition-all cursor-pointer border"
                                        :class="{
                                            'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/25': t.status === 'paid',
                                            'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30 hover:bg-rose-500/25': t.computed_status === 'overdue',
                                            'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30 hover:bg-amber-500/25': t.computed_status === 'pending'
                                        }"
                                        :title="t.status === 'paid' ? 'Clique para marcar como pendente' : 'Clique para dar baixa/pagar'"
                                    >
                                        <i v-if="t.status === 'paid'" class="fa-solid fa-check text-[10px]"></i>
                                        <i v-else-if="t.computed_status === 'overdue'" class="fa-solid fa-circle-exclamation text-[10px]"></i>
                                        <i v-else class="fa-regular fa-clock text-[10px]"></i>
                                        <span>{{ t.status === 'paid' ? 'Pago' : (t.computed_status === 'overdue' ? 'Vencido (Pagar)' : 'Pendente (Pagar)') }}</span>
                                    </button>
                                </td>
                                <td>
                                    <strong class="font-mono text-sm font-bold text-rose-600 dark:text-rose-400">
                                        R$ {{ formatCurrency(t.amount) }}
                                    </strong>
                                </td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="$emit('open-edit-transaction', t)"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all cursor-pointer"
                                            title="Editar despesa"
                                        >
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </button>
                                        <button
                                            type="button"
                                            @click="$emit('open-delete-transaction', t)"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-all cursor-pointer"
                                            title="Excluir"
                                        >
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="7" class="text-center py-12 opacity-60 text-xs">
                                Nenhuma despesa ou conta a pagar cadastrada com os filtros atuais.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
