<script setup>
import { ref, onMounted } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppointmentFilters from './Components/AppointmentFilters.vue';
import AppointmentTable from './Components/AppointmentTable.vue';
import AppointmentCalendarView from './Components/AppointmentCalendarView.vue';
import AppointmentDetailModal from './Components/AppointmentDetailModal.vue';

const page = usePage();

const props = defineProps({
    appointments: {
        type: Array,
        default: () => [],
    },
    status: {
        type: String,
        default: '',
    },
    date: {
        type: String,
        default: '',
    },
    pagination: {
        type: Object,
        default: () => ({}),
    },
});

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    const userPerms = page.props.auth?.permissions || [];
    return userPerms.includes(permission);
};

const activeView = ref('table');
const showDetailModal = ref(false);
const selectedAppointment = ref(null);

const filterForm = useForm({
    status: props.status || '',
    date: props.date || '',
});

const statusForm = useForm({
    status: 'pending',
});

const switchView = (mode) => {
    activeView.value = mode;
    localStorage.setItem('agendae_appointments_view', mode);
};

const submitFilter = () => {
    router.get(route('admin.appointments.index'), filterForm.data(), {
        preserveState: true,
    });
};

const clearFilters = () => {
    filterForm.status = '';
    filterForm.date = '';
    router.get(route('admin.appointments.index'));
};

const openDetailModal = (appointment) => {
    const duration = appointment.service?.duration_minutes ?? 30;
    const servicePrice = Number(appointment.service?.price || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const startTime = (appointment.start_time || '00:00').substring(0, 5);
    const endTime = (appointment.end_time || '00:00').substring(0, 5);
    const dateDisplay = appointment.appointment_date ? new Date(appointment.appointment_date + 'T00:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '-';

    selectedAppointment.value = {
        id: appointment.id,
        customer_name: appointment.customer_name,
        customer_email: appointment.customer_email,
        customer_phone: appointment.customer_phone,
        service_name: appointment.service?.name ?? 'Serviço',
        service_price: servicePrice,
        date: dateDisplay,
        time: `${startTime} às ${endTime}`,
        duration: duration + ' min',
        status: appointment.status,
        notes: appointment.notes ?? 'Nenhuma observação informada.',
    };
    statusForm.status = appointment.status;
    showDetailModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedAppointment.value = null;
    document.body.classList.remove('overflow-hidden');
};

const submitStatusChange = (newStatus) => {
    if (!selectedAppointment.value) return;
    statusForm.status = newStatus;
    statusForm.patch(route('admin.appointments.update-status', selectedAppointment.value.id), {
        onSuccess: () => {
            closeDetailModal();
        },
    });
};

onMounted(() => {
    const savedView = localStorage.getItem('agendae_appointments_view') || 'table';
    activeView.value = savedView;

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && showDetailModal.value) {
            closeDetailModal();
        }
    });
});
</script>

<template>
    <AdminLayout>
        <Head title="Gestão de Agendamentos - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Lista de Agendamentos</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Gerencie as reservas dos seus clientes e seus respectivos status</p>
        </template>

        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight" style="color: var(--text-heading);">Todos os Agendamentos</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Visualize e controle as solicitações de horários</p>
                </div>
            </div>

            <!-- Filters Bar -->
            <AppointmentFilters
                :filter-form="filterForm"
                :active-view="activeView"
                :total-count="appointments.length"
                @submit-filter="submitFilter"
                @clear-filters="clearFilters"
                @switch-view="switchView"
            />

            <!-- Table View -->
            <AppointmentTable
                v-if="activeView === 'table'"
                :appointments="appointments"
                :pagination="pagination"
                @open-detail="openDetailModal"
            />

            <!-- Calendar View -->
            <AppointmentCalendarView
                v-else
                :appointments="appointments"
                @open-detail="openDetailModal"
            />
        </div>

        <!-- Detail Modal -->
        <AppointmentDetailModal
            :show="showDetailModal"
            :appointment="selectedAppointment"
            :status-form="statusForm"
            :can-update-status="hasPermission('appointments.edit')"
            @close="closeDetailModal"
            @status-change="submitStatusChange"
        />
    </AdminLayout>
</template>
