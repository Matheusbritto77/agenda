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

// Active Scope Tab: 'company' or member ID (number)
const activeTab = ref('company');

const companyDefaultHours = computed(() => {
    return props.businessHours.filter(h => !h.team_member_id);
});

const selectedMember = computed(() => {
    if (activeTab.value === 'company') return null;
    return props.teamMembers.find(m => m.id === activeTab.value) || null;
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

const filteredBusinessHours = computed(() => {
    if (activeTab.value === 'company') {
        return companyDefaultHours.value;
    }
    return props.businessHours.filter(h => h.team_member_id === activeTab.value);
});

const filteredBlockedSlots = computed(() => {
    if (activeTab.value === 'company') {
        return props.blockedSlots.filter(b => !b.team_member_id);
    }
    return props.blockedSlots.filter(b => b.team_member_id === activeTab.value || !b.team_member_id);
});

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
    const currentMemberId = activeTab.value === 'company' ? null : activeTab.value;
    const configuredDayKeys = props.businessHours
        .filter(h => (h.team_member_id || null) === currentMemberId)
        .map(h => h.day_of_week);

    const firstAvailable = allDays.find(d => !configuredDayKeys.includes(d.key));

    createBusinessHourForm.reset();
    createBusinessHourForm.team_member_id = currentMemberId;
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
    createBusinessHourForm.team_member_id = selectedMember.value ? selectedMember.value.id : null;
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
    createBlockForm.team_member_id = activeTab.value === 'company' ? null : activeTab.value;
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
            <!-- Tabs: Company vs Team Members -->
            <div class="p-2 rounded-2xl bg-slate-200/60 dark:bg-slate-800/60 border border-slate-300/40 dark:border-slate-700/40 backdrop-blur-md">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
                    <!-- Company Tab -->
                    <button
                        type="button"
                        @click="activeTab = 'company'"
                        :class="[
                            'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all duration-200 flex items-center gap-2 shrink-0 cursor-pointer',
                            activeTab === 'company'
                                ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-md shadow-slate-900/5 dark:shadow-black/20'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white'
                        ]"
                    >
                        <div class="w-6 h-6 rounded-lg bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <span>Padrão da Empresa</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 font-bold opacity-75">
                            {{ companyDefaultHours.length }} dias
                        </span>
                    </button>

                    <!-- Team Members Tabs -->
                    <button
                        v-for="member in teamMembers"
                        :key="member.id"
                        type="button"
                        @click="activeTab = member.id"
                        :class="[
                            'px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 shrink-0 cursor-pointer',
                            activeTab === member.id
                                ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-md shadow-slate-900/5 dark:shadow-black/20'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white'
                        ]"
                    >
                        <div class="w-6 h-6 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex items-center justify-center shrink-0">
                            <img v-if="member.avatar_url" :src="member.avatar_url" :alt="member.name" class="w-full h-full object-cover" />
                            <span v-else class="text-[9px] font-black text-indigo-600 dark:text-indigo-400">{{ getInitials(member.name) }}</span>
                        </div>
                        <span>{{ member.name }}</span>
                        <span
                            v-if="memberCustomCountMap[member.id]"
                            class="text-[10px] px-1.5 py-0.5 rounded-full bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 font-extrabold"
                            title="Dias personalizados para este profissional"
                        >
                            {{ memberCustomCountMap[member.id] }} custom
                        </span>
                        <span
                            v-else
                            class="text-[9px] px-1.5 py-0.5 rounded-full bg-slate-200/80 dark:bg-slate-800 font-medium opacity-60"
                        >
                            Padrão
                        </span>
                    </button>
                </div>
            </div>

            <!-- Weekly Hours Section -->
            <WeeklyScheduleTable
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

            <!-- Blocked Slots Section -->
            <BlockedSlotsSection
                :blocked-slots="filteredBlockedSlots"
                :selected-member="selectedMember"
                :can-manage-blocks="hasPermission('schedules.blocks')"
                @open-create-block="openCreateBlockModal"
                @open-edit-block="openEditBlockModal"
                @open-delete-block="openDeleteBlockModal"
            />
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
