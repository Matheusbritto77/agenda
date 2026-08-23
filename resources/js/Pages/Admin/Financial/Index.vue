<script setup>
import { ref, computed } from 'vue';
import { Head, usePage, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TransactionModal from './Components/TransactionModal.vue';
import TransactionDeleteModal from './Components/TransactionDeleteModal.vue';

const page = usePage();

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_revenue: 0,
            appointment_revenue: 0,
            extra_income: 0,
            total_commissions: 0,
            expenses_paid: 0,
            expenses_pending: 0,
            expenses_overdue: 0,
            total_payable: 0,
            net_profit: 0,
            appointments_count: 0,
            commission_rate: 0,
            my_commissions: 0,
            members_data: [],
            unassigned_revenue: 0,
        })
    },
    history: {
        type: Array,
        default: () => []
    },
    transactions: {
        type: Array,
        default: () => []
    },
    teamMembers: {
        type: Array,
        default: () => []
    },
    teamMember: {
        type: Object,
        default: null
    },
    filters: {
        type: Object,
        default: () => ({
            period: 'this_month',
            start_date: null,
            end_date: null,
        })
    },
});

const activeTab = ref('cashflow'); // 'cashflow', 'expenses', 'incomes', 'commissions'
const expenseStatusFilter = ref('all'); // 'all', 'pending', 'paid', 'overdue'
const expenseCategoryFilter = ref('all');

const showTransactionModal = ref(false);
const isEditingTransaction = ref(false);
const showDeleteTransactionModal = ref(false);
const transactionToDelete = ref(null);

const transactionForm = useForm({
    id: null,
    type: 'expense',
    category: 'utilidades',
    title: '',
    description: '',
    amount: '',
    due_date: new Date().toISOString().split('T')[0],
    paid_at: '',
    status: 'pending',
    payment_method: 'pix',
    team_member_id: null,
    notes: '',
});

