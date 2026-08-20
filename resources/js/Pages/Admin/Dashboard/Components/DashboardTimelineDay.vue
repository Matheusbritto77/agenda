<script setup>
defineProps({
    timelineHours: {
        type: Array,
        required: true,
    },
});

defineEmits(['open-appointment']);

const statusClass = (status) => {
    switch (status) {
        case 'confirmed': return 'bg-emerald-500/15 border-emerald-500/30 text-emerald-700 dark:text-emerald-300';
        case 'completed': return 'bg-blue-500/15 border-blue-500/30 text-blue-700 dark:text-blue-300';
        case 'cancelled': return 'bg-rose-500/15 border-rose-500/30 text-rose-700 dark:text-rose-300';
        default: return 'bg-amber-500/15 border-amber-500/30 text-amber-700 dark:text-amber-300';
    }
};

const statusLabel = (status) => {
    switch (status) {
        case 'confirmed': return 'Confirmado';
        case 'completed': return 'Concluído';
        case 'cancelled': return 'Cancelado';
        default: return 'Pendente';
    }
};
</script>

<template>
    <div class="card p-4 sm:p-6 space-y-4 shadow-sm">
        <div class="space-y-3">
            <div
                v-for="item in timelineHours"
                :key="item.hour"
                class="flex items-start gap-3 sm:gap-4 py-2 border-b border-dashed border-slate-200 dark:border-slate-800 last:border-0"
            >
                <!-- Hour label -->
                <div class="w-12 sm:w-16 text-right shrink-0 pt-1">
                    <span class="text-xs font-black text-slate-400 dark:text-slate-500 font-mono">{{ item.hourString }}</span>
                </div>

                <!-- Appointments list in this slot -->
                <div class="flex-1 min-w-0 space-y-2">
                    <template v-if="item.matchingAppointments.length > 0">
                        <div
                            v-for="apt in item.matchingAppointments"
                            :key="apt.id"
                            @click="$emit('open-appointment', apt)"
                            :class="[
                                'p-3 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-2 cursor-pointer transition-all hover:scale-[1.01]',
                                statusClass(apt.status)
                            ]"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-white/80 dark:bg-slate-900/60 flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                    <i class="fa-solid fa-scissors"></i>
                                </div>
                                <div class="min-w-0">
                                    <h5 class="text-xs sm:text-sm font-bold truncate">{{ apt.client_name }}</h5>
                                    <p class="text-[11px] opacity-75 truncate">
                                        {{ apt.service?.name || 'Serviço' }} &bull; {{ apt.appointment_time?.substring(0, 5) }} ({{ apt.service?.duration_minutes || 30 }} min)
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                                <span class="text-xs font-black">R$ {{ Number(apt.service?.price || 0).toFixed(2) }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-white/60 dark:bg-slate-900/40">
                                    {{ statusLabel(apt.status) }}
                                </span>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="h-6 flex items-center">
                            <span class="text-[11px] text-slate-300 dark:text-slate-700 italic">Disponível</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
