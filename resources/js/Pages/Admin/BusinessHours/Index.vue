<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import WeeklyScheduleTable from './Components/WeeklyScheduleTable.vue';
import BlockedSlotsSection from './Components/BlockedSlotsSection.vue';
import BusinessHourModal from './Components/BusinessHourModal.vue';
import BlockedSlotModal from './Components/BlockedSlotModal.vue';
import DeleteConfirmModal from './Components/DeleteConfirmModal.vue';

const page = usePage();

const props = defineProps({
    businessHours: {
        type: Array,
        default: () => [],
    },
    blockedSlots: {
        type: Array,
        default: () => [],
    },
    teamMembers: {
        type: Array,
        default: () => [],
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
    6: 'Sábado',
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

// Main Navigation Tab: 'company' | 'team' | 'blocks'
const mainTab = ref('company');

// Selected Team Member ID when in 'team' tab
const selectedMemberId = ref(props.teamMembers.length > 0 ? props.teamMembers[0].id : null);

const selectedMember = computed(() => {
    if (mainTab.value !== 'team' || !selectedMemberId.value) return null;
    return props.teamMembers.find(m => m.id === selectedMemberId.value) || null;
});

const companyDefaultHours = computed(() => {
    return props.businessHours.filter(h => !h.team_member_id);
});

const memberCustomCountMap = computed(() => {
    const map = {};
    props.businessHours.forEach(h => {
        if (h.team_member_id) {
            map[h.team_member_id] = (map[h.team_member_id] || 0) + 1;
        }
    });
    return map;
});

const companyBlocks = computed(() => {
    return props.blockedSlots.filter(b => !b.team_member_id);
});

const selectedMemberBlocks = computed(() => {
    if (!selectedMemberId.value) return [];
    return props.blockedSlots.filter(b => b.team_member_id === selectedMemberId.value);
});

// Modal States
const showCreateBusinessHourModal = ref(false);
const showEditBusinessHourModal = ref(false);
const showDeleteBusinessHourModal = ref(false);
const showCreateBlockModal = ref(false);
const showEditBlockModal = ref(false);
const showDeleteBlockModal = ref(false);

const deleteHourData = ref(null);
const deleteBlockData = ref(null);

const createBusinessHourForm = useForm({
    team_member_id: null,
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
    team_member_id: null,
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
    team_member_id: null,
    starts_at: '',
    ends_at: '',
    reason: '',
});

const editBlockForm = useForm({
    id: null,
    team_member_id: null,
    reason: '',
    start_date: '',
    end_date: '',
    start_time: '',
    end_time: '',
    is_active: true,
});

const formatTime = (value) => {
    if (!value) return '';
    return value.substring(0, 5);
};

const getInitials = (name) => {
    if (!name) return 'PR';
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(w => w[0])
        .join('')
        .toUpperCase();
};

const openCreateBusinessHourModal = () => {
    const targetMemberId = mainTab.value === 'team' ? selectedMemberId.value : null;
    const configuredDayKeys = props.businessHours
        .filter(h => (h.team_member_id || null) === targetMemberId)
        .map(h => h.day_of_week);

    const firstAvailable = allDays.find(d => !configuredDayKeys.includes(d.key));

    createBusinessHourForm.reset();
    createBusinessHourForm.team_member_id = targetMemberId;
    createBusinessHourForm.day_of_week = firstAvailable ? String(firstAvailable.key) : '';
    createBusinessHourForm.opens_at = '08:00';
    createBusinessHourForm.closes_at = '18:00';
    createBusinessHourForm.has_break = false;
    createBusinessHourForm.break_opens_at = '12:00';
    createBusinessHourForm.break_closes_at = '13:00';
    createBusinessHourForm.is_active = true;

    showCreateBusinessHourModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const handleCustomizeDay = (hour) => {
    createBusinessHourForm.reset();
    createBusinessHourForm.team_member_id = selectedMemberId.value;
    createBusinessHourForm.day_of_week = String(hour.day_of_week);
    createBusinessHourForm.label = hour.label || '';
    createBusinessHourForm.opens_at = formatTime(hour.opens_at);
    createBusinessHourForm.closes_at = formatTime(hour.closes_at);
    const hasBreak = Boolean(hour.break_opens_at && hour.break_closes_at);
    createBusinessHourForm.has_break = hasBreak;
    createBusinessHourForm.break_opens_at = hour.break_opens_at ? formatTime(hour.break_opens_at) : '12:00';
    createBusinessHourForm.break_closes_at = hour.break_closes_at ? formatTime(hour.break_closes_at) : '13:00';
    createBusinessHourForm.is_active = Boolean(hour.is_active);

    showCreateBusinessHourModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openEditBusinessHourModal = (hour) => {
    editBusinessHourForm.id = hour.id;
    editBusinessHourForm.team_member_id = hour.team_member_id || null;
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
        isCustom: Boolean(hour.team_member_id),
        memberName: hour.team_member?.name || null,
    };
    showDeleteBusinessHourModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const submitCreateBusinessHour = () => {
    createBusinessHourForm.post(route('admin.business-hours.store'), {
        onSuccess: () => {
            closeCreateBusinessHourModal();
        },
    });
};

const submitEditBusinessHour = () => {
    editBusinessHourForm.put(route('admin.business-hours.update', editBusinessHourForm.id), {
        onSuccess: () => {
            closeEditBusinessHourModal();
        },
    });
};

const submitDeleteBusinessHour = () => {
    router.delete(route('admin.business-hours.destroy', deleteHourData.value.id), {
        onSuccess: () => {
            closeDeleteBusinessHourModal();
        },
    });
};

const openCreateBlockModal = () => {
    createBlockForm.reset();
    createBlockForm.team_member_id = mainTab.value === 'team' ? selectedMemberId.value : null;
    showCreateBlockModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openEditBlockModal = (block) => {
    const start = new Date(block.starts_at);
    const end = new Date(block.ends_at);
    editBlockForm.id = block.id;
    editBlockForm.team_member_id = block.team_member_id || null;
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
        reason: block.reason || 'Sem motivo',
        scope: block.team_member?.name ? `do profissional ${block.team_member.name}` : 'da empresa',
    };
    showDeleteBlockModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const submitCreateBlock = () => {
    createBlockForm.post(route('admin.business-hours.blocks.store'), {
        onSuccess: () => {
            closeCreateBlockModal();
        },
    });
};

const submitEditBlock = () => {
    const payload = {
        ...editBlockForm.data(),
        starts_at: `${editBlockForm.start_date}T${editBlockForm.start_time}`,
        ends_at: `${editBlockForm.end_date}T${editBlockForm.end_time}`,
    };
    delete payload.start_date;
    delete payload.end_date;
    delete payload.start_time;
    delete payload.end_time;

    router.put(route('admin.business-hours.blocks.update', editBlockForm.id), payload, {
        onSuccess: () => {
            closeEditBlockModal();
        },
    });
};

const submitDeleteBlock = () => {
    router.delete(route('admin.business-hours.blocks.destroy', deleteBlockData.value.id), {
        onSuccess: () => {
            closeDeleteBlockModal();
        },
    });
};

const closeCreateBusinessHourModal = () => {
    showCreateBusinessHourModal.value = false;
    document.body.classList.remove('overflow-hidden');
};

const closeEditBusinessHourModal = () => {
    showEditBusinessHourModal.value = false;
    document.body.classList.remove('overflow-hidden');
};

const closeDeleteBusinessHourModal = () => {
    showDeleteBusinessHourModal.value = false;
    deleteHourData.value = null;
    document.body.classList.remove('overflow-hidden');
};

const closeCreateBlockModal = () => {
    showCreateBlockModal.value = false;
    document.body.classList.remove('overflow-hidden');
};

const closeEditBlockModal = () => {
    showEditBlockModal.value = false;
    document.body.classList.remove('overflow-hidden');
};

const closeDeleteBlockModal = () => {
    showDeleteBlockModal.value = false;
    deleteBlockData.value = null;
    document.body.classList.remove('overflow-hidden');
};

onMounted(() => {
    document.body.classList.remove('overflow-hidden');
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCreateBusinessHourModal();
            closeEditBusinessHourModal();
            closeDeleteBusinessHourModal();
            closeCreateBlockModal();
            closeEditBlockModal();
            closeDeleteBlockModal();
        }
    });
});

onUnmounted(() => {
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <AdminLayout>
        <Head title="Horários & Bloqueios - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Horários de Funcionamento</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Grade de expediente por profissional, pausas de café e bloqueios</p>
        </template>

        <div class="space-y-6">
            <!-- Navigation Tabs (Main Level) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-2 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
                    <!-- Tab 1: Padrão da Empresa -->
                    <button
                        type="button"
                        @click="mainTab = 'company'"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 shrink-0 cursor-pointer',
                            mainTab === 'company'
                                ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white'
                        ]"
                    >
                        <i class="fa-solid fa-building text-xs"></i>
                        <span>Padrão da Empresa</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 font-bold opacity-80">
                            {{ companyDefaultHours.length }} dias
                        </span>
                    </button>

                    <!-- Tab 2: Equipe & Profissionais -->
                    <button
                        type="button"
                        @click="mainTab = 'team'"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 shrink-0 cursor-pointer',
                            mainTab === 'team'
                                ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white'
                        ]"
                    >
                        <i class="fa-solid fa-users text-xs"></i>
                        <span>Horários por Profissional</span>
                        <span v-if="teamMembers.length > 0" class="text-[10px] px-1.5 py-0.5 rounded-full bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 font-extrabold">
                            {{ teamMembers.length }}
                        </span>
                    </button>

                    <!-- Tab 3: Bloqueios & Feriados -->
                    <button
                        type="button"
                        @click="mainTab = 'blocks'"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 shrink-0 cursor-pointer',
                            mainTab === 'blocks'
                                ? 'bg-white dark:bg-slate-900 text-rose-600 dark:text-rose-400 shadow-sm'
                                : 'text-slate-600 dark:text-slate-400 hover:text-rose-600 dark:hover:text-white'
                        ]"
                    >
                        <i class="fa-solid fa-ban text-xs"></i>
                        <span>Bloqueios & Feriados</span>
                        <span v-if="blockedSlots.length > 0" class="text-[10px] px-1.5 py-0.5 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400 font-extrabold">
                            {{ blockedSlots.length }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- VIEW 1: PADRÃO DA EMPRESA -->
            <div v-if="mainTab === 'company'" class="space-y-6">
                <!-- Info Banner -->
                <div class="p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-start sm:items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <div class="flex-1 min-w-0 text-xs sm:text-sm">
                        <p class="font-bold text-indigo-950 dark:text-indigo-200">
                            Estes são os horários e intervalos padrão da empresa.
                        </p>
                        <p class="opacity-75 text-indigo-900/80 dark:text-indigo-300/80 text-xs mt-0.5">
                            Todos os membros da equipe que não tiverem uma agenda própria configurada na guia "Horários por Profissional" herdarão estes horários.
                        </p>
                    </div>
                </div>

                <!-- Company Weekly Schedule Table -->
                <WeeklyScheduleTable
                    :business-hours="companyDefaultHours"
                    :company-default-hours="companyDefaultHours"
                    :selected-member="null"
                    :days-map="daysMap"
                    :can-manage="hasPermission('schedules.manage')"
                    @open-create="openCreateBusinessHourModal"
                    @open-edit="openEditBusinessHourModal"
                    @open-delete="openDeleteBusinessHourModal"
                />

                <!-- Company Blocked Slots -->
                <BlockedSlotsSection
                    :blocked-slots="companyBlocks"
                    :selected-member="null"
                    :can-manage-blocks="hasPermission('schedules.blocks')"
                    @open-create-block="openCreateBlockModal"
                    @open-edit-block="openEditBlockModal"
                    @open-delete-block="openDeleteBlockModal"
                />
            </div>

            <!-- VIEW 2: HORÁRIOS POR PROFISSIONAL -->
            <div v-else-if="mainTab === 'team'" class="space-y-6">
                <!-- If No Team Members registered -->
                <div v-if="teamMembers.length === 0" class="card text-center py-12 px-4 text-slate-500">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center mx-auto mb-3 text-xl">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h4 class="text-sm font-bold" style="color: var(--text-heading);">Nenhum membro na equipe</h4>
                    <p class="text-xs opacity-70 mt-1 max-w-md mx-auto">
                        Cadastre profissionais na aba de Equipe para definir expedientes e pausas de café exclusivas para cada um.
                    </p>
                </div>

                <div v-else class="space-y-6">
                    <!-- Team Member Horizontal Selector -->
                    <div class="p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 px-1">
                            Selecione o Profissional para gerenciar a agenda
                        </div>
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                            <button
                                v-for="member in teamMembers"
                                :key="member.id"
                                type="button"
                                @click="selectedMemberId = member.id"
                                :class="[
                                    'p-2.5 pr-4 rounded-xl text-xs font-bold transition-all flex items-center gap-3 shrink-0 cursor-pointer border',
                                    selectedMemberId === member.id
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-600/25'
                                        : 'bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:border-indigo-400'
                                ]"
                            >
                                <div class="w-8 h-8 rounded-full overflow-hidden bg-white/20 flex items-center justify-center shrink-0">
                                    <img v-if="member.avatar_url" :src="member.avatar_url" :alt="member.name" class="w-full h-full object-cover" />
                                    <span v-else class="text-xs font-black" :class="selectedMemberId === member.id ? 'text-white' : 'text-indigo-600 dark:text-indigo-400'">
                                        {{ getInitials(member.name) }}
                                    </span>
                                </div>
                                <div class="text-left">
                                    <div class="font-extrabold leading-tight">{{ member.name }}</div>
                                    <div class="text-[10px] opacity-80 font-normal truncate max-w-[140px]">
                                        {{ member.job_title || 'Profissional' }}
                                    </div>
                                </div>
                                <span
                                    v-if="memberCustomCountMap[member.id]"
                                    :class="[
                                        'text-[10px] px-2 py-0.5 rounded-full font-black ml-1',
                                        selectedMemberId === member.id ? 'bg-white text-indigo-700' : 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400'
                                    ]"
                                >
                                    {{ memberCustomCountMap[member.id] }} custom
                                </span>
                                <span
                                    v-else
                                    :class="[
                                        'text-[9px] px-1.5 py-0.5 rounded-full font-medium ml-1',
                                        selectedMemberId === member.id ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500'
                                    ]"
                                >
                                    Padrão
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Selected Member Schedule Table -->
                    <WeeklyScheduleTable
                        v-if="selectedMember"
                        :business-hours="props.businessHours"
                        :company-default-hours="companyDefaultHours"
                        :selected-member="selectedMember"
                        :days-map="daysMap"
                        :can-manage="hasPermission('schedules.manage')"
                        @open-create="openCreateBusinessHourModal"
                        @open-edit="openEditBusinessHourModal"
                        @open-delete="openDeleteBusinessHourModal"
                        @customize-day="handleCustomizeDay"
                    />

                    <!-- Selected Member Blocked Slots -->
                    <BlockedSlotsSection
                        v-if="selectedMember"
                        :blocked-slots="selectedMemberBlocks"
                        :selected-member="selectedMember"
                        :can-manage-blocks="hasPermission('schedules.blocks')"
                        @open-create-block="openCreateBlockModal"
                        @open-edit-block="openEditBlockModal"
                        @open-delete-block="openDeleteBlockModal"
                    />
                </div>
            </div>

            <!-- VIEW 3: TODOS OS BLOQUEIOS -->
            <div v-else-if="mainTab === 'blocks'" class="space-y-6">
                <BlockedSlotsSection
                    :blocked-slots="props.blockedSlots"
                    :selected-member="null"
                    :can-manage-blocks="hasPermission('schedules.blocks')"
                    @open-create-block="openCreateBlockModal"
                    @open-edit-block="openEditBlockModal"
                    @open-delete-block="openDeleteBlockModal"
                />
            </div>
        </div>

        <!-- Create Business Hour Modal -->
        <BusinessHourModal
            :show="showCreateBusinessHourModal"
            :is-editing="false"
            :form="createBusinessHourForm"
            :all-days="allDays"
            :team-members="teamMembers"
            :all-business-hours="businessHours"
            @close="closeCreateBusinessHourModal"
            @submit="submitCreateBusinessHour"
        />

        <!-- Edit Business Hour Modal -->
        <BusinessHourModal
            :show="showEditBusinessHourModal"
            :is-editing="true"
            :form="editBusinessHourForm"
            :all-days="allDays"
            :team-members="teamMembers"
            :all-business-hours="businessHours"
            @close="closeEditBusinessHourModal"
            @submit="submitEditBusinessHour"
        />

        <!-- Delete Business Hour Modal -->
        <DeleteConfirmModal
            :show="showDeleteBusinessHourModal"
            :title="deleteHourData?.isCustom ? 'Restaurar Padrão da Empresa' : 'Excluir Expediente'"
            :message="deleteHourData?.isCustom
                ? `Deseja remover o horário personalizado de ${deleteHourData?.day_name} do profissional ${deleteHourData?.memberName}? Ele passará a usar o horário padrão da empresa.`
                : `Tem certeza de que deseja excluir o horário de ${deleteHourData?.day_name} (${deleteHourData?.period})?`
            "
            @close="closeDeleteBusinessHourModal"
            @confirm="submitDeleteBusinessHour"
        />

        <!-- Create Block Modal -->
        <BlockedSlotModal
            :show="showCreateBlockModal"
            :is-editing="false"
            :form="createBlockForm"
            :team-members="teamMembers"
            @close="closeCreateBlockModal"
            @submit="submitCreateBlock"
        />

        <!-- Edit Block Modal -->
        <BlockedSlotModal
            :show="showEditBlockModal"
            :is-editing="true"
            :form="editBlockForm"
            :team-members="teamMembers"
            @close="closeEditBlockModal"
            @submit="submitEditBlock"
        />

        <!-- Delete Block Modal -->
        <DeleteConfirmModal
            :show="showDeleteBlockModal"
            title="Excluir Bloqueio"
            :message="`Tem certeza de que deseja remover o bloqueio '${deleteBlockData?.reason}' (${deleteBlockData?.scope})?`"
            @close="closeDeleteBlockModal"
            @confirm="submitDeleteBlock"
        />
    </AdminLayout>
</template>
