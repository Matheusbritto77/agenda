<script setup>
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

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
        @click="handleBackdropClick"
    >
        <div class="liquid-glass-card w-full max-w-lg p-6 sm:p-7 space-y-5 relative" @click.stop>
            <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-brand-600/30">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">
                            {{ isEditing ? 'Editar Expediente' : 'Novo Dia de Expediente' }}
                        </h3>
                        <p class="text-xs opacity-60">Defina os horários de atendimento do dia</p>
                    </div>
                </div>
                <button type="button" @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="$emit('submit')" class="space-y-4">
                <div class="form-group mb-0">
                    <label class="form-label text-xs" for="modal_day_of_week">Dia da Semana *</label>
                    <select
                        id="modal_day_of_week"
                        v-model="form.day_of_week"
                        class="form-control text-xs sm:text-sm rounded-xl"
                        :disabled="isEditing"
                        required
                    >
                        <option value="">Selecione um dia...</option>
                        <option
                            v-for="d in allDays"
                            :key="d.key"
                            :value="String(d.key)"
                            :disabled="!isEditing && configuredDays.includes(d.key)"
                        >
                            {{ d.name }} {{ (!isEditing && configuredDays.includes(d.key)) ? '(Já cadastrado)' : '' }}
                        </option>
                    </select>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label text-xs" for="modal_label">Identificador / Rótulo (Opcional)</label>
                    <input
                        type="text"
                        id="modal_label"
                        v-model="form.label"
                        class="form-control text-xs sm:text-sm rounded-xl"
                        placeholder="Ex: Horário Normal, Plantão"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-group mb-0">
                        <label class="form-label text-xs" for="modal_opens_at">Abertura *</label>
                        <input
                            type="time"
                            id="modal_opens_at"
                            v-model="form.opens_at"
                            class="form-control text-xs sm:text-sm rounded-xl"
                            required
                        />
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label text-xs" for="modal_closes_at">Fechamento *</label>
                        <input
                            type="time"
                            id="modal_closes_at"
                            v-model="form.closes_at"
                            class="form-control text-xs sm:text-sm rounded-xl"
                            required
                        />
                    </div>
                </div>

                <!-- Break / Lunch toggle -->
                <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-mug-hot text-amber-500 text-xs"></i>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Intervalo de Almoço / Pausa</span>
                        </div>
                        <input
                            type="checkbox"
                            v-model="form.has_break"
                            class="rounded text-indigo-600 focus:ring-indigo-500"
                        />
                    </div>

                    <div v-if="form.has_break" class="grid grid-cols-2 gap-3 pt-2">
                        <div>
                            <label class="text-[11px] font-semibold text-slate-400 block mb-1">Início da Pausa</label>
                            <input
                                type="time"
                                v-model="form.break_opens_at"
                                class="form-control text-xs rounded-xl"
                            />
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-slate-400 block mb-1">Fim da Pausa</label>
                            <input
                                type="time"
                                v-model="form.break_closes_at"
                                class="form-control text-xs rounded-xl"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input
                        type="checkbox"
                        id="modal_hour_active"
                        v-model="form.is_active"
                        class="rounded text-indigo-600 focus:ring-indigo-500"
                    />
                    <label for="modal_hour_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Dia ativo para novos agendamentos
                    </label>
                </div>

                <div class="pt-4 border-t flex items-center justify-end gap-2" style="border-color: var(--border);">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="btn btn-outline py-2 px-4 text-xs font-bold rounded-xl"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary py-2 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30"
                        :disabled="form.processing"
                    >
                        <i class="fa-solid fa-check text-xs"></i>
                        <span>{{ form.processing ? 'Salvando...' : 'Salvar Expediente' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
