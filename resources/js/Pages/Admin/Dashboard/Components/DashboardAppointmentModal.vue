<script setup>
defineProps({
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
});

const emit = defineEmits(['close', 'status-change']);

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};
</script>

<template>
    <div
        v-if="show && appointment"
        class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
        @click="handleBackdropClick"
    >
        <div class="liquid-glass-card w-full max-w-lg p-6 sm:p-7 space-y-5 relative" @click.stop>
            <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-brand-600/30">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">Agendamento #{{ appointment.id }}</h3>
                        <p class="text-xs opacity-60">{{ appointment.date }} &bull; {{ appointment.time }}</p>
                    </div>
                </div>
                <button type="button" @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cliente</span>
                    <p class="font-bold text-slate-900 dark:text-white">{{ appointment.client_name }}</p>
                    <p class="text-xs text-slate-400">{{ appointment.client_phone || 'Sem telefone' }}</p>
                </div>

                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Serviço</span>
                    <p class="font-bold text-indigo-600 dark:text-indigo-400">{{ appointment.service_name }}</p>
                    <p class="text-xs font-black text-slate-900 dark:text-white">R$ {{ appointment.service_price }} ({{ appointment.duration }})</p>
                </div>

                <div v-if="appointment.notes" class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-1 sm:col-span-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Observações</span>
                    <p class="text-xs text-slate-600 dark:text-slate-300 italic">{{ appointment.notes }}</p>
                </div>
            </div>

            <!-- Status Buttons -->
            <div class="pt-4 border-t space-y-2" style="border-color: var(--border);">
                <span class="text-xs font-bold text-slate-400 block">Atualizar Status:</span>
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
</template>
