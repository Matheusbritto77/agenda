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
    teamMembers: {
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

const reasonPresets = [
    'Feriado Nacional',
    'Reforma / Manutenção',
    'Folga / Férias',
    'Treinamento / Evento',
    'Imprevisto / Emergência',
];

const setReason = (r) => {
    props.form.reason = r;
};

const setScope = (scope) => {
    if (scope === 'company') {
        props.form.team_member_id = null;
    } else if (scope === 'team') {
        if (props.teamMembers.length > 0 && !props.form.team_member_id) {
            props.form.team_member_id = props.teamMembers[0].id;
        }
    }
};

const setQuickRange = (type) => {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const formatDateTimeLocal = (d) => {
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    };

    if (type === 'today_full') {
        const start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0);
        const end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59);
        if (!props.isEditing) {
            props.form.starts_at = formatDateTimeLocal(start);
            props.form.ends_at = formatDateTimeLocal(end);
        } else {
            props.form.start_date = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`;
            props.form.start_time = '00:00';
            props.form.end_date = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`;
            props.form.end_time = '23:59';
        }
    } else if (type === 'tomorrow_full') {
        const tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0);
        const end = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 23, 59);
        if (!props.isEditing) {
            props.form.starts_at = formatDateTimeLocal(tomorrow);
            props.form.ends_at = formatDateTimeLocal(end);
        } else {
            props.form.start_date = `${tomorrow.getFullYear()}-${pad(tomorrow.getMonth() + 1)}-${pad(tomorrow.getDate())}`;
            props.form.start_time = '00:00';
            props.form.end_date = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`;
            props.form.end_time = '23:59';
        }
    } else if (type === 'next_2h') {
        const start = new Date(now.getTime());
        const end = new Date(now.getTime() + 2 * 60 * 60 * 1000);
        if (!props.isEditing) {
            props.form.starts_at = formatDateTimeLocal(start);
            props.form.ends_at = formatDateTimeLocal(end);
        } else {
            props.form.start_date = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`;
            props.form.start_time = `${pad(start.getHours())}:${pad(start.getMinutes())}`;
            props.form.end_date = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`;
            props.form.end_time = `${pad(end.getHours())}:${pad(end.getMinutes())}`;
        }
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
            <div class="liquid-glass-card w-full max-w-2xl p-6 sm:p-8 space-y-6 relative shadow-2xl max-h-[90vh] overflow-y-auto" @click.stop>
                <!-- Header -->
                <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-amber-500 via-rose-500 to-rose-600 text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-rose-500/30 shrink-0">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-black tracking-tight" style="color: var(--text-heading);">
                                {{ isEditing ? 'Editar Bloqueio de Horário' : 'Novo Bloqueio de Horário' }}
                            </h3>
                            <p class="text-xs opacity-60">Impeça agendamentos durante um intervalo específico</p>
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
                    <!-- Scope Selection (Company vs Team Member) -->
                    <div v-if="teamMembers.length > 0" class="space-y-2">
                        <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                            Aplicar Bloqueio Para
                        </label>
                        <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                            <button
                                type="button"
                                @click="setScope('company')"
                                :class="[
                                    'py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                    !form.team_member_id
                                        ? 'bg-white dark:bg-slate-900 text-rose-600 dark:text-rose-400 shadow-sm'
                                        : 'opacity-70 hover:opacity-100 text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                <i class="fa-solid fa-building text-xs"></i>
                                <span>Toda a Empresa</span>
                            </button>
                            <button
                                type="button"
                                @click="setScope('team')"
                                :class="[
                                    'py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                                    form.team_member_id
                                        ? 'bg-white dark:bg-slate-900 text-rose-600 dark:text-rose-400 shadow-sm'
                                        : 'opacity-70 hover:opacity-100 text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                <i class="fa-solid fa-user text-xs"></i>
                                <span>Profissional Específico</span>
                            </button>
                        </div>

                        <!-- Team Member Picker -->
                        <div v-if="form.team_member_id" class="pt-1">
                            <label class="form-label text-xs font-semibold block mb-1 text-slate-600 dark:text-slate-400">
                                Selecione o Profissional
                            </label>
                            <select
                                v-model="form.team_member_id"
                                class="form-control text-xs sm:text-sm rounded-xl font-bold"
                                required
                            >
                                <option v-for="member in teamMembers" :key="member.id" :value="member.id">
                                    {{ member.name }} {{ member.job_title ? `(${member.job_title})` : '' }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="space-y-1.5">
                        <label class="form-label text-xs font-bold uppercase tracking-wider flex items-center gap-1.5" style="color: var(--text-heading);" for="block_reason">
                            <i class="fa-solid fa-tag text-amber-500 text-xs"></i>
                            <span>Motivo do Bloqueio <span class="text-rose-500">*</span></span>
                        </label>
                        <input
                            type="text"
                            id="block_reason"
                            v-model="form.reason"
                            class="form-control text-xs sm:text-sm rounded-xl font-semibold"
                            placeholder="Ex: Feriado Nacional, Reforma, Folga Geral"
                            required
                        />
                        <span v-if="form.errors?.reason" class="text-xs text-rose-500 mt-1 font-medium block">
                            {{ form.errors.reason }}
                        </span>

                        <!-- Reason Presets -->
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <button
                                v-for="r in reasonPresets"
                                :key="r"
                                type="button"
                                @click="setReason(r)"
                                class="px-2 py-0.5 rounded-lg text-[11px] font-semibold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 hover:border-amber-500 hover:text-amber-600 transition-all cursor-pointer"
                            >
                                {{ r }}
                            </button>
                        </div>
                    </div>

                    <!-- Quick Range Buttons -->
                    <div class="space-y-1.5 pt-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Atalhos de Período</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                @click="setQuickRange('today_full')"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 hover:border-rose-500 hover:text-rose-600 transition-all cursor-pointer"
                            >
                                <i class="fa-regular fa-calendar-day mr-1"></i>
                                Hoje o dia todo
                            </button>
                            <button
                                type="button"
                                @click="setQuickRange('tomorrow_full')"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 hover:border-rose-500 hover:text-rose-600 transition-all cursor-pointer"
                            >
                                <i class="fa-regular fa-calendar-plus mr-1"></i>
                                Amanhã o dia todo
                            </button>
                            <button
                                type="button"
                                @click="setQuickRange('next_2h')"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 hover:border-rose-500 hover:text-rose-600 transition-all cursor-pointer"
                            >
                                <i class="fa-regular fa-clock mr-1"></i>
                                Próximas 2 horas
                            </button>
                        </div>
                    </div>

                    <!-- Date & Time Inputs -->
                    <template v-if="!isEditing">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <div class="form-group mb-0 space-y-1">
                                <label class="form-label text-xs font-bold uppercase tracking-wider flex items-center gap-1.5" style="color: var(--text-heading);" for="block_starts_at">
                                    <i class="fa-solid fa-play text-amber-500 text-[10px]"></i>
                                    <span>Início do Bloqueio <span class="text-rose-500">*</span></span>
                                </label>
                                <input
                                    type="datetime-local"
                                    id="block_starts_at"
                                    v-model="form.starts_at"
                                    class="form-control text-xs sm:text-sm rounded-xl font-semibold"
                                    required
                                />
                                <span v-if="form.errors?.starts_at" class="text-xs text-rose-500 mt-1 font-medium block">
                                    {{ form.errors.starts_at }}
                                </span>
                            </div>

                            <div class="form-group mb-0 space-y-1">
                                <label class="form-label text-xs font-bold uppercase tracking-wider flex items-center gap-1.5" style="color: var(--text-heading);" for="block_ends_at">
                                    <i class="fa-solid fa-stop text-rose-500 text-[10px]"></i>
                                    <span>Término do Bloqueio <span class="text-rose-500">*</span></span>
                                </label>
                                <input
                                    type="datetime-local"
                                    id="block_ends_at"
                                    v-model="form.ends_at"
                                    class="form-control text-xs sm:text-sm rounded-xl font-semibold"
                                    required
                                />
                                <span v-if="form.errors?.ends_at" class="text-xs text-rose-500 mt-1 font-medium block">
                                    {{ form.errors.ends_at }}
                                </span>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <div class="p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="form-group mb-0 space-y-1">
                                    <label class="form-label text-[11px] font-bold text-slate-500 dark:text-slate-400">Data de Início</label>
                                    <input type="date" v-model="form.start_date" class="form-control text-xs rounded-xl font-semibold" required />
                                </div>
                                <div class="form-group mb-0 space-y-1">
                                    <label class="form-label text-[11px] font-bold text-slate-500 dark:text-slate-400">Hora de Início</label>
                                    <input type="time" v-model="form.start_time" class="form-control text-xs rounded-xl font-semibold" required />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="form-group mb-0 space-y-1">
                                    <label class="form-label text-[11px] font-bold text-slate-500 dark:text-slate-400">Data de Término</label>
                                    <input type="date" v-model="form.end_date" class="form-control text-xs rounded-xl font-semibold" required />
                                </div>
                                <div class="form-group mb-0 space-y-1">
                                    <label class="form-label text-[11px] font-bold text-slate-500 dark:text-slate-400">Hora de Término</label>
                                    <input type="time" v-model="form.end_time" class="form-control text-xs rounded-xl font-semibold" required />
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Active Toggle in edit mode -->
                    <div v-if="isEditing" class="flex items-center gap-3 p-3 rounded-xl border border-slate-200/80 dark:border-slate-800/80 bg-white/50 dark:bg-slate-900/30">
                        <input
                            type="checkbox"
                            id="modal_block_active"
                            v-model="form.is_active"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                        />
                        <label for="modal_block_active" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                            Bloqueio ativo
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
                            <span>{{ form.processing ? 'Salvando...' : 'Salvar Bloqueio' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
