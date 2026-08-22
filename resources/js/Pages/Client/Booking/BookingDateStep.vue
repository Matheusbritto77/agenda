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
    canPrevMonth: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Escolha a Data',
    },
    subtitle: {
        type: String,
        default: 'Selecione o melhor dia para seu atendimento',
    },
});

defineEmits(['prev-month', 'next-month', 'select-date', 'prev-step']);
</script>

<template>
    <div class="space-y-6">
        <!-- Step Header -->
        <div class="space-y-1">
            <h3 class="text-base sm:text-lg font-black" :style="{ color: 'var(--text-heading, #0f172a)' }">
                {{ title }}
            </h3>
            <p v-if="subtitle" class="text-xs opacity-75" :style="{ color: 'var(--text-muted, #64748b)' }">
                {{ subtitle }}
            </p>
        </div>

        <!-- Calendar Card -->
        <div class="card shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <button
                    type="button"
                    @click="$emit('prev-month')"
                    :disabled="!canPrevMonth"
                    class="w-9 h-9 rounded-xl border flex items-center justify-center text-xs transition-all hover:scale-105 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                    :style="{
                        backgroundColor: 'var(--surface)',
                        borderColor: 'var(--border)',
                        color: 'var(--text)'
                    }"
                    title="Mês anterior"
                >
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <h3 class="text-base font-extrabold capitalize" :style="{ color: 'var(--text-heading)' }">
                    {{ monthTitle }}
                </h3>

                <button
                    type="button"
                    @click="$emit('next-month')"
                    class="w-9 h-9 rounded-xl border flex items-center justify-center text-xs transition-all hover:scale-105 cursor-pointer"
                    :style="{
                        backgroundColor: 'var(--surface)',
                        borderColor: 'var(--border)',
                        color: 'var(--text)'
                    }"
                    title="Próximo mês"
                >
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1.5 text-center">
                <div v-for="d in ['DOM','SEG','TER','QUA','QUI','SEX','SÁB']" :key="d" class="text-[11px] font-black uppercase opacity-60 py-1" :style="{ color: 'var(--text-muted)' }">
                    {{ d }}
                </div>
                <button
                    v-for="(d, idx) in calendarDays"
                    :key="idx"
                    type="button"
                    @click="!d.otherMonth && !d.disabled && $emit('select-date', d)"
                    :disabled="d.otherMonth || d.disabled"
                    :class="[
                        'aspect-square rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center transition-all relative cursor-pointer',
                        d.otherMonth ? 'opacity-20 cursor-not-allowed' : '',
                        d.disabled ? 'opacity-30 cursor-not-allowed line-through' : 'hover:scale-105',
                        d.isToday && !d.selected ? 'border font-black' : '',
                        d.selected ? 'font-black scale-105 z-10' : ''
                    ]"
                    :style="[
                        d.selected ? {
                            backgroundColor: 'var(--primary)',
                            color: 'var(--btn-text, #ffffff)',
                            boxShadow: '0 4px 12px var(--primary-light)'
                        } : {
                            backgroundColor: 'var(--surface)',
                            color: 'var(--text)',
                            borderColor: 'var(--border)'
                        },
                        d.isToday && !d.selected ? { borderColor: 'var(--primary)' } : {}
                    ]"
                >
                    {{ d.day }}
                </button>
            </div>

            <div class="pt-3 border-t flex items-center justify-between" :style="{ borderColor: 'var(--border, #e2e8f0)' }">
                <button
                    type="button"
                    @click="$emit('prev-step')"
                    class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl cursor-pointer"
                >
                    <i class="fa-solid fa-arrow-left text-xs mr-1"></i>
                    Voltar aos Serviços
                </button>

                <span class="text-xs opacity-60" :style="{ color: 'var(--text-muted)' }">
                    Clique em um dia para ver os horários
                </span>
            </div>
        </div>
    </div>
</template>
