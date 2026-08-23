<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    form: {
        type: Object,
        required: true,
    },
    teamMembers: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'submit']);

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};

const expenseCategories = [
    { value: 'aluguel', label: 'Aluguel & Condomínio', icon: 'fa-solid fa-building' },
    { value: 'utilidades', label: 'Contas (Luz, Água, Internet)', icon: 'fa-solid fa-bolt' },
    { value: 'fornecedores', label: 'Fornecedores & Produtos', icon: 'fa-solid fa-boxes-stacked' },
    { value: 'marketing', label: 'Marketing & Tráfego Pago', icon: 'fa-solid fa-bullhorn' },
    { value: 'pessoal', label: 'Equipe & Pró-labore', icon: 'fa-solid fa-users' },
    { value: 'impostos', label: 'Impostos & Taxas Bancárias', icon: 'fa-solid fa-receipt' },
    { value: 'manutencao', label: 'Manutenção & Equipamentos', icon: 'fa-solid fa-wrench' },
    { value: 'outros', label: 'Outras Despesas', icon: 'fa-solid fa-ellipsis' },
];

const incomeCategories = [
    { value: 'venda_produtos', label: 'Venda de Produtos no Balcão', icon: 'fa-solid fa-cart-shopping' },
    { value: 'cursos', label: 'Cursos & Treinamentos', icon: 'fa-solid fa-graduation-cap' },
    { value: 'consultoria', label: 'Consultoria / Parceria', icon: 'fa-solid fa-handshake' },
    { value: 'outros', label: 'Outras Entradas', icon: 'fa-solid fa-money-bill-wave' },
];

const activeCategories = computed(() => {
    return props.form.type === 'expense' ? expenseCategories : incomeCategories;
});

const paymentMethods = [
    { value: 'pix', label: 'Pix', icon: 'fa-brands fa-pix' },
    { value: 'credit_card', label: 'Cartão de Crédito', icon: 'fa-regular fa-credit-card' },
    { value: 'debit_card', label: 'Cartão de Débito', icon: 'fa-solid fa-credit-card' },
    { value: 'boleto', label: 'Boleto Bancário', icon: 'fa-solid fa-barcode' },
    { value: 'cash', label: 'Dinheiro em Espécie', icon: 'fa-solid fa-money-bill' },
    { value: 'bank_transfer', label: 'Transferência Bancária', icon: 'fa-solid fa-building-columns' },
];

const setType = (type) => {
    props.form.type = type;
    if (type === 'expense' && !expenseCategories.some(c => c.value === props.form.category)) {
        props.form.category = 'utilidades';
    } else if (type === 'income' && !incomeCategories.some(c => c.value === props.form.category)) {
        props.form.category = 'venda_produtos';
    }
};

