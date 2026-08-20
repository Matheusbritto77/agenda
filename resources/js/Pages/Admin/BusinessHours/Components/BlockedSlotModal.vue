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
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-amber-500 to-rose-500 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-amber-500/30">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">
                            {{ isEditing ? 'Editar Bloqueio' : 'Novo Bloqueio de Horário' }}
                        </h3>
                        <p class="text-xs opacity-60">Impeça agendamentos durante um intervalo específico</p>
                    </div>
                </div>
                <button type="button" @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="$emit('submit')" class="space-y-4">
                <div class="form-group mb-0">
                    <label class="form-label text-xs" for="block_reason">Motivo do Bloqueio *</label>
                    <input
                        type="text"
                        id="block_reason"
                        v-model="form.reason"
                        class="form-control text-xs sm:text-sm rounded-xl"
                        placeholder="Ex: Feriado Nacional, Reforma, Folga Geral"
                        required
                    />
                </div>

                <template v-if="!isEditing">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="block_starts_at">Início do Bloqueio *</label>
                            <input
                                type="datetime-local"
                                id="block_starts_at"
                                v-model="form.starts_at"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                required
                            />
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="block_ends_at">Término do Bloqueio *</label>
                            <input
                                type="datetime-local"
                                id="block_ends_at"
                                v-model="form.ends_at"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                required
                            />
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Data de Início</label>
                            <input type="date" v-model="form.start_date" class="form-control text-xs rounded-xl" required />
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Hora de Início</label>
                            <input type="time" v-model="form.start_time" class="form-control text-xs rounded-xl" required />
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Data de Término</label>
                            <input type="date" v-model="form.end_date" class="form-control text-xs rounded-xl" required />
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Hora de Término</label>
                            <input type="time" v-model="form.end_time" class="form-control text-xs rounded-xl" required />
                        </div>
                    </div>
                </template>

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
                        <span>{{ form.processing ? 'Salvando...' : 'Salvar Bloqueio' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
