<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    form: {
        type: Object,
        required: true,
    },
    allDays: {
        type: Array,
        required: true,
    },
    configuredDays: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'submit']);

const normalizedDays = computed(() => {
    return props.allDays.map(d => ({
        key: d.key !== undefined ? d.key : d.value,
        name: d.name || d.label || `Dia ${d.key ?? d.value}`,
        shortName: (d.name || d.label || '').substring(0, 3).toUpperCase(),
    }));
});

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};

const applyPreset = (opens, closes, hasBreak = false, breakOpens = '', breakCloses = '') => {
    props.form.opens_at = opens;
    props.form.closes_at = closes;
    props.form.has_break = hasBreak;
    if (hasBreak) {
        props.form.break_opens_at = breakOpens;
        props.form.break_closes_at = breakCloses;
    } else {
        props.form.break_opens_at = '';
        props.form.break_closes_at = '';
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
            <div class="liquid-glass-card w-full max-w-lg p-6 sm:p-7 space-y-5 relative shadow-2xl" @click.stop>
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-700 to-violet-600 text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-indigo-600/30">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-black tracking-tight" style="color: var(--text-heading);">
                                {{ isEditing ? 'Editar Expediente' : 'Novo Dia de Expediente' }}
                            </h3>
                            <p class="text-xs opacity-60">Defina os horários de atendimento do dia</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all cursor-pointer"
                    >
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="$emit('submit')" class="space-y-4">
                    <!-- Day of Week Field -->
                    <div class="space-y-1.5">
                        <label class="form-label text-xs font-bold uppercase tracking-wider flex items-center gap-1.5" style="color: var(--text-heading);" for="modal_day_of_week">
                            <i class="fa-solid fa-calendar-day text-indigo-500 text-xs"></i>
                            <span>Dia da Semana <span class="text-rose-500">*</span></span>
                        </label>
                        <select
                            id="modal_day_of_week"
                            v-model="form.day_of_week"
                            :disabled="isEditing"
                            class="form-control text-xs sm:text-sm rounded-xl font-semibold"
                            required
                        >
                            <option value="" disabled>Selecione um dia da semana</option>
                            <option
                                v-for="day in normalizedDays"
                                :key="day.key"
                                :value="String(day.key)"
                                :disabled="!isEditing && configuredDays.includes(day.key)"
                            >
                                {{ day.name }} {{ (!isEditing && configuredDays.includes(day.key)) ? '(Já configurado)' : '' }}
                            </option>
                        </select>
                        <span v-if="form.errors?.day_of_week" class="text-xs text-rose-500 mt-1 font-medium block">
                            {{ form.errors.day_of_week }}
                        </span>
                    </div>

                    <!-- Quick Presets -->
                    <div class="space-y-1.5 pt-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Preenchimento Rápido</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                @click="applyPreset('08:00', '18:00', true, '12:00', '13:00')"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 hover:border-indigo-500 hover:text-indigo-600 transition-all cursor-pointer"
                            >
                                08h - 18h (Almoço 12-13h)
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('09:00', '19:00', true, '13:00', '14:00')"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 hover:border-indigo-500 hover:text-indigo-600 transition-all cursor-pointer"
                            >
                                09h - 19h (Almoço 13-14h)
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('08:00', '12:00', false)"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 hover:border-indigo-500 hover:text-indigo-600 transition-all cursor-pointer"
                            >
                                08h - 12h (Sábado)
                            </button>
                        </div>
                    </div>

                    <!-- Operating Hours Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div class="form-group mb-0 space-y-1">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);" for="modal_opening_time">
                                <i class="fa-solid fa-sun text-amber-500 mr-1"></i>
                                Horário de Abertura <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="time"
                                id="modal_opening_time"
                                v-model="form.opens_at"
                                class="form-control text-xs sm:text-sm rounded-xl font-bold"
                                required
                            />
                            <span v-if="form.errors?.opens_at" class="text-xs text-rose-500 mt-1 font-medium block">
                                {{ form.errors.opens_at }}
                            </span>
                        </div>

                        <div class="form-group mb-0 space-y-1">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);" for="modal_closing_time">
                                <i class="fa-solid fa-moon text-indigo-500 mr-1"></i>
                                Horário de Fechamento <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="time"
                                id="modal_closing_time"
                                v-model="form.closes_at"
                                class="form-control text-xs sm:text-sm rounded-xl font-bold"
                                required
                            />
                            <span v-if="form.errors?.closes_at" class="text-xs text-rose-500 mt-1 font-medium block">
                                {{ form.errors.closes_at }}
                            </span>
                        </div>
                    </div>

                    <!-- Break / Interval Box -->
                    <div class="p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-mug-hot text-xs text-amber-500"></i>
                                <span class="text-xs font-bold" style="color: var(--text-heading);">Intervalo / Almoço</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.has_break"
                                    class="sr-only peer"
                                />
                                <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div v-if="form.has_break" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <div class="form-group mb-0 space-y-1">
                                <label class="form-label text-[11px] font-bold text-slate-500 dark:text-slate-400" for="modal_break_start">
                                    Início da Pausa
                                </label>
                                <input
                                    type="time"
                                    id="modal_break_start"
                                    v-model="form.break_opens_at"
                                    class="form-control text-xs sm:text-sm rounded-xl"
                                    :required="form.has_break"
                                />
                                <span v-if="form.errors?.break_opens_at" class="text-xs text-rose-500 mt-1 block">
                                    {{ form.errors.break_opens_at }}
                                </span>
                            </div>

                            <div class="form-group mb-0 space-y-1">
                                <label class="form-label text-[11px] font-bold text-slate-500 dark:text-slate-400" for="modal_break_end">
                                    Fim da Pausa
                                </label>
                                <input
                                    type="time"
                                    id="modal_break_end"
                                    v-model="form.break_closes_at"
                                    class="form-control text-xs sm:text-sm rounded-xl"
                                    :required="form.has_break"
                                />
                                <span v-if="form.errors?.break_closes_at" class="text-xs text-rose-500 mt-1 block">
                                    {{ form.errors.break_closes_at }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Active Toggle -->
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200/80 dark:border-slate-800/80 bg-white/50 dark:bg-slate-900/30">
                        <input
                            type="checkbox"
                            id="modal_hour_active"
                            v-model="form.is_active"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                        />
                        <label for="modal_hour_active" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                            Dia ativo para novos agendamentos online
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary py-2.5 px-6 text-xs font-black rounded-xl shadow-lg shadow-indigo-600/30 inline-flex items-center gap-2 cursor-pointer"
                            :disabled="form.processing"
                        >
                            <i v-if="form.processing" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                            <i v-else class="fa-solid fa-check text-xs"></i>
                            <span>{{ form.processing ? 'Salvando...' : 'Salvar Expediente' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
