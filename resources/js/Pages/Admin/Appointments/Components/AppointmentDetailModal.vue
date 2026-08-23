<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    appointment: {
        type: Object,
        default: null,
    },
    statusForm: {
        type: Object,
        required: true,
    },
    canUpdateStatus: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'status-change']);

const togglingPublic = ref(false);

const togglePublic = (review) => {
    if (!review?.id || togglingPublic.value) return;
    togglingPublic.value = true;
    router.patch(route('appointments.reviews.toggle-public', review.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            review.is_public = !review.is_public;
            togglingPublic.value = false;
        },
        onFinish: () => {
            togglingPublic.value = false;
        },
    });
};

const whatsAppUrl = computed(() => {
    if (!props.appointment?.customer_phone) return '#';
    const cleanPhone = props.appointment.customer_phone.replace(/\D/g, '');
    return 'https://wa.me/55' + cleanPhone;
});

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show && appointment"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div class="liquid-glass-card w-full max-w-xl p-6 sm:p-7 space-y-6 relative max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-indigo-600/30">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">Detalhes do Agendamento</h3>
                            <p class="text-xs opacity-60">ID: #{{ appointment.id }}</p>
                        </div>
                    </div>
                    <button type="button" @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
                    <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Cliente</span>
                        <p class="font-bold text-slate-900 dark:text-white">{{ appointment.customer_name }}</p>
                        <p class="text-xs text-slate-400">{{ appointment.customer_email }}</p>
                        <a
                            :href="whatsAppUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline pt-1"
                        >
                            <i class="fa-brands fa-whatsapp text-sm"></i>
                            {{ appointment.customer_phone }}
                        </a>
                    </div>

                    <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Profissional Responsável</span>
                        <div v-if="appointment.team_member_name" class="flex items-center gap-2.5 pt-0.5">
                            <div class="w-8 h-8 rounded-full bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-black shrink-0 overflow-hidden border border-indigo-500/20">
                                <img v-if="appointment.team_member_avatar" :src="appointment.team_member_avatar" class="w-full h-full object-cover" />
                                <span v-else>{{ appointment.team_member_name.substring(0, 1).toUpperCase() }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 dark:text-white truncate">{{ appointment.team_member_name }}</p>
                                <p v-if="appointment.team_member_job" class="text-xs text-slate-400 truncate">{{ appointment.team_member_job }}</p>
                            </div>
                        </div>
                        <div v-else class="text-xs text-slate-400 italic pt-1 flex items-center gap-1.5">
                            <i class="fa-solid fa-user-slash text-xs"></i>
                            <span>Atendimento geral / Sem profissional específico</span>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Serviço</span>
                        <p class="font-bold text-indigo-600 dark:text-indigo-400">{{ appointment.service_name }}</p>
                        <p class="text-xs text-slate-400">Duração: {{ appointment.duration }}</p>
                        <p class="font-black text-sm text-slate-900 dark:text-white pt-1">R$ {{ appointment.service_price }}</p>
                    </div>

                    <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Horário Marcado</span>
                        <p class="font-bold text-slate-900 dark:text-white">{{ appointment.date }}</p>
                        <p class="text-xs text-slate-400">{{ appointment.time }}</p>
                        <div class="pt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider" :class="appointment.status === 'confirmed' ? 'bg-emerald-500/15 text-emerald-600' : (appointment.status === 'completed' ? 'bg-blue-500/15 text-blue-600' : (appointment.status === 'cancelled' ? 'bg-rose-500/15 text-rose-600' : 'bg-amber-500/15 text-amber-600'))">
                                {{ appointment.status === 'confirmed' ? 'Confirmado' : (appointment.status === 'completed' ? 'Concluído' : (appointment.status === 'cancelled' ? 'Cancelado' : 'Pendente')) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="appointment.notes" class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-1 sm:col-span-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Observações</span>
                        <p class="text-xs text-slate-600 dark:text-slate-300 italic">{{ appointment.notes }}</p>
                    </div>

                    <!-- Customer Review / Internal Evaluation Section -->
                    <div v-if="appointment.review" class="p-4 rounded-2xl border border-amber-500/25 bg-amber-500/5 dark:bg-amber-500/10 space-y-2.5 sm:col-span-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="flex gap-0.5 text-amber-400 text-sm">
                                    <i v-for="s in 5" :key="s" class="fa-star text-xs" :class="s <= appointment.review.rating ? 'fa-solid' : 'fa-regular'"></i>
                                </div>
                                <span class="text-xs font-black text-slate-900 dark:text-white">{{ appointment.review.rating }}.0 de 5</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="appointment.review.is_public ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/20'">
                                    {{ appointment.review.is_public ? 'Público na Empresa' : 'Feedback Interno' }}
                                </span>
                            </div>

                            <button
                                v-if="canUpdateStatus"
                                type="button"
                                @click="togglePublic(appointment.review)"
                                :disabled="togglingPublic"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold transition-all cursor-pointer border"
                                :class="appointment.review.is_public ? 'border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500/20' : 'border-indigo-500/30 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-500/20'"
                            >
                                <i :class="appointment.review.is_public ? 'fa-solid fa-eye-slash' : 'fa-solid fa-share-nodes'"></i>
                                <span>{{ appointment.review.is_public ? 'Tornar Apenas Interno' : 'Destacar na Página Pública' }}</span>
                            </button>
                        </div>

                        <p v-if="appointment.review.comment" class="text-xs sm:text-sm text-slate-700 dark:text-slate-200 italic leading-relaxed">
                            "{{ appointment.review.comment }}"
                        </p>
                        <p v-else class="text-xs text-slate-400 italic">Cliente avaliou este serviço sem comentário adicional.</p>
                        
                        <span v-if="appointment.review.created_at" class="text-[10px] text-slate-400 block">
                            Avaliado em {{ appointment.review.created_at }}
                        </span>
                    </div>
                </div>

                <!-- Status Action Buttons -->
                <div v-if="canUpdateStatus" class="pt-4 border-t space-y-2" style="border-color: var(--border);">
                    <span class="text-xs font-bold text-slate-400 block">Alterar Status do Atendimento:</span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button
                            type="button"
                            @click="$emit('status-change', 'confirmed')"
                            :disabled="statusForm.processing || appointment.status === 'confirmed'"
                            class="p-2 rounded-xl text-xs font-bold border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 disabled:opacity-40 transition-all cursor-pointer"
                        >
                            Confirmar
                        </button>
                        <button
                            type="button"
                            @click="$emit('status-change', 'completed')"
                            :disabled="statusForm.processing || appointment.status === 'completed'"
                            class="p-2 rounded-xl text-xs font-bold border border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400 hover:bg-blue-500/20 disabled:opacity-40 transition-all cursor-pointer"
                        >
                            Concluir
                        </button>
                        <button
                            type="button"
                            @click="$emit('status-change', 'pending')"
                            :disabled="statusForm.processing || appointment.status === 'pending'"
                            class="p-2 rounded-xl text-xs font-bold border border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 disabled:opacity-40 transition-all cursor-pointer"
                        >
                            Pendente
                        </button>
                        <button
                            type="button"
                            @click="$emit('status-change', 'cancelled')"
                            :disabled="statusForm.processing || appointment.status === 'cancelled'"
                            class="p-2 rounded-xl text-xs font-bold border border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 disabled:opacity-40 transition-all cursor-pointer"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
