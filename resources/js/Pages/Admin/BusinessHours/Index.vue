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

const configuredDays = computed(() => props.businessHours.map(h => h.day_of_week));

const showCreateBusinessHourModal = ref(false);
const showEditBusinessHourModal = ref(false);
const showDeleteBusinessHourModal = ref(false);
const showCreateBlockModal = ref(false);
const showEditBlockModal = ref(false);
const showDeleteBlockModal = ref(false);

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

const formatTime = (value) => {
    if (!value) return '';
    return value.substring(0, 5);
};

const openCreateBusinessHourModal = () => {
    const firstAvailable = allDays.find(d => !configuredDays.value.includes(d.key));
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
            <p class="text-xs opacity-60 hidden sm:block truncate">Grade semanal de expediente, intervalos e bloqueios especiais</p>
        </template>

        <div class="space-y-8">
            <!-- Weekly Hours Section -->
            <WeeklyScheduleTable
                :business-hours="businessHours"
                :days-map="daysMap"
                :can-manage="hasPermission('schedules.manage')"
                @open-create="openCreateBusinessHourModal"
                @open-edit="openEditBusinessHourModal"
                @open-delete="openDeleteBusinessHourModal"
            />

            <!-- Blocked Slots Section -->
            <BlockedSlotsSection
                :blocked-slots="blockedSlots"
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
            :configured-days="configuredDays"
            @close="closeCreateBusinessHourModal"
            @submit="submitCreateBusinessHour"
        />

        <!-- Edit Business Hour Modal -->
        <BusinessHourModal
            :show="showEditBusinessHourModal"
            :is-editing="true"
            :form="editBusinessHourForm"
            :all-days="allDays"
            :configured-days="configuredDays"
            @close="closeEditBusinessHourModal"
            @submit="submitEditBusinessHour"
        />

        <!-- Delete Business Hour Modal -->
        <DeleteConfirmModal
            :show="showDeleteBusinessHourModal"
            title="Excluir Expediente"
            :message="`Tem certeza de que deseja excluir o horário de ${deleteHourData?.day_name} (${deleteHourData?.period})?`"
            @close="closeDeleteBusinessHourModal"
            @confirm="submitDeleteBusinessHour"
        />

        <!-- Create Block Modal -->
        <BlockedSlotModal
            :show="showCreateBlockModal"
            :is-editing="false"
            :form="createBlockForm"
            @close="closeCreateBlockModal"
            @submit="submitCreateBlock"
        />

        <!-- Edit Block Modal -->
        <BlockedSlotModal
            :show="showEditBlockModal"
            :is-editing="true"
            :form="editBlockForm"
            @close="closeEditBlockModal"
            @submit="submitEditBlock"
        />

        <!-- Delete Block Modal -->
        <DeleteConfirmModal
            :show="showDeleteBlockModal"
            title="Excluir Bloqueio"
            :message="`Tem certeza de que deseja remover o bloqueio '${deleteBlockData?.reason}'?`"
            @close="closeDeleteBlockModal"
            @confirm="submitDeleteBlock"
        />
    </AdminLayout>
</template>
