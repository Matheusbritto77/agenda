<script setup>
defineProps({
    weekHeaderDays: {
        type: Array,
        required: true,
    },
    weekMatrixHours: {
        type: Array,
        required: true,
    },
});

defineEmits(['open-appointment']);

const statusClass = (status) => {
    switch (status) {
        case 'confirmed': return 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30';
        case 'completed': return 'bg-blue-500/20 text-blue-600 dark:text-blue-300 border border-blue-500/30';
        case 'cancelled': return 'bg-rose-500/20 text-rose-600 dark:text-rose-300 border border-rose-500/30';
        default: return 'bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/30';
    }
};
</script>

<template>
    <div class="card p-4 sm:p-5 overflow-x-auto shadow-sm">
        <div class="min-w-[720px]">
            <!-- Matrix Header -->
            <div class="grid grid-cols-8 gap-2 pb-3 border-b text-center font-bold text-xs uppercase" style="border-color: var(--border);">
                <div class="text-slate-400">Hora</div>
                <div
                    v-for="d in weekHeaderDays"
                    :key="d.dateStr"
                    :class="['p-2 rounded-xl transition-all', d.isDayActive ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400']"
                >
                    <div class="text-[10px] opacity-80">{{ d.dayName.substring(0, 3) }}</div>
                    <div class="text-sm font-black">{{ d.dayNum }}</div>
                </div>
            </div>

            <!-- Matrix Rows -->
            <div class="space-y-1 pt-2">
                <div
                    v-for="row in weekMatrixHours"
                    :key="row.hour"
                    class="grid grid-cols-8 gap-2 items-stretch py-1 border-b border-dashed border-slate-100 dark:border-slate-800/60"
                >
                    <div class="text-xs font-mono text-slate-400 flex items-center justify-center font-bold">
                        {{ row.hourString }}
                    </div>

                    <div
                        v-for="(col, colIdx) in row.dayCols"
                        :key="colIdx"
                        class="min-h-[44px] p-1 rounded-lg bg-slate-50/50 dark:bg-slate-900/30 border border-slate-100 dark:border-slate-800/40 flex flex-col gap-1 overflow-hidden"
                    >
                        <div
                            v-for="apt in col.cellAppointments"
                            :key="apt.id"
                            @click="$emit('open-appointment', apt)"
                            :class="[
                                'p-1 rounded text-[10px] font-bold truncate cursor-pointer transition-all hover:scale-102',
                                statusClass(apt.status)
                            ]"
                            :title="`${apt.client_name} - ${apt.service?.name}`"
                        >
                            {{ apt.client_name }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
