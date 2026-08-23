<script setup>
import { ref, computed } from 'vue';
import { Head, usePage, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FinancialKpiCards from './Components/FinancialKpiCards.vue';
import FinancialCashflowTab from './Components/FinancialCashflowTab.vue';
import FinancialExpensesTab from './Components/FinancialExpensesTab.vue';
import FinancialIncomesTab from './Components/FinancialIncomesTab.vue';
import FinancialCommissionsTab from './Components/FinancialCommissionsTab.vue';
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

            <!-- KPIs Cards for Professional or Executive Owner -->
            <FinancialKpiCards
                :stats="stats"
                :team-member="teamMember"
                :format-currency="formatCurrency"
                :format-percent="formatPercent"
            />

            <!-- Professional-Only History View -->
            <div v-if="teamMember" class="card p-0 overflow-hidden mt-6 shadow-sm">
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

            <!-- Admin Full Multi-Tab Financial Management -->
            <template v-else>
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

                <!-- Tab 1: Fluxo de Caixa / DRE -->
                <FinancialCashflowTab
                    v-show="activeTab === 'cashflow'"
                    :stats="stats"
                    :filters="filters"
                    :format-currency="formatCurrency"
                    @open-create-transaction="openCreateTransactionModal"
                    @export-cashflow="exportCashflowToCSV"
                    @switch-tab="activeTab = $event"
                />

                <!-- Tab 2: Despesas -->
                <FinancialExpensesTab
                    v-show="activeTab === 'expenses'"
                    :expenses-list="expensesList"
                    v-model:expense-status-filter="expenseStatusFilter"
                    :category-labels="categoryLabels"
                    :payment-method-labels="paymentMethodLabels"
                    :format-currency="formatCurrency"
                    @open-create-transaction="openCreateTransactionModal"
                    @open-edit-transaction="openEditTransactionModal"
                    @open-delete-transaction="openDeleteTransactionModal"
                    @toggle-status="toggleStatus"
                />

                <!-- Tab 3: Incomes -->
                <FinancialIncomesTab
                    v-show="activeTab === 'incomes'"
                    :history="history"
                    :extra-incomes-list="extraIncomesList"
                    :category-labels="categoryLabels"
                    :has-permission="hasPermission"
                    :format-currency="formatCurrency"
                    @open-create-transaction="openCreateTransactionModal"
                    @export-appointments="exportAppointmentsToCSV"
                />

                <!-- Tab 4: Commissions -->
                <FinancialCommissionsTab
                    v-show="activeTab === 'commissions'"
                    :stats="stats"
                    :has-permission="hasPermission"
                    :get-initials="getInitials"
                    :format-currency="formatCurrency"
                    :format-percent="formatPercent"
                    @export-appointments="exportAppointmentsToCSV"
                />
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
