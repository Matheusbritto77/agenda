<script setup>
import { computed } from 'vue';

const props = defineProps({
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
    selectedMember: {
        type: Object,
        default: null,
    },
    companyDefaultHours: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['open-create', 'open-edit', 'open-delete', 'customize-day']);

const formatTime = (value) => {
    if (!value) return '';
    return value.substring(0, 5);
};

// When viewing Company Default, only display pure company hours (team_member_id === null)
// When viewing a specific Team Member, build the complete resolved 7-day schedule
const resolvedSchedule = computed(() => {
    if (!props.selectedMember) {
        return props.companyDefaultHours.map(h => ({
            ...h,
            isCustom: false,
            isInherited: false,
        }));
    }

    const memberCustomMap = new Map();
    props.businessHours.forEach(h => {
        if (h.team_member_id === props.selectedMember.id) {
            memberCustomMap.set(h.day_of_week, h);
        }
    });

    const companyMap = new Map();
    props.companyDefaultHours.forEach(h => {
        companyMap.set(h.day_of_week, h);
    });

    const allDayKeys = [1, 2, 3, 4, 5, 6, 0];
    const result = [];

    allDayKeys.forEach(dayKey => {
        if (memberCustomMap.has(dayKey)) {
            const custom = memberCustomMap.get(dayKey);
            result.push({
                ...custom,
                isCustom: true,
                isInherited: false,
            });
        } else if (companyMap.has(dayKey)) {
            const def = companyMap.get(dayKey);
            result.push({
                ...def,
                isCustom: false,
                isInherited: true,
                inheritedFromId: def.id,
            });
        }
    });

    return result;
});
</script>

<template>
    <div class="card overflow-hidden p-0 shadow-sm">
        <div class="p-5 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">
                        {{ selectedMember ? `Expediente: ${selectedMember.name}` : 'Grade de Expediente Padrão da Empresa' }}
                    </h3>
                    <span
                        v-if="selectedMember"
                        class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30"
                    >
                        <i class="fa-solid fa-user-gear text-[10px] mr-1"></i>
                        Profissional
                    </span>
                    <span
                        v-else
                        class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                    >
                        <i class="fa-solid fa-building text-[10px] mr-1"></i>
                        Padrão da Empresa
                    </span>
                </div>
                <p class="text-xs opacity-60 mt-1">
                    {{ selectedMember
                        ? 'Defina horários e pausas de café específicas para este profissional. Dias não configurados herdam o padrão da empresa automaticamente.'
                        : 'Dias e horários de funcionamento base da sua empresa. Todos os profissionais utilizam estes horários por padrão.'
                    }}
                </p>
            </div>
            <button
                v-if="canManage"
                type="button"
                @click="$emit('open-create')"
                class="btn btn-primary text-xs py-2.5 px-4 self-start sm:self-auto cursor-pointer rounded-xl font-bold shadow-md shadow-indigo-600/20 inline-flex items-center gap-1.5"
            >
                <i class="fa-solid fa-plus text-xs"></i>
                <span>{{ selectedMember ? 'Personalizar Novo Dia' : 'Novo Dia de Expediente' }}</span>
            </button>
        </div>

        <div v-if="resolvedSchedule.length === 0" class="text-center py-12 px-4 text-slate-500">
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
                        <th>Intervalo / Almoço / Café</th>
                        <th>Tipo / Escopo</th>
                        <th>Status</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="hour in resolvedSchedule" :key="(hour.isInherited ? 'inh-' : 'hour-') + hour.id + '-' + hour.day_of_week">
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
                            <div v-if="hour.breaks && hour.breaks.length > 0" class="space-y-1">
                                <div
                                    v-for="(b, bIdx) in hour.breaks"
                                    :key="bIdx"
                                    class="text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5 font-medium"
                                >
                                    <i class="fa-solid fa-mug-hot text-amber-500 text-[11px]"></i>
                                    <span>{{ formatTime(b.opens_at) }} às {{ formatTime(b.closes_at) }}</span>
                                    <span v-if="b.label" class="text-[10px] text-slate-400">({{ b.label }})</span>
                                </div>
                            </div>
                            <div v-else-if="hour.break_opens_at && hour.break_closes_at" class="text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5 font-medium">
                                <i class="fa-solid fa-mug-hot text-amber-500 text-[11px]"></i>
                                <span>{{ formatTime(hour.break_opens_at) }} às {{ formatTime(hour.break_closes_at) }}</span>
                            </div>
                            <span v-else class="text-xs text-slate-400 italic">Sem intervalo</span>
                        </td>
                        <td>
                            <span
                                v-if="hour.isCustom"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30"
                            >
                                <i class="fa-solid fa-user-check text-[9px]"></i>
                                <span>Personalizado</span>
                            </span>
                            <span
                                v-else-if="hour.isInherited"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700"
                                title="Este dia está usando o horário padrão da empresa."
                            >
                                <i class="fa-solid fa-building text-[9px]"></i>
                                <span>Herdado do Padrão</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-500"
                            >
                                <i class="fa-solid fa-building text-[9px]"></i>
                                <span>Padrão Empresa</span>
                            </span>
                        </td>
                        <td>
                            <span :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold', hour.is_active ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'bg-slate-200 dark:bg-slate-800 text-slate-500']">
                                <span :class="['w-1.5 h-1.5 rounded-full', hour.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                {{ hour.is_active ? 'Ativo' : 'Pausado' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <!-- When inherited in member view, offer button to customize this day -->
                                <button
                                    v-if="canManage && hour.isInherited"
                                    type="button"
                                    @click="$emit('customize-day', hour)"
                                    class="btn btn-outline text-[11px] py-1 px-2.5 rounded-lg font-bold cursor-pointer hover:border-indigo-500 hover:text-indigo-600 transition-all"
                                    title="Personalizar este dia especificamente para este profissional"
                                >
                                    <i class="fa-solid fa-plus text-[10px] mr-1"></i>
                                    <span>Personalizar</span>
                                </button>

                                <template v-else-if="canManage">
                                    <button
                                        type="button"
                                        @click="$emit('open-edit', hour)"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-700 text-indigo-500 transition-all cursor-pointer"
                                        title="Editar horário"
                                    >
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="$emit('open-delete', hour)"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-rose-100 dark:hover:bg-rose-950/40 text-rose-500 transition-all cursor-pointer"
                                        :title="hour.isCustom ? 'Restaurar padrão da empresa' : 'Excluir horário'"
                                    >
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
