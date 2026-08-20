<script setup>
defineProps({
    businessHours: {
        type: Array,
        default: () => [],
    },
    daysMap: {
        type: Object,
        required: true,
    },
    canManage: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['open-create', 'open-edit', 'open-delete']);

const formatTime = (value) => {
    if (!value) return '';
    return value.substring(0, 5);
};
</script>

<template>
    <div class="card overflow-hidden p-0 shadow-sm">
        <div class="p-5 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
            <div>
                <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">Grade de Expediente Semanal</h3>
                <p class="text-xs opacity-60">Dias e horários de abertura e fechamento para agendamento online</p>
            </div>
            <button
                v-if="canManage"
                type="button"
                @click="$emit('open-create')"
                class="btn btn-primary text-xs py-2 px-3.5 self-start sm:self-auto"
            >
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Novo Dia de Expediente</span>
            </button>
        </div>

        <div v-if="businessHours.length === 0" class="text-center py-12 px-4 text-slate-500">
            <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center mx-auto mb-3 text-xl">
                <i class="fa-regular fa-clock"></i>
            </div>
            <h4 class="text-sm font-bold" style="color: var(--text-heading);">Nenhum horário cadastrado</h4>
            <p class="text-xs opacity-70 mt-1">Adicione os dias e horários em que seu estabelecimento realiza atendimentos.</p>
        </div>

        <div v-else class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Dia da Semana</th>
                        <th>Horário de Atendimento</th>
                        <th>Intervalo / Almoço</th>
                        <th>Status</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="hour in businessHours" :key="hour.id">
                        <td>
                            <div class="font-extrabold text-sm" style="color: var(--text-heading);">
                                {{ daysMap[hour.day_of_week] }}
                            </div>
                            <div v-if="hour.label" class="text-xs text-slate-400 font-medium mt-0.5">
                                {{ hour.label }}
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center gap-1.5 font-bold text-xs sm:text-sm">
                                <i class="fa-regular fa-clock text-indigo-500 text-xs"></i>
                                <span>{{ formatTime(hour.opens_at) }} às {{ formatTime(hour.closes_at) }}</span>
                            </div>
                        </td>
                        <td>
                            <div v-if="hour.break_opens_at && hour.break_closes_at" class="text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5 font-medium">
                                <i class="fa-solid fa-mug-hot text-amber-500 text-[11px]"></i>
                                {{ formatTime(hour.break_opens_at) }} às {{ formatTime(hour.break_closes_at) }}
                            </div>
                            <span v-else class="text-xs text-slate-400 italic">Sem intervalo</span>
                        </td>
                        <td>
                            <span :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold', hour.is_active ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'bg-slate-200 dark:bg-slate-800 text-slate-500']">
                                <span :class="['w-1.5 h-1.5 rounded-full', hour.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                {{ hour.is_active ? 'Ativo' : 'Pausado' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    v-if="canManage"
                                    type="button"
                                    @click="$emit('open-edit', hour)"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-700 text-indigo-500 transition-all"
                                    title="Editar horário"
                                >
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                <button
                                    v-if="canManage"
                                    type="button"
                                    @click="$emit('open-delete', hour)"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-rose-100 dark:hover:bg-rose-950/40 text-rose-500 transition-all"
                                    title="Excluir horário"
                                >
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
