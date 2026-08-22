<script setup>
const props = defineProps({
    selectedDate: {
        type: String,
        required: true,
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
    title: {
        type: String,
        default: 'Escolha o Horário',
    },
    subtitle: {
        type: String,
        default: 'Selecione o horário desejado para seu atendimento',
    },
});

defineEmits(['select-time', 'prev-step', 'change-date']);

const formatDateLong = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
};
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

        <!-- Selected Date Ribbon / Card -->
        <div class="card shadow-sm space-y-4">
            <div class="flex items-center justify-between gap-3 flex-wrap pb-3 border-b" :style="{ borderColor: 'var(--border, #e2e8f0)' }">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-base shrink-0" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider opacity-60" :style="{ color: 'var(--text-muted)' }">Data Selecionada</span>
                        <p class="text-sm font-extrabold capitalize" :style="{ color: 'var(--text-heading)' }">{{ formatDateLong(selectedDate) }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="$emit('change-date')"
                    class="btn btn-outline py-2 px-3.5 text-xs font-bold rounded-xl cursor-pointer"
                >
                    <i class="fa-solid fa-calendar-days text-xs mr-1" :style="{ color: 'var(--primary)' }"></i>
                    Alterar Data
                </button>
            </div>

            <!-- Time Slots Grid -->
            <div>
                <h4 class="text-xs font-extrabold mb-3 flex items-center gap-2" :style="{ color: 'var(--text-heading)' }">
                    <i class="fa-regular fa-clock" :style="{ color: 'var(--primary)' }"></i>
                    <span>Horários Livres Disponíveis</span>
                </h4>

                <div v-if="slotsLoading" class="text-center py-12">
                    <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 block" :style="{ color: 'var(--primary)' }"></i>
                    <p class="text-xs opacity-60" :style="{ color: 'var(--text-muted)' }">Carregando horários livres...</p>
                </div>

                <div v-else-if="availableSlots.length === 0" class="text-center py-12 opacity-70">
                    <i class="fa-solid fa-calendar-xmark text-3xl mb-2 block text-rose-500"></i>
                    <p class="font-bold text-sm" :style="{ color: 'var(--text-heading)' }">Nenhum horário disponível para este dia.</p>
                    <p class="text-xs opacity-60 mt-1" :style="{ color: 'var(--text-muted)' }">Por favor, selecione outra data no calendário.</p>
                    <button
                        type="button"
                        @click="$emit('change-date')"
                        class="btn btn-primary mt-4 py-2 px-4 text-xs font-bold rounded-xl cursor-pointer"
                    >
                        Escolher Outra Data
                    </button>
                </div>

                <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2.5">
                    <button
                        v-for="slot in availableSlots"
                        :key="slot.time || slot"
                        type="button"
                        @click="$emit('select-time', slot.time || slot)"
                        :class="[
                            'py-3 px-2.5 rounded-xl text-xs font-black text-center transition-all hover:scale-105 cursor-pointer',
                            selectedTime === (slot.time || slot) ? 'shadow-md scale-105' : 'border'
                        ]"
                        :style="selectedTime === (slot.time || slot) ? {
                            backgroundColor: 'var(--primary)',
                            color: 'var(--btn-text, #ffffff)',
                            borderColor: 'var(--primary)',
                            boxShadow: '0 4px 12px var(--primary-light)'
                        } : {
                            backgroundColor: 'var(--surface)',
                            color: 'var(--text)',
                            borderColor: 'var(--border)'
                        }"
                    >
                        {{ slot.time || slot }}
                    </button>
                </div>
            </div>

            <!-- Footer navigation -->
            <div class="pt-4 border-t flex items-center justify-between" :style="{ borderColor: 'var(--border, #e2e8f0)' }">
                <button
                    type="button"
                    @click="$emit('prev-step')"
                    class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl cursor-pointer"
                >
                    <i class="fa-solid fa-arrow-left text-xs mr-1"></i>
                    Voltar para Data
                </button>

                <span class="text-xs opacity-60 hidden sm:inline" :style="{ color: 'var(--text-muted)' }">
                    Selecione um horário para prosseguir para a confirmação
                </span>
            </div>
        </div>
    </div>
</template>
