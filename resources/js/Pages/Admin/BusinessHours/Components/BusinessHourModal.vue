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
    teamMembers: {
        type: Array,
        default: () => [],
    },
    allBusinessHours: {
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

const effectiveConfiguredDays = computed(() => {
    const selectedMemberId = props.form.team_member_id ? Number(props.form.team_member_id) : null;
    return props.allBusinessHours
        .filter(h => {
            const hMemberId = h.team_member_id ? Number(h.team_member_id) : null;
            return hMemberId === selectedMemberId;
        })
        .map(h => h.day_of_week);
});

const setScope = (scope) => {
    if (scope === 'company') {
        props.form.team_member_id = null;
    } else if (scope === 'team') {
        if (props.teamMembers.length > 0 && !props.form.team_member_id) {
            props.form.team_member_id = props.teamMembers[0].id;
        }
    }
};

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};

const addBreak = () => {
    if (!Array.isArray(props.form.breaks)) {
        props.form.breaks = [];
    }
    props.form.has_break = true;
    props.form.breaks.push({
        label: props.form.breaks.length === 0 ? 'Almoço' : `Café / Pausa ${props.form.breaks.length + 1}`,
        opens_at: '12:00',
        closes_at: '13:00',
    });
};

const removeBreak = (index) => {
    if (Array.isArray(props.form.breaks)) {
        props.form.breaks.splice(index, 1);
        if (props.form.breaks.length === 0) {
            props.form.has_break = false;
        }
    }
};

