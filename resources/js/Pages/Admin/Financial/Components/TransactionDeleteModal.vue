<script setup>
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    transaction: {
        type: Object,
        default: null,
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};

const formatCurrency = (val) => {
    return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div class="liquid-glass-card w-full max-w-md p-6 space-y-5 relative shadow-2xl" @click.stop>
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold" style="color: var(--text-heading);">
                            Excluir Lançamento Financeiro
                        </h3>
                        <p class="text-xs opacity-60">Esta ação não pode ser desfeita.</p>
                    </div>
                </div>

                <div v-if="transaction" class="p-3.5 rounded-xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-700 dark:text-slate-200">{{ transaction.title }}</span>
                        <span class="font-black text-rose-600 dark:text-rose-400">R$ {{ formatCurrency(transaction.amount) }}</span>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        Vencimento: {{ transaction.due_date }}
                    </div>
                </div>

                <div class="pt-3 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="btn btn-outline py-2 px-4 text-xs font-bold rounded-xl cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="$emit('confirm')"
                        class="btn bg-rose-600 hover:bg-rose-700 text-white py-2 px-5 text-xs font-black rounded-xl shadow-lg shadow-rose-600/30 inline-flex items-center gap-1.5 cursor-pointer"
                        :disabled="processing"
                    >
                        <i v-if="processing" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                        <i v-else class="fa-solid fa-trash text-xs"></i>
                        <span>{{ processing ? 'Excluindo...' : 'Confirmar Exclusão' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
