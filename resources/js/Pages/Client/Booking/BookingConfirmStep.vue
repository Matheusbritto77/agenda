<script setup>
defineProps({
    activeProfessional: {
        type: Object,
        default: null,
    },
    selectedService: {
        type: Object,
        default: null,
    },
    selectedDate: {
        type: String,
        default: '',
    },
    selectedTime: {
        type: String,
        default: '',
    },
    bookingForm: {
        type: Object,
        required: true,
    },
    paymentEnabled: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['prev-step', 'submit-booking']);

const formatDateLong = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
};

const formatCurrency = (val) => Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <div class="space-y-6">
        <!-- Summary Box -->
        <div class="card p-5 sm:p-6 bg-gradient-to-tr from-indigo-500/5 to-cyan-500/5 border-indigo-500/20 shadow-sm space-y-4">
            <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Resumo da sua Reserva</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs sm:text-sm">
                <div v-if="activeProfessional" class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase text-slate-400">Profissional</span>
                    <p class="font-extrabold text-indigo-600 dark:text-indigo-400">{{ activeProfessional.name }}</p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase text-slate-400">Serviço</span>
                    <p class="font-extrabold text-slate-900 dark:text-white">{{ selectedService?.name }}</p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase text-slate-400">Data</span>
                    <p class="font-bold text-slate-700 dark:text-slate-200 capitalize">{{ formatDateLong(selectedDate) }}</p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase text-slate-400">Horário</span>
                    <p class="font-bold text-slate-700 dark:text-slate-200">{{ selectedTime }} ({{ selectedService?.duration_minutes || 30 }} min)</p>
                </div>

                <div class="space-y-0.5 sm:col-span-2">
                    <span class="text-[10px] font-bold uppercase text-slate-400">Valor Total</span>
                    <p class="text-base font-black text-emerald-600 dark:text-emerald-400">R$ {{ formatCurrency(selectedService?.price) }}</p>
                </div>
            </div>
        </div>

        <!-- Customer Details Form -->
        <form @submit.prevent="$emit('submit-booking', paymentEnabled)" class="card p-5 sm:p-6 shadow-sm space-y-4">
            <h4 class="text-sm font-extrabold" style="color: var(--text-heading);">Seus Dados para Contato</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group mb-0">
                    <label class="form-label text-xs" for="form_client_name">Nome Completo *</label>
                    <input
                        type="text"
                        id="form_client_name"
                        v-model="bookingForm.client_name"
                        class="form-control text-xs sm:text-sm rounded-xl"
                        placeholder="Ex: Maria da Silva"
                        required
                    />
                    <span v-if="bookingForm.errors?.client_name" class="text-xs text-rose-500 mt-1 block">{{ bookingForm.errors.client_name }}</span>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label text-xs" for="form_client_email">E-mail *</label>
                    <input
                        type="email"
                        id="form_client_email"
                        v-model="bookingForm.client_email"
                        class="form-control text-xs sm:text-sm rounded-xl"
                        placeholder="seu@email.com"
                        required
                    />
                    <span v-if="bookingForm.errors?.client_email" class="text-xs text-rose-500 mt-1 block">{{ bookingForm.errors.client_email }}</span>
                </div>

                <div class="form-group mb-0 md:col-span-2">
                    <label class="form-label text-xs" for="form_client_phone">Telefone / WhatsApp *</label>
                    <input
                        type="tel"
                        id="form_client_phone"
                        v-model="bookingForm.client_phone"
                        class="form-control text-xs sm:text-sm rounded-xl"
                        placeholder="(11) 99999-8888"
                        required
                    />
                    <span v-if="bookingForm.errors?.client_phone" class="text-xs text-rose-500 mt-1 block">{{ bookingForm.errors.client_phone }}</span>
                </div>

                <div class="form-group mb-0 md:col-span-2">
                    <label class="form-label text-xs" for="form_notes">Observações (opcional)</label>
                    <textarea
                        id="form_notes"
                        v-model="bookingForm.notes"
                        rows="2"
                        class="form-control text-xs sm:text-sm rounded-xl"
                        placeholder="Alguma preferência ou informação relevante para o profissional?"
                    ></textarea>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between gap-3 pt-4 border-t" style="border-color: var(--border);">
                <button
                    type="button"
                    @click="$emit('prev-step')"
                    class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl"
                >
                    <i class="fa-solid fa-arrow-left text-xs mr-1"></i>
                    Voltar
                </button>

                <div class="flex items-center gap-2">
                    <button
                        v-if="paymentEnabled"
                        type="button"
                        @click="$emit('submit-booking', false)"
                        :disabled="bookingForm.processing"
                        class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl"
                    >
                        Agendar sem Pagar
                    </button>

                    <button
                        type="submit"
                        :disabled="bookingForm.processing"
                        class="btn btn-primary py-2.5 px-6 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30"
                    >
                        <i v-if="bookingForm.processing" class="fa-solid fa-spinner fa-spin text-xs mr-1"></i>
                        <i v-else :class="['fa-solid text-xs mr-1', paymentEnabled ? 'fa-wallet' : 'fa-calendar-check']"></i>
                        <span>{{ bookingForm.processing ? 'Processando...' : (paymentEnabled ? 'Pagar e Confirmar' : 'Confirmar Agendamento') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>
