<script setup>
import { ref } from 'vue';

const selectedService = ref('corte-executivo');
const selectedTime = ref('14:30');
const bookingSuccess = ref(false);

const demoServices = [
    { id: 'corte-executivo', name: 'Corte Executivo & Barba', duration: '45 min', price: 'R$ 90,00' },
    { id: 'consultoria-vip', name: 'Consultoria Empresarial VIP', duration: '60 min', price: 'R$ 350,00' },
    { id: 'sessao-estetica', name: 'Sessão Estética Avançada', duration: '30 min', price: 'R$ 180,00' }
];

const demoTimeSlots = ['09:00', '10:30', '11:15', '14:30', '16:00', '17:30'];

function simulateBooking() {
    bookingSuccess.value = true;
    setTimeout(() => {
        bookingSuccess.value = false;
    }, 4000);
}
</script>

<template>
    <section id="demo" class="py-16 sm:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">
                Simulador Interativo
            </span>
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white mt-3">
                Experimente a experiência que seu cliente terá
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2">
                Simule um agendamento rápido em tempo real e veja a fluidez do fluxo.
            </p>
        </div>

        <div class="max-w-xl mx-auto card p-6 sm:p-8 space-y-6 shadow-2xl border-indigo-500/30">
            <div v-if="bookingSuccess" class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-center space-y-2 animate-pulse">
                <i class="fa-solid fa-circle-check text-2xl text-emerald-500"></i>
                <h4 class="font-bold text-sm text-emerald-700 dark:text-emerald-300">Agendamento de Demonstração Confirmado!</h4>
                <p class="text-xs text-slate-500">Assim será a confirmação instantânea para o seu cliente.</p>
            </div>

            <div v-else class="space-y-5">
                <div>
                    <label class="form-label text-xs font-bold block mb-2">1. Selecione o Serviço</label>
                    <div class="grid grid-cols-1 gap-2">
                        <button
                            v-for="s in demoServices"
                            :key="s.id"
                            type="button"
                            @click="selectedService = s.id"
                            :class="[
                                'p-3 rounded-xl border flex items-center justify-between text-left transition-all',
                                selectedService === s.id
                                    ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 shadow-xs'
                                    : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                            ]"
                        >
                            <div>
                                <p class="text-xs font-bold">{{ s.name }}</p>
                                <span class="text-[11px] opacity-70">{{ s.duration }}</span>
                            </div>
                            <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">{{ s.price }}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="form-label text-xs font-bold block mb-2">2. Escolha o Horário</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="t in demoTimeSlots"
                            :key="t"
                            type="button"
                            @click="selectedTime = t"
                            :class="[
                                'p-2.5 rounded-xl border font-bold text-xs transition-all text-center',
                                selectedTime === t
                                    ? 'border-indigo-600 bg-indigo-600 text-white shadow-md'
                                    : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                            ]"
                        >
                            {{ t }}
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="button"
                        @click="simulateBooking"
                        class="w-full py-3.5 px-6 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 shadow-lg shadow-indigo-600/30 transition-all cursor-pointer"
                    >
                        Simular Reserva
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
