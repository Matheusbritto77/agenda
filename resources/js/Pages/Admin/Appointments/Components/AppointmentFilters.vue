<script setup>
defineProps({
    filterForm: {
        type: Object,
        required: true,
    },
    activeView: {
        type: String,
        default: 'table',
    },
    hasAppointments: {
        type: Boolean,
        default: false,
    },
    totalCount: {
        type: Number,
        default: 0,
    },
});

defineEmits(['submit-filter', 'clear-filters', 'switch-view']);
</script>

<template>
    <div class="card p-4 sm:p-5 shadow-sm space-y-4">
        <form @submit.prevent="$emit('submit-filter')" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <div class="sm:col-span-4 lg:col-span-4">
                <label class="form-label text-xs font-bold block mb-1">Status</label>
                <select v-model="filterForm.status" class="form-control text-xs sm:text-sm rounded-xl">
                    <option value="">Todos os Status</option>
                    <option value="confirmed">Confirmados</option>
                    <option value="pending">Pendentes</option>
                    <option value="completed">Concluídos</option>
                    <option value="cancelled">Cancelados</option>
                </select>
            </div>

            <div class="sm:col-span-4 lg:col-span-4">
                <label class="form-label text-xs font-bold block mb-1">Data Específica</label>
                <input type="date" v-model="filterForm.date" class="form-control text-xs sm:text-sm rounded-xl" />
            </div>

            <div class="sm:col-span-4 lg:col-span-4 flex items-center gap-2">
                <button type="submit" class="btn btn-primary py-2.5 px-4 text-xs font-bold rounded-xl flex-1 justify-center">
                    <i class="fa-solid fa-filter text-xs"></i>
                    <span>Filtrar</span>
                </button>

                <button
                    v-if="filterForm.status || filterForm.date"
                    type="button"
                    @click="$emit('clear-filters')"
                    class="btn btn-outline py-2.5 px-3 text-xs font-bold rounded-xl"
                    title="Limpar filtros"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </form>

        <div class="flex items-center justify-between pt-3 border-t text-xs opacity-75" style="border-color: var(--border);">
            <span>Total listado: <strong>{{ totalCount }}</strong> agendamentos</span>

            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                <button
                    type="button"
                    @click="$emit('switch-view', 'table')"
                    :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', activeView === 'table' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'opacity-60 hover:opacity-100']"
                >
                    <i class="fa-solid fa-table-list mr-1"></i>
                    Tabela
                </button>
                <button
                    type="button"
                    @click="$emit('switch-view', 'calendar')"
                    :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', activeView === 'calendar' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'opacity-60 hover:opacity-100']"
                >
                    <i class="fa-regular fa-calendar mr-1"></i>
                    Calendário
                </button>
            </div>
        </div>
    </div>
</template>