const handleStatusChange = () => {
    if (props.form.status === 'paid' && !props.form.paid_at) {
        const today = new Date().toISOString().split('T')[0];
        props.form.paid_at = today;
    } else if (props.form.status === 'pending') {
        props.form.paid_at = '';
    }
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 sm:p-6 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div class="liquid-glass-card w-full max-w-3xl p-6 sm:p-8 space-y-6 relative shadow-2xl max-h-[90vh] overflow-y-auto" @click.stop>
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-3.5">
                        <div
                            :class="[
                                'w-12 h-12 rounded-2xl text-white flex items-center justify-center font-bold text-xl shadow-lg shrink-0',
                                form.type === 'expense'
                                    ? 'bg-gradient-to-tr from-rose-600 to-rose-700 shadow-rose-600/30'
                                    : 'bg-gradient-to-tr from-emerald-600 to-teal-700 shadow-emerald-600/30'
                            ]"
                        >
                            <i :class="form.type === 'expense' ? 'fa-solid fa-arrow-trend-down' : 'fa-solid fa-arrow-trend-up'"></i>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-xl font-black tracking-tight" style="color: var(--text-heading);">
                                {{ isEditing ? 'Editar Lançamento Financeiro' : 'Novo Lançamento Financeiro' }}
                            </h3>
                            <p class="text-xs opacity-60 mt-0.5">Registre despesas, contas a pagar ou receitas extras do estabelecimento</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all cursor-pointer"
                    >
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="$emit('submit')" class="space-y-5">
                    
                    <!-- Row 1: Type Selection (Expense vs Extra Income) -->
                    <div class="space-y-1.5">
                        <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                            Tipo de Movimentação <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3 p-1.5 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                            <button
                                type="button"
                                @click="setType('expense')"
                                :class="[
                                    'py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 cursor-pointer',
                                    form.type === 'expense'
                                        ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                ]"
                            >
                                <i class="fa-solid fa-arrow-down text-xs"></i>
                                <span>Despesa / Conta a Pagar (Saída)</span>
                            </button>
                            <button
                                type="button"
                                @click="setType('income')"
                                :class="[
                                    'py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 cursor-pointer',
                                    form.type === 'income'
                                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                ]"
                            >
                                <i class="fa-solid fa-arrow-up text-xs"></i>
                                <span>Receita Avulsa / Venda Balcão (Entrada)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Row 2: Title & Category (Horizontal 2 Columns) -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <!-- Title -->
                        <div class="sm:col-span-7 space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                Descrição / Nome da Conta <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.title"
                                class="form-control text-xs sm:text-sm rounded-xl font-semibold"
                                :placeholder="form.type === 'expense' ? 'Ex: Conta de Luz Enel, Aluguel do Salão, Produtos L\'Oréal' : 'Ex: Venda de Pomada Modeladora, Workshop de Barba'"
                                required
                            />
                            <span v-if="form.errors?.title" class="text-xs text-rose-500 font-bold block mt-1">
                                {{ form.errors.title }}
                            </span>
                        </div>

                        <!-- Category -->
                        <div class="sm:col-span-5 space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                Categoria <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.category"
                                class="form-control text-xs sm:text-sm rounded-xl font-bold"
                                required
                            >
                                <option v-for="cat in activeCategories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                            <span v-if="form.errors?.category" class="text-xs text-rose-500 font-bold block mt-1">
                                {{ form.errors.category }}
                            </span>
                        </div>
                    </div>

                    <!-- Row 3: Amount & Due Date & Status (Horizontal 3 Columns) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Amount -->
                        <div class="space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                Valor (R$) <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-stretch rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-indigo-500/30 focus-within:border-indigo-500 overflow-hidden transition-all">
                                <span class="px-3 py-2 text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/80 border-r border-slate-200 dark:border-slate-700 flex items-center justify-center select-none">
                                    R$
                                </span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    v-model="form.amount"
                                    class="w-full bg-transparent border-0 px-3 py-2 text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 focus:ring-0 focus:outline-none"
                                    placeholder="150.00"
                                    required
                                />
                            </div>
                            <span v-if="form.errors?.amount" class="text-xs text-rose-500 font-bold block mt-1">
                                {{ form.errors.amount }}
                            </span>
                        </div>

                        <!-- Due Date -->
                        <div class="space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                <i class="fa-regular fa-calendar text-indigo-500 mr-1 text-[11px]"></i>
                                Data de Vencimento <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="date"
                                v-model="form.due_date"
                                class="form-control text-xs sm:text-sm rounded-xl font-bold"
                                required
                            />
                            <span v-if="form.errors?.due_date" class="text-xs text-rose-500 font-bold block mt-1">
                                {{ form.errors.due_date }}
                            </span>
                        </div>

                        <!-- Status -->
                        <div class="space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                Situação / Status <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.status"
                                @change="handleStatusChange"
                                class="form-control text-xs sm:text-sm rounded-xl font-bold"
                                required
                            >
                                <option value="pending">⏳ Pendente (A Pagar)</option>
                                <option value="paid">✅ Pago / Liquidado</option>
                                <option value="cancelled">🚫 Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 4: Payment Method & Payment Date (if paid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Payment Method -->
                        <div class="space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                Forma de Pagamento
                            </label>
                            <select
                                v-model="form.payment_method"
                                class="form-control text-xs sm:text-sm rounded-xl font-semibold"
                            >
                                <option value="">Não especificado</option>
                                <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                                    {{ method.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Payment Date (Active if paid) -->
                        <div class="space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                <i class="fa-solid fa-calendar-check text-emerald-500 mr-1 text-[11px]"></i>
                                Data de Pagamento / Baixa
                            </label>
                            <input
                                type="date"
                                v-model="form.paid_at"
                                :disabled="form.status !== 'paid'"
                                class="form-control text-xs sm:text-sm rounded-xl font-bold"
                                :class="{ 'opacity-50': form.status !== 'paid' }"
                            />
                        </div>
                    </div>

                    <!-- Row 5: Notes & Linked Professional (Optional) -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <div v-if="teamMembers.length > 0" class="sm:col-span-5 space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                Vincular a Profissional (Opcional)
                            </label>
                            <select
                                v-model="form.team_member_id"
                                class="form-control text-xs sm:text-sm rounded-xl font-semibold"
                            >
                                <option :value="null">Geral do Estabelecimento</option>
                                <option v-for="m in teamMembers" :key="m.id" :value="m.id">
                                    {{ m.name }}
                                </option>
                            </select>
                        </div>

                        <div :class="teamMembers.length > 0 ? 'sm:col-span-7' : 'sm:col-span-12'" class="space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                Observações ou Código da Fatura (Opcional)
                            </label>
                            <input
                                type="text"
                                v-model="form.notes"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="Ex: Chave Pix, código de barras do boleto, nº da nota fiscal"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="btn btn-outline py-2.5 px-5 text-xs font-bold rounded-xl cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :class="[
                                'btn py-2.5 px-6 text-xs font-black rounded-xl shadow-lg inline-flex items-center gap-2 cursor-pointer text-white',
                                form.type === 'expense'
                                    ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-600/30'
                                    : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/30'
                            ]"
                            :disabled="form.processing"
                        >
                            <i v-if="form.processing" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                            <i v-else class="fa-solid fa-check text-xs"></i>
                            <span>{{ form.processing ? 'Salvando...' : (isEditing ? 'Salvar Alterações' : (form.type === 'expense' ? 'Salvar Despesa' : 'Salvar Receita')) }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
