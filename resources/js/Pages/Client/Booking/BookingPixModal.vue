<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';

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
    customerName: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close']);

const copied = ref(false);
const timeLeftFormatted = ref('15:00');
let timerInterval = null;

const copyPixCode = () => {
    const code = props.paymentDetails?.pix_copy_paste || props.paymentDetails?.pix_qr_code;
    if (code) {
        navigator.clipboard.writeText(code);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 3000);
    }
};

const formatCurrency = (val) => Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const startCountdown = () => {
    clearInterval(timerInterval);
    let secondsLeft = 15 * 60; // 15 minutes
    
    if (props.paymentDetails?.expires_at) {
        const exp = new Date(props.paymentDetails.expires_at).getTime();
        const now = Date.now();
        const diff = Math.max(0, Math.floor((exp - now) / 1000));
        if (diff > 0 && diff < 3600) {
            secondsLeft = diff;
        }
    }

    const updateDisplay = () => {
        const m = Math.floor(secondsLeft / 60).toString().padStart(2, '0');
        const s = (secondsLeft % 60).toString().padStart(2, '0');
        timeLeftFormatted.value = `${m}:${s}`;
        if (secondsLeft <= 0) {
            clearInterval(timerInterval);
        } else {
            secondsLeft--;
        }
    };

    updateDisplay();
    timerInterval = setInterval(updateDisplay, 1000);
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        startCountdown();
    } else {
        clearInterval(timerInterval);
    }
});

onUnmounted(() => {
    clearInterval(timerInterval);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[99999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
            @click.self="$emit('close')"
        >
            <div class="liquid-glass-card w-full max-w-md p-6 sm:p-7 space-y-5 text-center relative shadow-2xl" @click.stop>
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">
                            <i class="fa-brands fa-pix"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-left" style="color: var(--text-heading);">
                            Pagamento PIX Instantâneo
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="w-8 h-8 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all cursor-pointer"
                    >
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="paymentLoading" class="py-12 flex flex-col items-center justify-center space-y-3">
                    <i class="fa-solid fa-circle-notch fa-spin text-3xl text-emerald-600"></i>
                    <p class="text-xs text-slate-500 font-medium">Gerando QR Code PIX com o Mercado Pago...</p>
                </div>

                <!-- Content State -->
                <div v-else class="space-y-4">
                    
                    <!-- Value & Service Box -->
                    <div class="bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 p-3.5 rounded-2xl text-left flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total a Pagar</span>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                                R$ {{ formatCurrency(paymentDetails?.amount || selectedService?.price) }}
                            </p>
                            <p class="text-[11px] text-slate-500 mt-0.5">{{ selectedService?.name }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 font-bold block">Expira em:</span>
                            <span class="text-xs font-mono font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md">
                                {{ timeLeftFormatted }}
                            </span>
                        </div>
                    </div>

                    <!-- QR Code Display -->
                    <div v-if="paymentDetails?.pix_qr_code_base64" class="flex justify-center my-2">
                        <div class="p-3.5 bg-white rounded-2xl border-2 border-emerald-500/30 shadow-md inline-block">
                            <img :src="`data:image/png;base64,${paymentDetails.pix_qr_code_base64}`" class="w-44 h-44 block object-contain select-none" alt="QR Code Pix" />
                        </div>
                    </div>

                    <!-- Copy Paste Button -->
                    <div class="space-y-1.5">
                        <button
                            type="button"
                            @click="copyPixCode"
                            class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-black text-xs bg-emerald-600 hover:bg-emerald-700 text-white transition-all shadow-lg shadow-emerald-600/30 cursor-pointer"
                        >
                            <i :class="copied ? 'fa-solid fa-check' : 'fa-solid fa-copy'"></i>
                            <span>{{ copied ? 'Código PIX Copiado com Sucesso!' : 'Copiar Código PIX (Copia e Cola)' }}</span>
                        </button>
                        <p class="text-[11px] text-slate-400">Abra o app do seu banco e cole o código no Pix Copia e Cola</p>
                    </div>

                    <!-- Live Real-Time Detection Indicator -->
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2 text-xs">
                        <template v-if="paymentStatus === 'approved'">
                            <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-black">
                                <i class="fa-solid fa-circle-check text-base animate-bounce"></i>
                                <span>Pagamento Confirmado! Finalizando agendamento...</span>
                            </span>
                        </template>
                        <template v-else>
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                            </span>
                            <span class="text-slate-600 dark:text-slate-300 font-bold">
                                Aguardando confirmação do pagamento...
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
