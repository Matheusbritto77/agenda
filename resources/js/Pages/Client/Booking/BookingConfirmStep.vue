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
    title: {
        type: String,
        default: 'Dados & Confirmação',
    },
    confirmButtonLabel: {
        type: String,
        default: 'Confirmar Agendamento',
    },
    showNotes: {
        type: Boolean,
        default: true,
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
        <div class="card p-5 sm:p-6 border space-y-4 shadow-sm" :style="{ backgroundColor: 'var(--primary-light)', borderColor: 'var(--primary)' }">
            <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider" :style="{ color: 'var(--primary)' }">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Resumo da sua Reserva</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs sm:text-sm">
                <div v-if="activeProfessional" class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase opacity-60" :style="{ color: 'var(--text-muted)' }">Profissional</span>
                    <p class="font-extrabold" :style="{ color: 'var(--primary)' }">{{ activeProfessional.name }}</p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase opacity-60" :style="{ color: 'var(--text-muted)' }">Serviço</span>
                    <p class="font-extrabold" :style="{ color: 'var(--text-heading)' }">{{ selectedService?.name }}</p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase opacity-60" :style="{ color: 'var(--text-muted)' }">Data</span>
                    <p class="font-bold capitalize" :style="{ color: 'var(--text)' }">{{ formatDateLong(selectedDate) }}</p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase opacity-60" :style="{ color: 'var(--text-muted)' }">Horário</span>
                    <p class="font-bold" :style="{ color: 'var(--text)' }">{{ selectedTime }} ({{ selectedService?.duration_minutes || 30 }} min)</p>
                </div>

                <div class="space-y-0.5 sm:col-span-2">
                    <span class="text-[10px] font-bold uppercase opacity-60" :style="{ color: 'var(--text-muted)' }">Valor Total</span>
                    <p class="text-base font-black" :style="{ color: 'var(--primary)' }">R$ {{ formatCurrency(selectedService?.price) }}</p>
                </div>
            </div>
        </div>

        <!-- Customer Details Form -->
        <form @submit.prevent="$emit('submit-booking', paymentEnabled)" class="card p-5 sm:p-6 shadow-sm space-y-4">
            <h4 class="text-sm font-extrabold" :style="{ color: 'var(--text-heading)' }">
                {{ title }}
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group mb-0">
                    <label class="form-label text-xs font-bold block mb-1" for="form_client_name" :style="{ color: 'var(--text-heading)' }">Nome Completo *</label>
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
                    <label class="form-label text-xs font-bold block mb-1" for="form_client_email" :style="{ color: 'var(--text-heading)' }">E-mail *</label>
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
                    <label class="form-label text-xs font-bold block mb-1" for="form_client_phone" :style="{ color: 'var(--text-heading)' }">Telefone / WhatsApp *</label>
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

                <div v-if="showNotes" class="form-group mb-0 md:col-span-2">
                    <label class="form-label text-xs font-bold block mb-1" for="form_notes" :style="{ color: 'var(--text-heading)' }">Observações (opcional)</label>
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
            <div class="flex items-center justify-between gap-3 pt-4 border-t" :style="{ borderColor: 'var(--border, #e2e8f0)' }">
                <button
                    type="button"
                    @click="$emit('prev-step')"
                    class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl cursor-pointer"
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
                        class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl cursor-pointer"
                    >
                        Agendar sem Pagar
                    </button>

                    <button
                        type="submit"
                        :disabled="bookingForm.processing"
                        class="btn btn-primary py-2.5 px-6 text-xs font-bold rounded-xl shadow-md cursor-pointer"
                        :style="{
                            backgroundColor: 'var(--primary)',
                            color: 'var(--btn-text, #ffffff)'
                        }"
                    >
                        <i v-if="bookingForm.processing" class="fa-solid fa-spinner fa-spin text-xs mr-1"></i>
                        <i v-else :class="['fa-solid text-xs mr-1', paymentEnabled ? 'fa-wallet' : 'fa-calendar-check']"></i>
                        <span>{{ bookingForm.processing ? 'Processando...' : (paymentEnabled ? 'Pagar e Confirmar' : confirmButtonLabel) }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>
