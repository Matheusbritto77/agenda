<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();

const props = defineProps({
    businessHours: {
        type: Array,
        default: () => []
    },
    blockedSlots: {
        type: Array,
        default: () => []
    },
});

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    const userPerms = page.props.auth?.permissions || [];
    return userPerms.includes(permission);
};

const daysMap = {
    0: 'Domingo',
    1: 'Segunda-feira',
    2: 'Terça-feira',
    3: 'Quarta-feira',
    4: 'Quinta-feira',
    5: 'Sexta-feira',
    6: 'Sábado'
};

const allDays = [
    { key: 1, name: 'Segunda-feira' },
    { key: 2, name: 'Terça-feira' },
    { key: 3, name: 'Quarta-feira' },
    { key: 4, name: 'Quinta-feira' },
    { key: 5, name: 'Sexta-feira' },
    { key: 6, name: 'Sábado' },
    { key: 0, name: 'Domingo' },
];

const configuredDays = computed(() => props.businessHours.map(h => h.day_of_week));

const showCreateBusinessHourModal = ref(false);
const showEditBusinessHourModal = ref(false);
const showDeleteBusinessHourModal = ref(false);
const showCreateBlockModal = ref(false);
const showEditBlockModal = ref(false);
const showDeleteBlockModal = ref(false);

const selectedHour = ref(null);
const selectedBlock = ref(null);
const deleteHourData = ref(null);
const deleteBlockData = ref(null);

const createBusinessHourForm = useForm({
    day_of_week: '',
    label: '',
    opens_at: '08:00',
    closes_at: '18:00',
    has_break: false,
    break_opens_at: '12:00',
    break_closes_at: '13:00',
    is_active: true,
});

const editBusinessHourForm = useForm({
    id: null,
    day_of_week: '',
    label: '',
    opens_at: '',
    closes_at: '',
    has_break: false,
    break_opens_at: '12:00',
    break_closes_at: '13:00',
    is_active: true,
});

const createBlockForm = useForm({
    starts_at: '',
    ends_at: '',
    reason: '',
});

const editBlockForm = useForm({
    id: null,
    reason: '',
    start_date: '',
    end_date: '',
    start_time: '',
    end_time: '',
    is_active: true,
});

