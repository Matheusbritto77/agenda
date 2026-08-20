<script setup>
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

const page = usePage();

const bookingForm = useForm({
    service_id: '',
    client_name: '',
    client_email: '',
    client_phone: '',
    status: 'confirmed',
    appointment_date: new Date().toISOString().split('T')[0],
    appointment_time: '09:00',
    notes: '',
});

const submitBooking = () => {
    bookingForm.post(route('admin.appointments.store'), {
        onSuccess: () => {
            emit('close');
            bookingForm.reset();
            bookingForm.status = 'confirmed';
            bookingForm.appointment_date = new Date().toISOString().split('T')[0];
            bookingForm.appointment_time = '09:00';
        },
    });
};

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div class="liquid-glass-card w-full max-w-2xl p-6 sm:p-7 space-y-5 relative" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-brand-600/30">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold" style="color: var(--text-heading);">Novo Agendamento Manual</h3>
                            <p class="text-xs opacity-60">Cadastre uma reserva interna diretamente no sistema</p>
                        </div>
                    </div>
                    <button type="button" @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitBooking" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group md:col-span-2 mb-0">
                            <label class="form-label text-xs" for="modal_service_id">Selecione o Serviço *</label>
                            <select
                                id="modal_service_id"
                                v-model="bookingForm.service_id"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                required
                            >
                                <option value="">Selecione um serviço...</option>
                                <option
                                    v-for="svc in page.props.services || []"
                                    :key="svc.id"
                                    :value="svc.id"
                                >
                                    {{ svc.name }} — R$ {{ Number(svc.price || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }} ({{ svc.duration_minutes }} min)
                                </option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_client_name">Nome Completo do Cliente *</label>
                            <input
                                type="text"
                                id="modal_client_name"
                                v-model="bookingForm.client_name"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="Ex: João da Silva"
                                required
                            >
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_client_email">E-mail do Cliente *</label>
                            <input
                                type="email"
                                id="modal_client_email"
                                v-model="bookingForm.client_email"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="cliente@email.com"
                                required
                            >
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_client_phone">Telefone / WhatsApp *</label>
                            <input
                                type="text"
                                id="modal_client_phone"
                                v-model="bookingForm.client_phone"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="(11) 99999-8888"
                                required
                            >
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_status">Status Inicial *</label>
                            <select
                                id="modal_status"
                                v-model="bookingForm.status"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                required
                            >
                                <option value="confirmed">Confirmado</option>
                                <option value="pending">Pendente</option>
                                <option value="completed">Concluído</option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_appointment_date">Data do Agendamento *</label>
                            <input
                                type="date"
                                id="modal_appointment_date"
                                v-model="bookingForm.appointment_date"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                required
                            >
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_appointment_time">Horário de Início *</label>
                            <input
                                type="time"
                                id="modal_appointment_time"
                                v-model="bookingForm.appointment_time"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                required
                            >
                        </div>

                        <div class="form-group md:col-span-2 mb-0">
                            <label class="form-label text-xs" for="modal_notes">Observações Adicionais</label>
                            <textarea
                                id="modal_notes"
                                v-model="bookingForm.notes"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                rows="2"
                                placeholder="Ex: Cliente preferiu atendimento especial"
                            ></textarea>
                        </div>
                    </div>

                    <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary py-2.5 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30"
                            :disabled="bookingForm.processing"
                        >
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>{{ bookingForm.processing ? 'Salvando...' : 'Confirmar Agendamento' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
