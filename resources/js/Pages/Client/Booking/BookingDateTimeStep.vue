<script setup>
defineProps({
    monthTitle: {
        type: String,
        required: true,
    },
    calendarDays: {
        type: Array,
        required: true,
    },
    selectedDate: {
        type: String,
        default: '',
    },
    selectedTime: {
        type: String,
        default: '',
    },
    availableSlots: {
        type: Array,
        default: () => [],
    },
    slotsLoading: {
        type: Boolean,
        default: false,
    },
    canPrevMonth: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['prev-month', 'next-month', 'select-date', 'select-time']);

const formatDateLong = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: 'short' });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Calendar Card -->
        <div class="card p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <button
                    type="button"
                    @click="$emit('prev-month')"
                    :disabled="!canPrevMonth"
                    class="w-9 h-9 rounded-xl border flex items-center justify-center text-xs transition-all hover:scale-105 disabled:opacity-30 disabled:cursor-not-allowed"
                >
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <h3 class="text-base font-extrabold capitalize" style="color: var(--text-heading);">
                    {{ monthTitle }}
                </h3>
                <button
                    type="button"
                    @click="$emit('next-month')"
                    class="w-9 h-9 rounded-xl border flex items-center justify-center text-xs transition-all hover:scale-105"
                >
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1.5 text-center">
                <div v-for="d in ['DOM','SEG','TER','QUA','QUI','SEX','SÁB']" :key="d" class="text-[11px] font-black uppercase text-slate-400 py-1">
                    {{ d }}
                </div>
                <button
                    v-for="(d, idx) in calendarDays"
                    :key="idx"
                    type="button"
                    @click="!d.otherMonth && !d.disabled && $emit('select-date', d)"
                    :disabled="d.otherMonth || d.disabled"
                    :class="[
                        'aspect-square rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center transition-all relative',
                        d.otherMonth ? 'opacity-20 cursor-not-allowed' : '',
                        d.disabled ? 'opacity-30 cursor-not-allowed line-through' : 'hover:scale-105',
                        d.isToday && !d.selected ? 'border border-indigo-500 font-black' : '',
                        d.selected ? 'bg-indigo-600 text-white font-black shadow-lg shadow-indigo-600/30 scale-105 z-10' : 'bg-slate-50 dark:bg-slate-900/60 text-slate-700 dark:text-slate-300'
                    ]"
                    :style="d.selected ? { backgroundColor: 'var(--primary)' } : {}"
                >
                    {{ d.day }}
                </button>
            </div>
        </div>

        <!-- Available Time Slots -->
        <div class="card p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-extrabold flex items-center gap-2" style="color: var(--text-heading);">
                    <i class="fa-regular fa-clock" :style="{ color: 'var(--primary)' }"></i>
                    <span>Horários Disponíveis</span>
                </h4>
                <span v-if="selectedDate" class="text-xs font-semibold px-2.5 py-0.5 rounded-full" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
                    {{ formatDateLong(selectedDate) }}
                </span>
            </div>

            <div v-if="!selectedDate" class="text-center py-8 text-slate-400">
                <i class="fa-regular fa-hand-pointer text-2xl mb-2 block animate-bounce" :style="{ color: 'var(--primary)' }"></i>
                <p class="font-bold text-xs">Selecione uma data no calendário acima</p>
            </div>

            <div v-else-if="slotsLoading" class="text-center py-8">
                <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 block" :style="{ color: 'var(--primary)' }"></i>
                <p class="text-xs text-slate-400">Carregando horários livres...</p>
            </div>

            <div v-else-if="availableSlots.length === 0" class="text-center py-8 text-slate-400">
                <i class="fa-solid fa-calendar-xmark text-2xl mb-2 block text-rose-500"></i>
                <p class="font-bold text-xs">Nenhum horário disponível para esta data.</p>
            </div>

            <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                <button
                    v-for="slot in availableSlots"
                    :key="slot.time || slot"
                    type="button"
                    @click="$emit('select-time', slot.time || slot)"
                    :class="[
                        'py-2.5 px-2 rounded-xl text-xs font-black text-center transition-all hover:scale-105 cursor-pointer',
                        selectedTime === (slot.time || slot) ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200'
                    ]"
                    :style="selectedTime === (slot.time || slot) ? { backgroundColor: 'var(--primary)' } : {}"
                >
                    {{ slot.time || slot }}
                </button>
            </div>
        </div>
    </div>
</template>