const applyPreset = (opens, closes, breaksList = []) => {
    props.form.opens_at = opens;
    props.form.closes_at = closes;
    if (breaksList.length > 0) {
        props.form.has_break = true;
        props.form.breaks = breaksList.map(b => ({ ...b }));
        props.form.break_opens_at = breaksList[0].opens_at;
        props.form.break_closes_at = breaksList[0].closes_at;
    } else {
        props.form.has_break = false;
        props.form.breaks = [];
        props.form.break_opens_at = '';
        props.form.break_closes_at = '';
    }
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 sm:p-6 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div class="liquid-glass-card w-full max-w-3xl p-6 sm:p-8 space-y-6 relative shadow-2xl max-h-[90vh] overflow-y-auto" @click.stop>
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-700 to-violet-600 text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-indigo-600/30 shrink-0">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-xl font-black tracking-tight" style="color: var(--text-heading);">
                                {{ isEditing ? 'Editar Expediente' : 'Novo Dia de Expediente' }}
                            </h3>
                            <p class="text-xs opacity-60 mt-0.5">Defina os horários de abertura, fechamento e pausas de café/almoço</p>
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
                <form @submit.prevent="$emit('submit')" class="space-y-5">
                    
                    <!-- Row 1: Scope & Day of Week (Horizontal 2-Column Grid) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        
                        <!-- Left: Scope Selection (Company vs Team Member) -->
                        <div v-if="teamMembers.length > 0" class="space-y-2">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                Aplicar Horário Para
                            </label>
                            <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                                <button
                                    type="button"
                                    @click="setScope('company')"
                                    :disabled="isEditing"
                                    :class="[
                                        'py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                        !form.team_member_id
                                            ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                            : 'opacity-70 hover:opacity-100 text-slate-600 dark:text-slate-400'
                                    ]"
                                >
                                    <i class="fa-solid fa-building text-xs"></i>
                                    <span>Padrão Empresa</span>
                                </button>
                                <button
                                    type="button"
                                    @click="setScope('team')"
                                    :disabled="isEditing"
                                    :class="[
                                        'py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                        form.team_member_id
                                            ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                            : 'opacity-70 hover:opacity-100 text-slate-600 dark:text-slate-400'
                                    ]"
                                >
                                    <i class="fa-solid fa-user text-xs"></i>
                                    <span>Profissional</span>
                                </button>
                            </div>

                            <!-- Team Member Picker -->
                            <div v-if="form.team_member_id" class="pt-1">
                                <select
                                    v-model="form.team_member_id"
                                    :disabled="isEditing"
                                    class="form-control text-xs sm:text-sm rounded-xl font-bold"
                                    required
                                >
                                    <option v-for="member in teamMembers" :key="member.id" :value="member.id">
                                        {{ member.name }} {{ member.job_title ? `(${member.job_title})` : '' }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Right: Day of Week -->
                        <div class="space-y-2" :class="{ 'md:col-span-2': teamMembers.length === 0 }">
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
                                    :disabled="!isEditing && effectiveConfiguredDays.includes(day.key)"
                                >
                                    {{ day.name }} {{ (!isEditing && effectiveConfiguredDays.includes(day.key)) ? '(Já configurado)' : '' }}
                                </option>
                            </select>
                            <span v-if="form.errors?.day_of_week" class="text-xs text-rose-500 mt-1 font-medium block">
                                {{ form.errors.day_of_week }}
                            </span>
                        </div>
                    </div>

                    <!-- Row 2: Quick Presets Bar -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 space-y-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Preenchimento Rápido com Pausas</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="applyPreset('08:00', '18:00', [{ label: 'Almoço', opens_at: '12:00', closes_at: '13:00' }])"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-indigo-500 hover:text-indigo-600 transition-all cursor-pointer shadow-2xs"
                            >
                                ☀️ 08h às 18h (Almoço 12-13h)
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('09:00', '19:00', [
                                    { label: 'Café da Manhã', opens_at: '10:30', closes_at: '11:00' },
                                    { label: 'Almoço', opens_at: '13:00', closes_at: '14:00' },
                                    { label: 'Café da Tarde', opens_at: '16:30', closes_at: '17:00' }
                                ])"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-indigo-500 hover:text-indigo-600 transition-all cursor-pointer shadow-2xs"
                            >
                                ☕ 09h às 19h (3 Pausas: Manhã, Almoço, Tarde)
                            </button>
                            <button
                                type="button"
                                @click="applyPreset('08:00', '13:00', [])"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-indigo-500 hover:text-indigo-600 transition-all cursor-pointer shadow-2xs"
                            >
                                ⚡ 08h às 13h (Meio Período Sem Pausa)
                            </button>
                        </div>
                    </div>

                    <!-- Row 3: Operating Hours (Opening & Closing) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                    <!-- Row 4: Multiple Break / Interval Manager -->
                    <div class="p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/40 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80 dark:border-slate-800">
                            <div>
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-mug-hot text-sm text-amber-500"></i>
                                    <span class="text-xs sm:text-sm font-extrabold" style="color: var(--text-heading);">Intervalos & Pausas (Café, Almoço, Descanso)</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">Adicione quantas pausas desejar sem quebrar os horários</p>
                            </div>
                            
                            <button
                                type="button"
                                @click="addBreak"
                                class="btn btn-outline text-xs py-1.5 px-3 rounded-xl font-bold self-start sm:self-auto inline-flex items-center gap-1.5 cursor-pointer hover:border-amber-500 hover:text-amber-600 transition-all"
                            >
                                <i class="fa-solid fa-plus text-xs"></i>
                                <span>Adicionar Pausa</span>
                            </button>
                        </div>

                        <!-- Breaks List -->
                        <div v-if="form.breaks && form.breaks.length > 0" class="space-y-3">
                            <div
                                v-for="(b, idx) in form.breaks"
                                :key="idx"
                                class="p-3.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs transition-all"
                            >
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                    <!-- Label / Motivo da Pausa -->
                                    <div class="sm:col-span-5 space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <i class="fa-solid fa-tag text-[10px] text-amber-500"></i>
                                            <span>Nome / Tipo da Pausa</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="b.label"
                                            class="form-control text-xs rounded-xl font-semibold"
                                            placeholder="Ex: Almoço, Café da Tarde"
                                        />
                                    </div>

                                    <!-- Início da Pausa -->
                                    <div class="sm:col-span-3 space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <i class="fa-solid fa-play text-[9px] text-amber-500"></i>
                                            <span>Início</span>
                                        </label>
                                        <input
                                            type="time"
                                            v-model="b.opens_at"
                                            class="form-control text-xs rounded-xl font-bold"
                                            required
                                        />
                                    </div>

                                    <!-- Fim da Pausa -->
                                    <div class="sm:col-span-3 space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <i class="fa-solid fa-stop text-[9px] text-rose-500"></i>
                                            <span>Término</span>
                                        </label>
                                        <input
                                            type="time"
                                            v-model="b.closes_at"
                                            class="form-control text-xs rounded-xl font-bold"
                                            required
                                        />
                                    </div>

                                    <!-- Remove Button -->
                                    <div class="sm:col-span-1 flex items-center justify-end sm:justify-center pt-2 sm:pt-4">
                                        <button
                                            type="button"
                                            @click="removeBreak(idx)"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-all cursor-pointer"
                                            title="Remover esta pausa"
                                        >
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty Breaks State -->
                        <div v-else class="text-center py-4 px-3 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 text-slate-400">
                            <p class="text-xs font-semibold">Nenhuma pausa configurada para este dia.</p>
                            <button
                                type="button"
                                @click="addBreak"
                                class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mt-1 hover:underline cursor-pointer inline-flex items-center gap-1"
                            >
                                <i class="fa-solid fa-plus text-[10px]"></i>
                                <span>Clique aqui para adicionar uma pausa (almoço / café)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Row 5: Status Active -->
                    <div class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800/80 bg-white/50 dark:bg-slate-900/30">
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

                    <!-- Actions Bar -->
                    <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="btn btn-outline py-2.5 px-5 text-xs font-bold rounded-xl cursor-pointer"
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