const formatDateTime = (value) => {
    if (!value) return '';
    const d = new Date(value);
    return d.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatTime = (value) => {
    if (!value) return '';
    return value.substring(0, 5);
};

const openCreateBusinessHourModal = () => {
    const firstAvailable = allDays.find(d => !configuredDays.value.includes(d.key));
    createBusinessHourForm.day_of_week = firstAvailable ? String(firstAvailable.key) : '';
    createBusinessHourForm.label = '';
    createBusinessHourForm.opens_at = '08:00';
    createBusinessHourForm.closes_at = '18:00';
    createBusinessHourForm.has_break = false;
    createBusinessHourForm.break_opens_at = '12:00';
    createBusinessHourForm.break_closes_at = '13:00';
    createBusinessHourForm.is_active = true;
    createBusinessHourForm.reset();
    createBusinessHourForm.day_of_week = firstAvailable ? String(firstAvailable.key) : '';
    createBusinessHourForm.opens_at = '08:00';
    createBusinessHourForm.closes_at = '18:00';
    createBusinessHourForm.break_opens_at = '12:00';
    createBusinessHourForm.break_closes_at = '13:00';
    createBusinessHourForm.is_active = true;
    showCreateBusinessHourModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openEditBusinessHourModal = (hour) => {
    selectedHour.value = hour;
    editBusinessHourForm.id = hour.id;
    editBusinessHourForm.day_of_week = String(hour.day_of_week);
    editBusinessHourForm.label = hour.label || '';
    editBusinessHourForm.opens_at = formatTime(hour.opens_at);
    editBusinessHourForm.closes_at = formatTime(hour.closes_at);
    const hasBreak = Boolean(hour.break_opens_at && hour.break_closes_at);
    editBusinessHourForm.has_break = hasBreak;
    editBusinessHourForm.break_opens_at = hour.break_opens_at ? formatTime(hour.break_opens_at) : '12:00';
    editBusinessHourForm.break_closes_at = hour.break_closes_at ? formatTime(hour.break_closes_at) : '13:00';
    editBusinessHourForm.is_active = Boolean(hour.is_active);
    showEditBusinessHourModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openDeleteBusinessHourModal = (hour) => {
    deleteHourData.value = {
        id: hour.id,
        day_name: daysMap[hour.day_of_week] || 'Dia ' + hour.day_of_week,
        label: hour.label || '',
        period: `${formatTime(hour.opens_at)} às ${formatTime(hour.closes_at)}`,
    };
    showDeleteBusinessHourModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const submitCreateBusinessHour = () => {
    createBusinessHourForm.post(route('admin.business-hours.store'), {
        onSuccess: () => {
            showCreateBusinessHourModal.value = false;
            document.body.classList.remove('overflow-hidden');
        },
    });
};

const submitEditBusinessHour = () => {
    editBusinessHourForm.put(route('admin.business-hours.update', editBusinessHourForm.id), {
        onSuccess: () => {
            showEditBusinessHourModal.value = false;
            document.body.classList.remove('overflow-hidden');
        },
    });
};

const submitDeleteBusinessHour = () => {
    router.delete(route('admin.business-hours.destroy', deleteHourData.value.id), {
        onSuccess: () => {
            showDeleteBusinessHourModal.value = false;
            document.body.classList.remove('overflow-hidden');
        },
    });
};

const openCreateBlockModal = () => {
    createBlockForm.reset();
    showCreateBlockModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openEditBlockModal = (block) => {
    selectedBlock.value = block;
    const start = new Date(block.starts_at);
    const end = new Date(block.ends_at);
    editBlockForm.id = block.id;
    editBlockForm.reason = block.reason || '';
    editBlockForm.start_date = start.toISOString().split('T')[0];
    editBlockForm.end_date = end.toISOString().split('T')[0];
    editBlockForm.start_time = start.toTimeString().substring(0, 5);
    editBlockForm.end_time = end.toTimeString().substring(0, 5);
    editBlockForm.is_active = Boolean(block.is_active);
    showEditBlockModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openDeleteBlockModal = (block) => {
    deleteBlockData.value = {
        id: block.id,
        starts_at: formatDateTime(block.starts_at),
        ends_at: formatDateTime(block.ends_at),
        reason: block.reason || 'Sem motivo',
    };
    showDeleteBlockModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const submitCreateBlock = () => {
    createBlockForm.post(route('admin.business-hours.blocks.store'), {
        onSuccess: () => {
            showCreateBlockModal.value = false;
            document.body.classList.remove('overflow-hidden');
        },
    });
};

const submitEditBlock = () => {
    const payload = {
        ...editBlockForm,
        starts_at: `${editBlockForm.start_date}T${editBlockForm.start_time}`,
        ends_at: `${editBlockForm.end_date}T${editBlockForm.end_time}`,
    };
    delete payload.start_date;
    delete payload.end_date;
    delete payload.start_time;
    delete payload.end_time;
    router.put(`/admin/blocks/${editBlockForm.id}`, payload, {
        onSuccess: () => {
            showEditBlockModal.value = false;
            document.body.classList.remove('overflow-hidden');
        },
    });
};

const submitDeleteBlock = () => {
    router.delete(route('admin.business-hours.blocks.destroy', deleteBlockData.value.id), {
        onSuccess: () => {
            showDeleteBlockModal.value = false;
            document.body.classList.remove('overflow-hidden');
        },
    });
};

const closeModal = (modalName) => {
    if (modalName === 'createBusinessHour') showCreateBusinessHourModal.value = false;
    if (modalName === 'editBusinessHour') showEditBusinessHourModal.value = false;
    if (modalName === 'deleteBusinessHour') showDeleteBusinessHourModal.value = false;
    if (modalName === 'createBlock') showCreateBlockModal.value = false;
    if (modalName === 'editBlock') showEditBlockModal.value = false;
    if (modalName === 'deleteBlock') showDeleteBlockModal.value = false;
    document.body.classList.remove('overflow-hidden');
};

const handleBackdropClick = (event, modalName) => {
    if (event.target === event.currentTarget) {
        closeModal(modalName);
    }
};


onMounted(() => {
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            showCreateBusinessHourModal.value = false;
            showEditBusinessHourModal.value = false;
            showDeleteBusinessHourModal.value = false;
            showCreateBlockModal.value = false;
            showEditBlockModal.value = false;
            showDeleteBlockModal.value = false;
            document.body.classList.remove('overflow-hidden');
        }
    });
});
</script>

<template>
    <AdminLayout>
        <Head title="Horários & Bloqueios - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Horários de Funcionamento & Bloqueios</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Configuração do expediente semanal e regras de indisponibilidade</p>
        </template>

        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight" style="color: var(--text-heading);">Expediente & Disponibilidade</h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Defina os horários de atendimento da semana e registre bloqueios pontuais.</p>
                </div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <button v-if="hasPermission('schedules.manage')" type="button" @click="openCreateBusinessHourModal" class="btn btn-primary text-xs sm:text-sm py-2.5 px-4 shadow-lg shadow-indigo-600/30">
                        <i class="fa-solid fa-clock text-xs"></i>
                        <span>+ Novo Horário de Funcionamento</span>
                    </button>
                    <button v-if="hasPermission('schedules.blocks')" type="button" @click="openCreateBlockModal" class="btn btn-outline text-xs sm:text-sm py-2.5 px-4 hover:border-rose-500 hover:text-rose-600">
                        <i class="fa-solid fa-ban text-xs text-rose-500"></i>
                        <span>+ Novo Bloqueio</span>
                    </button>
                    <Link :href="route('admin.dashboard')" class="btn btn-outline text-xs sm:text-sm py-2.5 px-3">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        <span>Painel</span>
                    </Link>
                </div>
            </div>

            <div v-if="hasPermission('schedules.view') || hasPermission('schedules.manage') || hasPermission('schedules.breaks')" class="card overflow-hidden p-0">
                <div class="flex items-center justify-between p-5 border-b flex-wrap gap-3" style="border-color: var(--border);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shadow-md shadow-indigo-500/20">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base sm:text-lg" style="color: var(--text-heading);">Expediente Semanal de Atendimento</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Abertura, fechamento e dias de funcionamento</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button v-if="hasPermission('schedules.manage')" type="button" @click="openCreateBusinessHourModal" class="btn btn-outline py-1.5 px-3 text-xs rounded-xl">
                            <i class="fa-solid fa-plus text-xs text-indigo-500"></i>
                            <span>Adicionar Faixa</span>
                        </button>
                        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            {{ businessHours.length }} Faixas Configuradas
                        </span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th>Dia da Semana</th>
                                <th>Rótulo / Descrição</th>
                                <th>Abertura</th>
                                <th>Fechamento</th>
                                <th>Status</th>
                                <th v-if="hasPermission('schedules.manage') || hasPermission('schedules.breaks')" class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="businessHours.length > 0">
                                <tr v-for="hour in businessHours" :key="hour.id">
                                    <td>
                                        <strong class="text-sm whitespace-nowrap font-extrabold" style="color: var(--text-heading);">{{ daysMap[hour.day_of_week] ?? 'Dia '+hour.day_of_week }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-xs font-medium">{{ hour.label || '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-xs font-semibold">{{ formatTime(hour.opens_at) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-xs font-semibold">{{ formatTime(hour.closes_at) }}</span>
                                    </td>
                                    <td>
                                        <span v-if="hour.is_active" class="badge badge-confirmed">Aberto</span>
                                        <span v-else class="badge badge-cancelled">Fechado</span>
                                    </td>
                                    <td v-if="hasPermission('schedules.manage') || hasPermission('schedules.breaks')" class="text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                v-if="hasPermission('schedules.manage') || hasPermission('schedules.breaks')"
                                                type="button"
                                                class="btn btn-outline py-1.5 px-3 text-xs rounded-xl"
                                                @click="openEditBusinessHourModal(hour)"
                                                title="Editar no Modal com Calculadora"
                                            >
                                                <i class="fa-solid fa-pen-to-square text-xs text-indigo-500"></i>
                                                <span>Editar</span>
                                            </button>
                                            <button
                                                v-if="hasPermission('schedules.manage')"
                                                type="button"
                                                class="btn btn-danger py-1.5 px-2.5 text-xs rounded-xl hover:bg-rose-600 hover:text-white"
                                                @click="openDeleteBusinessHourModal(hour)"
                                                title="Excluir Expediente"
                                            >
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                                <span>Excluir</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="6" class="text-center py-8 text-slate-400">
                                    <div class="space-y-3">
                                        <p>Nenhum horário cadastrado. Os horários padrão do sistema serão aplicados.</p>
                                        <button type="button" @click="openCreateBusinessHourModal" class="btn btn-primary text-xs py-2 px-4 rounded-xl">
                                            <i class="fa-solid fa-plus text-xs"></i>
                                            <span>Cadastrar Primeiro Horário</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="hasPermission('schedules.view') || hasPermission('schedules.blocks')" class="card space-y-5">
                <div class="flex items-center justify-between pb-4 border-b flex-wrap gap-3" style="border-color: var(--border);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg shadow-md shadow-rose-500/20">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base sm:text-lg" style="color: var(--text-heading);">Bloqueios de Agenda e Feriados</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Datas e horários bloqueados para atendimento online</p>
                        </div>
                    </div>

                    <button v-if="hasPermission('schedules.blocks')" type="button" @click="openCreateBlockModal" class="btn btn-primary text-xs py-2 px-3.5 rounded-xl">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Novo Bloqueio</span>
                    </button>
                </div>

                <div>
                    <div v-if="blockedSlots.length === 0" class="text-center py-10 px-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 text-xs font-medium space-y-2">
                        <i class="fa-solid fa-calendar-check text-2xl text-emerald-500 block mb-1"></i>
                        <p class="font-semibold text-sm" style="color: var(--text-heading);">Nenhum bloqueio cadastrado</p>
                        <p class="text-[11px] opacity-70">A agenda está operando com base nas regras normais do expediente semanal.</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th>Início</th>
                                    <th>Término</th>
                                    <th>Motivo</th>
                                    <th>Status</th>
                                    <th v-if="hasPermission('schedules.blocks')" class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="block in blockedSlots" :key="block.id">
                                    <td>
                                        <strong class="text-xs whitespace-nowrap font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ formatDateTime(block.starts_at) }}
                                        </strong>
                                    </td>
                                    <td>
                                        <strong class="text-xs whitespace-nowrap font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ formatDateTime(block.ends_at) }}
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="text-xs opacity-80">{{ block.reason ?? 'Sem motivo especificado' }}</span>
                                    </td>
                                    <td>
                                        <span v-if="block.is_active" class="badge badge-confirmed">Ativo</span>
                                        <span v-else class="badge badge-cancelled">Inativo</span>
                                    </td>
                                    <td v-if="hasPermission('schedules.blocks')" class="text-right whitespace-nowrap space-x-1.5">
                                        <button
                                            v-if="hasPermission('schedules.blocks')"
                                            type="button"
                                            class="btn btn-outline py-1.5 px-3 text-xs rounded-xl hover:border-indigo-500 hover:text-indigo-600"
                                            @click="openEditBlockModal(block)"
                                            title="Editar Bloqueio"
                                        >
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            <span>Editar</span>
                                        </button>

                                        <button
                                            v-if="hasPermission('schedules.blocks')"
                                            type="button"
                                            class="btn btn-danger py-1.5 px-3 text-xs rounded-xl"
                                            @click="openDeleteBlockModal(block)"
                                            title="Remover Bloqueio"
                                        >
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                            <span>Remover</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <Teleport to="body">
            <div v-if="showCreateBusinessHourModal" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click="handleBackdropClick($event, 'createBusinessHour')">
                <div class="liquid-glass-card w-full max-w-lg p-6 sm:p-7 space-y-5 relative" @click.stop>
                    <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shadow-md shadow-indigo-500/20">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-extrabold" style="color: var(--text-heading);">Novo Horário de Funcionamento</h3>
                                <p class="text-xs opacity-60">Adicione uma faixa de horário de atendimento</p>
                            </div>
                        </div>
                        <button type="button" @click="closeModal('createBusinessHour')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitCreateBusinessHour" class="space-y-4">
                        <div class="form-group mb-0">
                            <label class="form-label text-xs font-bold" for="modal_day_of_week">Dia da Semana *</label>
                            <select id="modal_day_of_week" v-model="createBusinessHourForm.day_of_week" class="form-control text-xs sm:text-sm rounded-xl" required>
                                <option v-for="day in allDays" :key="day.key" :value="String(day.key)" :disabled="configuredDays.includes(day.key)">
                                    {{ day.name }}{{ configuredDays.includes(day.key) ? ' (Já cadastrado)' : '' }}
                                </option>
                            </select>
                        </div>

                        <div v-if="createBusinessHourForm.errors" class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-[11px] font-semibold text-rose-600 dark:text-rose-300">
                            {{ Object.values(createBusinessHourForm.errors)[0] }}
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs font-bold" for="modal_bh_label">Rótulo / Descrição (opcional)</label>
                            <input type="text" id="modal_bh_label" v-model="createBusinessHourForm.label" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Ex: Expediente Manhã, Atendimento Principal">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group mb-0">
                                <label class="form-label text-xs font-bold" for="modal_bh_opens_at">Horário de Abertura *</label>
                                <input type="time" id="modal_bh_opens_at" v-model="createBusinessHourForm.opens_at" class="form-control text-xs sm:text-sm rounded-xl" required>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label text-xs font-bold" for="modal_bh_closes_at">Horário de Fechamento *</label>
                                <input type="time" id="modal_bh_closes_at" v-model="createBusinessHourForm.closes_at" class="form-control text-xs sm:text-sm rounded-xl" required>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-3">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" v-model="createBusinessHourForm.has_break" value="1" class="w-4 h-4 rounded text-indigo-600 focus:ring-0">
                                <span class="text-xs font-bold" style="color: var(--text-heading);">
                                    <i class="fa-solid fa-mug-hot text-amber-500 mr-1"></i>
                                    Adicionar Horário de Almoço / Pausa (Opcional)
                                </span>
                            </label>

                            <div v-if="createBusinessHourForm.has_break" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-200 dark:border-slate-800">
                                <div class="form-group mb-0">
                                    <label class="form-label text-[11px] font-bold" for="modal_bh_break_opens_at">Início da Pausa (Almoço)</label>
                                    <input type="time" id="modal_bh_break_opens_at" v-model="createBusinessHourForm.break_opens_at" class="form-control text-xs sm:text-sm rounded-xl" :disabled="!createBusinessHourForm.has_break">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label text-[11px] font-bold" for="modal_bh_break_closes_at">Fim da Pausa (Retorno)</label>
                                    <input type="time" id="modal_bh_break_closes_at" v-model="createBusinessHourForm.break_closes_at" class="form-control text-xs sm:text-sm rounded-xl" :disabled="!createBusinessHourForm.has_break">
                                </div>
                            </div>
                        </div>

                        <div class="pt-1">
                            <label class="flex items-center gap-3 cursor-pointer select-none p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                                <input type="checkbox" v-model="createBusinessHourForm.is_active" id="modal_bh_is_active" value="1" class="w-4 h-4 rounded text-indigo-600 focus:ring-0">
                                <div class="text-xs">
                                    <span class="font-bold block" style="color: var(--text-heading);">Horário Ativo</span>
                                    <span class="opacity-70 text-[11px]">Disponibilizar esta faixa de horário para reservas públicas</span>
                                </div>
                            </label>
                        </div>

                        <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                            <button type="button" @click="closeModal('createBusinessHour')" class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl">Cancelar</button>
                            <button type="submit" class="btn btn-primary py-2.5 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30" :disabled="createBusinessHourForm.processing">
                                <i class="fa-solid fa-check text-xs"></i>
                                <span>{{ createBusinessHourForm.processing ? 'Cadastrando...' : 'Cadastrar Horário' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showEditBusinessHourModal" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click="handleBackdropClick($event, 'editBusinessHour')">
                <div class="liquid-glass-card w-full max-w-lg p-6 sm:p-7 space-y-5 relative" @click.stop>
                    <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shadow-md shadow-indigo-500/20">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-extrabold" style="color: var(--text-heading);">Editar Horário de Funcionamento</h3>
                                <p class="text-xs opacity-60">Ajuste o expediente semanal e intervalo de faixas</p>
                            </div>
                        </div>
                        <button type="button" @click="closeModal('editBusinessHour')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitEditBusinessHour" class="space-y-4">
                        <div class="form-group mb-0">
                            <label class="form-label text-xs font-bold" for="edit_day_of_week">Dia da Semana *</label>
                            <select id="edit_day_of_week" v-model="editBusinessHourForm.day_of_week" class="form-control text-xs sm:text-sm rounded-xl" required :disabled="!hasPermission('schedules.manage')">
                                <option v-for="day in allDays" :key="day.key" :value="String(day.key)">{{ day.name }}</option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs font-bold" for="edit_bh_label">Rótulo / Descrição (opcional)</label>
                            <input type="text" id="edit_bh_label" v-model="editBusinessHourForm.label" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Ex: Expediente Manhã, Atendimento Principal" :disabled="!hasPermission('schedules.manage')">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group mb-0">
                                <label class="form-label text-xs font-bold" for="edit_bh_opens_at">Horário de Abertura *</label>
                                <input type="time" id="edit_bh_opens_at" v-model="editBusinessHourForm.opens_at" class="form-control text-xs sm:text-sm rounded-xl" required :disabled="!hasPermission('schedules.manage')">
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label text-xs font-bold" for="edit_bh_closes_at">Horário de Fechamento *</label>
                                <input type="time" id="edit_bh_closes_at" v-model="editBusinessHourForm.closes_at" class="form-control text-xs sm:text-sm rounded-xl" required :disabled="!hasPermission('schedules.manage')">
                            </div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-3">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" v-model="editBusinessHourForm.has_break" value="1" class="w-4 h-4 rounded text-indigo-600 focus:ring-0">
                                <span class="text-xs font-bold" style="color: var(--text-heading);">
                                    <i class="fa-solid fa-mug-hot text-amber-500 mr-1"></i>
                                    Adicionar Horário de Almoço / Pausa (Opcional)
                                </span>
                            </label>

                            <div v-if="editBusinessHourForm.has_break" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-200 dark:border-slate-800">
                                <div class="form-group mb-0">
                                    <label class="form-label text-[11px] font-bold" for="edit_bh_break_opens_at">Início da Pausa (Almoço)</label>
                                    <input type="time" id="edit_bh_break_opens_at" v-model="editBusinessHourForm.break_opens_at" class="form-control text-xs sm:text-sm rounded-xl">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label text-[11px] font-bold" for="edit_bh_break_closes_at">Fim da Pausa (Retorno)</label>
                                    <input type="time" id="edit_bh_break_closes_at" v-model="editBusinessHourForm.break_closes_at" class="form-control text-xs sm:text-sm rounded-xl">
                                </div>
                            </div>
                        </div>

                        <div class="pt-1">
                            <label class="flex items-center gap-3 cursor-pointer select-none p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                                <input type="checkbox" v-model="editBusinessHourForm.is_active" value="1" class="w-4 h-4 rounded text-indigo-600 focus:ring-0" :disabled="!hasPermission('schedules.manage')">
                                <div class="text-xs">
                                    <span class="font-bold block" style="color: var(--text-heading);">Horário Ativo</span>
                                    <span class="opacity-70 text-[11px]">Disponibilizar esta faixa de horário para reservas públicas</span>
                                </div>
                            </label>
                        </div>

                        <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                            <button type="button" @click="closeModal('editBusinessHour')" class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl">Cancelar</button>
                            <button type="submit" class="btn btn-primary py-2.5 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30" :disabled="editBusinessHourForm.processing">
                                <i class="fa-solid fa-floppy-disk text-xs"></i>
                                <span>{{ editBusinessHourForm.processing ? 'Salvando...' : 'Salvar Alterações' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showDeleteBusinessHourModal" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click="handleBackdropClick($event, 'deleteBusinessHour')">
                <div class="liquid-glass-card w-full max-w-md p-6 space-y-4 relative" @click.stop>
                    <div class="flex items-center gap-3 pb-3 border-b" style="border-color: var(--border);">
                        <div class="w-10 h-10 rounded-2xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-rose-600 dark:text-rose-400">Excluir Expediente</h3>
                            <p class="text-xs opacity-60">Remover dia de atendimento semanal</p>
                        </div>
                    </div>

                    <p class="text-xs sm:text-sm" style="color: var(--text);">
                        Deseja remover o expediente de <strong class="font-bold text-indigo-600 dark:text-indigo-400">{{ deleteHourData?.day_name }}</strong> (<span>{{ deleteHourData?.period }}</span>)? Os clientes não poderão agendar horários neste dia.
                    </p>

                    <div class="pt-3 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                        <button type="button" @click="closeModal('deleteBusinessHour')" class="btn btn-outline py-2 px-3.5 text-xs font-bold rounded-xl">Cancelar</button>
                        <button type="button" @click="submitDeleteBusinessHour" class="btn btn-danger py-2 px-4 text-xs font-bold rounded-xl">
                            <i class="fa-solid fa-trash-can text-xs mr-1"></i>
                            <span>Sim, Excluir</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showCreateBlockModal" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click="handleBackdropClick($event, 'createBlock')">
                <div class="liquid-glass-card w-full max-w-lg p-6 sm:p-7 space-y-5 relative" @click.stop>
                    <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg shadow-md shadow-rose-500/20">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-extrabold" style="color: var(--text-heading);">Novo Bloqueio de Horário</h3>
                                <p class="text-xs opacity-60">Impeça novos agendamentos para um período específico</p>
                            </div>
                        </div>
                        <button type="button" @click="closeModal('createBlock')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitCreateBlock" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group mb-0">
                                <label class="form-label text-xs" for="modal_starts_at">Data / Hora Início *</label>
                                <input type="datetime-local" id="modal_starts_at" v-model="createBlockForm.starts_at" class="form-control text-xs sm:text-sm rounded-xl" required>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label text-xs" for="modal_ends_at">Data / Hora Término *</label>
                                <input type="datetime-local" id="modal_ends_at" v-model="createBlockForm.ends_at" class="form-control text-xs sm:text-sm rounded-xl" required>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_reason">Motivo do Bloqueio</label>
                            <input type="text" id="modal_reason" v-model="createBlockForm.reason" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Ex: Feriado municipal, Manutenção no estabelecimento, Folga programada">
                        </div>

                        <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                            <button type="button" @click="closeModal('createBlock')" class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl">Cancelar</button>
                            <button type="submit" class="btn btn-primary py-2.5 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30" :disabled="createBlockForm.processing">
                                <i class="fa-solid fa-check text-xs"></i>
                                <span>{{ createBlockForm.processing ? 'Cadastrando...' : 'Cadastrar Bloqueio' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showEditBlockModal" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click="handleBackdropClick($event, 'editBlock')">
                <div class="liquid-glass-card w-full max-w-lg p-6 sm:p-7 space-y-5 relative" @click.stop>
                    <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 flex items-center justify-center text-lg shadow-md shadow-amber-500/20">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-extrabold" style="color: var(--text-heading);">Editar Bloqueio de Horário</h3>
                                <p class="text-xs opacity-60">Altere datas, horários ou motivo do bloqueio</p>
                            </div>
                        </div>
                        <button type="button" @click="closeModal('editBlock')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitEditBlock" class="space-y-4">
                        <div class="form-group mb-0">
                            <label class="form-label text-xs font-bold" for="edit_block_reason">Rótulo / Motivo *</label>
                            <input type="text" id="edit_block_reason" v-model="editBlockForm.reason" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Ex: Feriado municipal, Manutenção no estabelecimento, Folga" required>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group mb-0">
                                <label class="form-label text-xs font-bold" for="edit_block_start_date">Data de Início *</label>
                                <input type="date" id="edit_block_start_date" v-model="editBlockForm.start_date" class="form-control text-xs sm:text-sm rounded-xl" required>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label text-xs font-bold" for="edit_block_start_time">Hora de Início *</label>
                                <input type="time" id="edit_block_start_time" v-model="editBlockForm.start_time" class="form-control text-xs sm:text-sm rounded-xl" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-group mb-0">
                                <label class="form-label text-xs font-bold" for="edit_block_end_date">Data de Fim *</label>
                                <input type="date" id="edit_block_end_date" v-model="editBlockForm.end_date" class="form-control text-xs sm:text-sm rounded-xl" required>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label text-xs font-bold" for="edit_block_end_time">Hora de Fim *</label>
                                <input type="time" id="edit_block_end_time" v-model="editBlockForm.end_time" class="form-control text-xs sm:text-sm rounded-xl" required>
                            </div>
                        </div>

                        <div class="form-group mb-0 pt-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold">
                                <input type="checkbox" v-model="editBlockForm.is_active" value="1" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                <span>Bloqueio Ativo</span>
                            </label>
                        </div>

                        <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                            <button type="button" @click="closeModal('editBlock')" class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl">Cancelar</button>
                            <button type="submit" class="btn btn-primary py-2.5 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30">
                                <i class="fa-solid fa-floppy-disk text-xs"></i>
                                <span>Salvar Alterações</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showDeleteBlockModal" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click="handleBackdropClick($event, 'deleteBlock')">
                <div class="liquid-glass-card w-full max-md p-6 space-y-4 relative" @click.stop>
                    <div class="flex items-center gap-3 pb-3 border-b" style="border-color: var(--border);">
                        <div class="w-10 h-10 rounded-2xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-rose-600 dark:text-rose-400">Remover Bloqueio</h3>
                            <p class="text-xs opacity-60">Liberar horário para agendamento</p>
                        </div>
                    </div>

                    <p class="text-xs sm:text-sm" style="color: var(--text);">
                        Deseja remover o bloqueio entre <strong class="font-bold text-indigo-600 dark:text-indigo-400">{{ deleteBlockData?.starts_at }} e {{ deleteBlockData?.ends_at }}</strong>? O período voltará a ficar disponível para os clientes.
                    </p>

                    <div class="pt-3 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                        <button type="button" @click="closeModal('deleteBlock')" class="btn btn-outline py-2 px-3.5 text-xs font-bold rounded-xl">Cancelar</button>
                        <button type="button" @click="submitDeleteBlock" class="btn btn-danger py-2 px-4 text-xs font-bold rounded-xl">
                            <i class="fa-solid fa-trash-can text-xs mr-1"></i>
                            <span>Sim, Remover</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
