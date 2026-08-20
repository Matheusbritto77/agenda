<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    paymentLoading: {
        type: Boolean,
        default: false,
    },
    paymentDetails: {
        type: Object,
        default: null,
    },
    paymentStatus: {
        type: String,
        default: 'pending',
    },
    selectedService: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'fallback-store']);

const copied = ref(false);

const copyPixCode = () => {
    if (props.paymentDetails?.pix_copy_paste) {
        navigator.clipboard.writeText(props.paymentDetails.pix_copy_paste);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 3000);
    }
};

const formatCurrency = (val) => Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[99999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
            @click.self="$emit('close')"
        >
            <div class="liquid-glass-card w-full max-w-md p-6 sm:p-7 space-y-5 text-center relative" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b" style="border-color: var(--border);">
                    <h3 class="text-base font-extrabold" style="color: var(--text-heading);">Pagamento PIX Instantâneo</h3>
                    <button type="button" @click="$emit('close')" class="w-8 h-8 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <div v-if="paymentLoading" class="py-12 flex flex-col items-center justify-center space-y-3">
                    <i class="fa-solid fa-spinner fa-spin text-3xl text-indigo-600"></i>
                    <p class="text-xs text-slate-500">Gerando QR Code PIX...</p>
                </div>

                <div v-else class="space-y-4">
                    <div class="bg-indigo-500/10 border border-indigo-500/20 p-3.5 rounded-xl text-left">
                        <span class="text-xs text-slate-500">Total a pagar:</span>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">R$ {{ formatCurrency(selectedService?.price) }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Serviço: {{ selectedService?.name }}</p>
                    </div>

                    <div v-if="paymentDetails?.pix_qr_code_base64" class="flex justify-center">
                        <div class="p-3 bg-white rounded-2xl border border-slate-200 inline-block shadow-sm">
                            <img :src="`data:image/png;base64,${paymentDetails.pix_qr_code_base64}`" class="w-44 h-44 block" alt="QR Code Pix" />
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="copyPixCode"
                        class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-bold text-xs bg-indigo-600 hover:bg-indigo-700 text-white transition-all shadow-md cursor-pointer"
                    >
                        <i class="fa-solid fa-copy"></i>
                        <span>{{ copied ? 'Código PIX Copiado!' : 'Copiar Código PIX (Copia e Cola)' }}</span>
                    </button>

                    <div class="flex items-center justify-center gap-2 text-xs">
                        <span v-if="paymentStatus === 'pending'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 animate-pulse font-semibold">
                            <i class="fa-solid fa-clock text-xs"></i>
                            Aguardando pagamento...
                        </span>
                        <span v-else-if="paymentStatus === 'approved'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold">
                            <i class="fa-solid fa-circle-check text-xs"></i>
                            Pagamento Aprovado!
                        </span>
                    </div>

                    <div class="pt-2 border-t" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="$emit('fallback-store')"
                            class="w-full py-2 px-4 rounded-xl font-semibold text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition-all cursor-pointer"
                        >
                            Pagar no Estabelecimento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
