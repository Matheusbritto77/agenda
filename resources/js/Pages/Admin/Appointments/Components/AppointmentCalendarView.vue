<script setup>
import { computed } from 'vue';

const props = defineProps({
    appointments: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['open-detail']);

const today = computed(() => new Date());

const startOfMonth = computed(() => {
    return new Date(today.value.getFullYear(), today.value.getMonth(), 1);
});

const endOfMonth = computed(() => {
    return new Date(today.value.getFullYear(), today.value.getMonth() + 1, 0);
});

const startDayOfWeek = computed(() => {
    return startOfMonth.value.getDay();
});

const daysInMonth = computed(() => {
    return endOfMonth.value.getDate();
});

const monthFormatted = computed(() => {
    return today.value.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
});

const groupedByDate = computed(() => {
    const grouped = {};
    props.appointments.forEach((app) => {
        const dateKey = new Date(app.appointment_date + 'T00:00:00').toISOString().split('T')[0];
        if (!grouped[dateKey]) {
            grouped[dateKey] = [];
        }
        grouped[dateKey].push(app);
    });
    return grouped;
});

const calendarDays = computed(() => {
    const days = [];
    const start = startOfMonth.value;
    for (let i = 0; i < startDayOfWeek.value; i++) {
        days.push({ isEmpty: true });
    }
    for (let day = 1; day <= daysInMonth.value; day++) {
        const d = new Date(start.getFullYear(), start.getMonth(), day);
        const dateStr = d.toISOString().split('T')[0];
        const todayStr = today.value.toISOString().split('T')[0];
        const appsForDay = groupedByDate.value[dateStr] || [];
        days.push({
            isEmpty: false,
            day: day,
            dateStr: dateStr,
            isToday: dateStr === todayStr,
            appointments: appsForDay,
        });
    }
    return days;
});

const calendarStatusClass = (status) => {
    switch (status) {
        case 'confirmed':
            return 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30';
        case 'cancelled':
            return 'bg-rose-500/20 text-rose-600 dark:text-rose-300 border border-rose-500/30';
        default:
            return 'bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 border border-indigo-500/30';
    }
};
</script>

<template>
    <div class="card p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold capitalize" style="color: var(--text-heading);">{{ monthFormatted }}</h3>
        </div>

        <div class="grid grid-cols-7 gap-2 text-center text-xs font-bold uppercase tracking-wider text-slate-400">
            <div>Dom</div><div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div>
        </div>

        <div class="grid grid-cols-7 gap-2">
            <div
                v-for="(day, idx) in calendarDays"
                :key="idx"
                :class="[
                    'min-h-[90px] sm:min-h-[110px] p-2 rounded-2xl border transition-all flex flex-col justify-between text-left relative overflow-hidden',
                    day.isEmpty ? 'opacity-20 border-dashed border-slate-300 dark:border-slate-800' : 'border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50',
                    day.isToday ? 'ring-2 ring-indigo-500 bg-indigo-500/5' : ''
                ]"
            >
                <template v-if="!day.isEmpty">
                    <div class="flex items-center justify-between">
                        <span :class="['text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center', day.isToday ? 'bg-indigo-600 text-white' : 'text-slate-700 dark:text-slate-300']">
                            {{ day.day }}
                        </span>
                        <span v-if="day.appointments.length > 0" class="text-[10px] font-extrabold text-indigo-500">
                            {{ day.appointments.length }}
                        </span>
                    </div>

                    <div class="space-y-1 mt-1 overflow-y-auto max-h-[60px] custom-scrollbar">
                        <div
                            v-for="app in day.appointments.slice(0, 3)"
                            :key="app.id"
                            @click="$emit('open-detail', app)"
                            :class="['text-[10px] p-1 rounded-md font-bold truncate cursor-pointer transition-transform hover:scale-102', calendarStatusClass(app.status)]"
                            :title="`${app.customer_name} - ${(app.start_time || '').substring(0,5)}`"
                        >
                            {{ (app.start_time || '').substring(0, 5) }} {{ app.customer_name }}
                        </div>
                        <div v-if="day.appointments.length > 3" class="text-[9px] text-slate-400 font-bold pl-1">
                            +{{ day.appointments.length - 3 }} mais
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