const deleteForm = useForm({});

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    const userPerms = page.props.auth?.permissions || [];
    return userPerms.includes(permission);
};

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const formatPercent = (value) => {
    return Number(value || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const getInitials = (name) => {
    if (!name) return 'A';
    return name.substring(0, 2).toUpperCase();
};

const setPeriod = (period) => {
    router.get(route('admin.financial.index'), { period }, { preserveState: true, preserveScroll: true });
};

// Filtered Transactions
const expensesList = computed(() => {
    return props.transactions.filter(t => t.type === 'expense').filter(t => {
        if (expenseStatusFilter.value === 'all') return true;
        return t.computed_status === expenseStatusFilter.value;
    }).filter(t => {
        if (expenseCategoryFilter.value === 'all') return true;
        return t.category === expenseCategoryFilter.value;
    });
});

const extraIncomesList = computed(() => {
    return props.transactions.filter(t => t.type === 'income');
});

// Category Name Labels & Badges
const categoryLabels = {
    aluguel: 'Aluguel & Condomínio',
    utilidades: 'Contas (Luz, Água, Internet)',
    fornecedores: 'Fornecedores & Produtos',
    marketing: 'Marketing & Anúncios',
    pessoal: 'Equipe & Pró-labore',
    impostos: 'Impostos & Taxas',
    manutencao: 'Manutenção & Equipamentos',
    venda_produtos: 'Venda de Produtos',
    cursos: 'Cursos & Treinamentos',
    consultoria: 'Consultoria',
    outros: 'Outros',
};

const paymentMethodLabels = {
    pix: 'Pix',
    credit_card: 'Cartão de Crédito',
    debit_card: 'Cartão de Débito',
    boleto: 'Boleto',
    cash: 'Dinheiro',
    bank_transfer: 'Transferência',
};

// Open Modals
const openCreateTransactionModal = (defaultType = 'expense') => {
    transactionForm.reset();
    transactionForm.id = null;
    transactionForm.type = defaultType;
    transactionForm.category = defaultType === 'expense' ? 'utilidades' : 'venda_produtos';
    transactionForm.title = '';
    transactionForm.amount = '';
    transactionForm.due_date = new Date().toISOString().split('T')[0];
    transactionForm.paid_at = '';
    transactionForm.status = 'pending';
    transactionForm.payment_method = 'pix';
    transactionForm.team_member_id = null;
    transactionForm.notes = '';

    isEditingTransaction.value = false;
    showTransactionModal.value = true;
};

const openEditTransactionModal = (t) => {
    transactionForm.id = t.id;
    transactionForm.type = t.type;
    transactionForm.category = t.category;
    transactionForm.title = t.title;
    transactionForm.description = t.description || '';
    transactionForm.amount = t.amount;
    transactionForm.due_date = t.due_date;
    transactionForm.paid_at = t.paid_at || '';
    transactionForm.status = t.status;
    transactionForm.payment_method = t.payment_method || '';
    transactionForm.team_member_id = t.team_member_id || null;
    transactionForm.notes = t.notes || '';

    isEditingTransaction.value = true;
    showTransactionModal.value = true;
};

const submitTransactionForm = () => {
    if (isEditingTransaction.value) {
        transactionForm.put(route('admin.financial.transactions.update', transactionForm.id), {
            onSuccess: () => {
                showTransactionModal.value = false;
            },
        });
    } else {
        transactionForm.post(route('admin.financial.transactions.store'), {
            onSuccess: () => {
                showTransactionModal.value = false;
            },
        });
    }
};

const toggleStatus = (transaction) => {
    router.patch(route('admin.financial.transactions.toggle-status', transaction.id), {}, {
        preserveScroll: true,
    });
};

const openDeleteTransactionModal = (t) => {
    transactionToDelete.value = t;
    showDeleteTransactionModal.value = true;
};

const confirmDeleteTransaction = () => {
    if (!transactionToDelete.value) return;
    deleteForm.delete(route('admin.financial.transactions.destroy', transactionToDelete.value.id), {
        onSuccess: () => {
            showDeleteTransactionModal.value = false;
            transactionToDelete.value = null;
        },
    });
};

// CSV Exports
const exportCashflowToCSV = () => {
    if (!hasPermission('reports.export')) return;
    const rows = [
        ["Tipo", "Categoria", "Descricao", "Data Vencimento", "Data Pagamento", "Forma Pagamento", "Status", "Valor (R$)"]
    ];

    props.transactions.forEach(t => {
        rows.push([
            t.type === 'expense' ? 'Despesa/Saida' : 'Receita/Entrada',
            categoryLabels[t.category] || t.category,
            t.title,
            t.due_date,
            t.paid_at || '-',
            paymentMethodLabels[t.payment_method] || t.payment_method || '-',
            t.computed_status === 'paid' ? 'Pago' : (t.computed_status === 'overdue' ? 'Vencido' : 'Pendente'),
            `R$ ${formatCurrency(t.amount)}`
        ]);
    });

    let csvContent = "data:text/csv;charset=utf-8,\uFEFF"
        + rows.map(e => e.map(val => `"${String(val).replace(/"/g, '""')}"`).join(",")).join("\n");

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `fluxo_de_caixa_${props.filters?.period || 'completo'}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const exportAppointmentsToCSV = () => {
    if (!hasPermission('reports.export')) return;
    const rows = [
        ["Data/Horario", "Cliente", "Servico", "Profissional", "Valor do Servico", "Comissao (%)", "Repasse Profissional", "Status"]
    ];

    props.history.forEach(row => {
        rows.push([
            `${row.date} ${row.time}`,
            row.client_name,
            row.service_name,
            row.professional_name,
            `R$ ${formatCurrency(row.price)}`,
            `${formatPercent(row.rate)}%`,
            `R$ ${formatCurrency(row.earned)}`,
            row.status
        ]);
    });

    let csvContent = "data:text/csv;charset=utf-8,\uFEFF"
        + rows.map(e => e.map(val => `"${String(val).replace(/"/g, '""')}"`).join(",")).join("\n");

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "historico_faturamento_atendimentos.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <AdminLayout>
        <Head title="Painel Financeiro & Fluxo de Caixa - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">
                    Gestão Financeira & Caixa
                </h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Contas a pagar, despesas operacionais, fluxo de caixa e comissões</p>
        </template>

        <div class="space-y-6">
            
            <!-- Top Controls: Period Filters & Main Actions -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black tracking-tight" style="color: var(--text-heading);">
                        Painel de Controle Financeiro
                    </h2>
                    <p class="text-xs sm:text-sm opacity-70 mt-0.5">
                        Monitore suas receitas, pague contas, controle despesas fixas/variáveis e acompanhe o lucro real.
                    </p>
                </div>

                <!-- Right Action & Period Selection -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Period Filter Pills -->
                    <div class="p-1 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center gap-1">
                        <button
                            type="button"
                            @click="setPeriod('this_month')"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer',
                                filters.period === 'this_month'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            Este Mês
                        </button>
                        <button
                            type="button"
                            @click="setPeriod('last_month')"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer',
                                filters.period === 'last_month'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            Mês Anterior
                        </button>
                        <button
                            type="button"
                            @click="setPeriod('last_30_days')"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer',
                                filters.period === 'last_30_days'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            30 Dias
                        </button>
                        <button
                            type="button"
                            @click="setPeriod('all')"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer',
                                filters.period === 'all'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            Todos
                        </button>
                    </div>

                    <!-- New Transaction Button -->
                    <button
                        v-if="!teamMember"
                        type="button"
                        @click="openCreateTransactionModal('expense')"
                        class="btn btn-primary py-2 px-4 text-xs font-black rounded-xl shadow-lg shadow-indigo-600/30 flex items-center gap-1.5 cursor-pointer"
                    >
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Novo Lançamento</span>
                    </button>
                </div>
            </div>

            <!-- Professional-Only View -->
            <template v-if="teamMember">
                <!-- Professional KPIs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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

                <!-- Professional History Table -->
                <div class="card p-0 overflow-hidden mt-6 shadow-sm">
                    <div class="p-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
                        <h4 class="font-black text-xs sm:text-sm" style="color: var(--text-heading);">Detalhamento de Atendimentos & Ganhos</h4>
                        <button v-if="hasPermission('reports.export')" type="button" @click="exportAppointmentsToCSV" class="btn btn-outline py-1.5 px-3 text-xs flex items-center gap-1.5 rounded-xl hover:text-emerald-500 hover:border-emerald-500">
                            <i class="fa-solid fa-file-csv text-emerald-500"></i>
                            <span>Exportar CSV</span>
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th>Data / Hora</th>
                                    <th>Cliente</th>
                                    <th>Serviço</th>
                                    <th>Valor do Serviço</th>
                                    <th>% Comissão</th>
                                    <th class="text-right">Seu Ganho</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="history.length > 0">
                                    <tr v-for="(row, idx) in history" :key="idx">
                                        <td>
                                            <span class="font-bold text-xs opacity-80">{{ row.date }} às {{ row.time }}</span>
                                        </td>
                                        <td>
                                            <span class="font-semibold">{{ row.client_name }}</span>
                                        </td>
                                        <td>
                                            <span class="font-medium text-xs">{{ row.service_name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs">R$ {{ formatCurrency(row.price) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-mono">{{ formatPercent(row.rate) }}%</span>
                                        </td>
                                        <td class="text-right">
                                            <strong class="text-emerald-600 dark:text-emerald-400 font-black">R$ {{ formatCurrency(row.earned) }}</strong>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="6" class="text-center py-12 opacity-60 text-xs">Nenhum atendimento encontrado no período selecionado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <!-- Admin Full Multi-Tab Financial Management -->
            <template v-else>
                <!-- 4 Executive Financial KPIs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
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

                <!-- Navigation Tabs Bar -->
                <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 overflow-x-auto">
                    <button
                        type="button"
                        @click="activeTab = 'cashflow'"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer',
                            activeTab === 'cashflow'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800'
                        ]"
                    >
                        <i class="fa-solid fa-chart-pie text-xs"></i>
                        <span>1. Resumo & Fluxo de Caixa</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'expenses'"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer',
                            activeTab === 'expenses'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800'
                        ]"
                    >
                        <i class="fa-solid fa-file-invoice-dollar text-xs"></i>
                        <span>2. Contas a Pagar & Despesas</span>
                        <span v-if="expensesList.length > 0" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500/20 text-rose-600 dark:text-rose-400">
                            {{ expensesList.length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'incomes'"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer',
                            activeTab === 'incomes'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800'
                        ]"
                    >
                        <i class="fa-solid fa-cash-register text-xs"></i>
                        <span>3. Entradas & Faturamento</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                            {{ history.length + extraIncomesList.length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'commissions'"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer',
                            activeTab === 'commissions'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-800'
                        ]"
                    >
                        <i class="fa-solid fa-users text-xs"></i>
                        <span>4. Comissões da Equipe</span>
                    </button>
                </div>

                <!-- TAB 1: Fluxo de Caixa / DRE Simplificado -->
                <div v-show="activeTab === 'cashflow'" class="space-y-6">
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
                                        @click="openCreateTransactionModal('expense')"
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
                                        @click="openCreateTransactionModal('income')"
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
                                        @click="exportCashflowToCSV"
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
                                    @click="activeTab = 'expenses'"
                                    class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline inline-flex items-center gap-1 cursor-pointer"
                                >
                                    <span>Ver contas a pagar</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Contas a Pagar & Despesas -->
                <div v-show="activeTab === 'expenses'" class="space-y-4">
                    <div class="card p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Filter by status -->
                            <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                                <button
                                    type="button"
                                    @click="expenseStatusFilter = 'all'"
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
                                    @click="expenseStatusFilter = 'pending'"
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
                                    @click="expenseStatusFilter = 'overdue'"
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
                                    @click="expenseStatusFilter = 'paid'"
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
                                @click="openCreateTransactionModal('expense')"
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
                                                <!-- Status Badge with 1-click toggle -->
                                                <button
                                                    type="button"
                                                    @click="toggleStatus(t)"
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
                                                        @click="openEditTransactionModal(t)"
                                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all cursor-pointer"
                                                        title="Editar despesa"
                                                    >
                                                        <i class="fa-solid fa-pen text-xs"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="openDeleteTransactionModal(t)"
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

                <!-- TAB 3: Entradas & Faturamento -->
                <div v-show="activeTab === 'incomes'" class="space-y-4">
                    <div class="card p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <h4 class="font-extrabold text-sm" style="color: var(--text-heading);">
                                Receitas de Agendamentos & Vendas Avulsas
                            </h4>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="openCreateTransactionModal('income')"
                                class="btn btn-outline py-1.5 px-3 text-xs font-bold rounded-xl inline-flex items-center gap-1.5 cursor-pointer hover:border-emerald-500 hover:text-emerald-600"
                            >
                                <i class="fa-solid fa-plus text-xs text-emerald-500"></i>
                                <span>Lançar Entrada Avulsa</span>
                            </button>
                            <button
                                v-if="hasPermission('reports.export')"
                                type="button"
                                @click="exportAppointmentsToCSV"
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

                <!-- TAB 4: Comissões & Repasses da Equipe -->
                <div v-show="activeTab === 'commissions'" class="space-y-4">
                    <div class="card p-0 overflow-hidden shadow-sm">
                        <div class="p-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
                            <div>
                                <h4 class="font-black text-xs sm:text-sm" style="color: var(--text-heading);">Tabela Consolidada de Repasse por Profissional</h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">Valores calculados automaticamente com base nas taxas de comissão e atendimentos do período</p>
                            </div>
                            <button v-if="hasPermission('reports.export')" type="button" @click="exportAppointmentsToCSV" class="btn btn-outline py-1.5 px-3 text-xs flex items-center gap-1.5 rounded-xl hover:text-emerald-500 hover:border-emerald-500">
                                <i class="fa-solid fa-file-csv text-emerald-500"></i>
                                <span>Exportar CSV</span>
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="min-w-full">
                                <thead>
                                    <tr>
                                        <th>Profissional</th>
                                        <th>Cargo</th>
                                        <th>Atendimentos</th>
                                        <th>Faturamento Gerado</th>
                                        <th>Comissão Padrão</th>
                                        <th class="text-right">Total a Repassar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="stats.members_data && stats.members_data.length > 0">
                                        <tr v-for="(data, idx) in stats.members_data" :key="idx">
                                            <td>
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 border flex items-center justify-center shrink-0">
                                                        <img v-if="data.member?.avatar_url" :src="data.member.avatar_url" alt="" class="w-full h-full object-cover">
                                                        <div v-else class="w-full h-full bg-indigo-600 flex items-center justify-center text-white text-[11px] font-bold">
                                                            {{ getInitials(data.member?.name) }}
                                                        </div>
                                                    </div>
                                                    <span class="font-bold text-xs sm:text-sm" style="color: var(--text-heading);">{{ data.member?.name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ data.member?.job_title ?? 'Profissional' }}</span>
                                            </td>
                                            <td>
                                                <span class="text-xs font-medium">{{ data.count }} serviços</span>
                                            </td>
                                            <td>
                                                <span class="text-xs font-mono">R$ {{ formatCurrency(data.revenue) }}</span>
                                            </td>
                                            <td>
                                                <span class="text-xs font-mono font-bold">{{ formatPercent(data.rate) }}%</span>
                                            </td>
                                            <td class="text-right">
                                                <strong class="text-rose-600 dark:text-rose-400 font-mono font-black text-sm">R$ {{ formatCurrency(data.commission) }}</strong>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="6" class="text-center py-10 opacity-60 text-xs">Nenhum profissional com atendimento no período.</td>
                                    </tr>
                                    <tr v-if="stats.unassigned_revenue > 0" class="bg-slate-50/50 dark:bg-slate-900/30">
                                        <td>
                                            <span class="font-bold italic text-slate-500">Sem profissional vinculado</span>
                                        </td>
                                        <td>
                                            <span class="text-xs italic opacity-60">Faturamento Direto</span>
                                        </td>
                                        <td>
                                            <span class="text-xs opacity-60">-</span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-mono">R$ {{ formatCurrency(stats.unassigned_revenue) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs opacity-60">0.00%</span>
                                        </td>
                                        <td class="text-right">
                                            <strong class="text-slate-500 font-mono font-bold">R$ 0,00</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Modals -->
        <TransactionModal
            :show="showTransactionModal"
            :is-editing="isEditingTransaction"
            :form="transactionForm"
            :team-members="teamMembers"
            @close="showTransactionModal = false"
            @submit="submitTransactionForm"
        />

        <TransactionDeleteModal
            :show="showDeleteTransactionModal"
            :transaction="transactionToDelete"
            :processing="deleteForm.processing"
            @close="showDeleteTransactionModal = false"
            @confirm="confirmDeleteTransaction"
        />
    </AdminLayout>
</template>
