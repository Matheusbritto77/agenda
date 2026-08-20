<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DashboardKpis from './Dashboard/Components/DashboardKpis.vue';
import DashboardTimelineDay from './Dashboard/Components/DashboardTimelineDay.vue';
import DashboardMatrixWeek from './Dashboard/Components/DashboardMatrixWeek.vue';
import DashboardAppointmentModal from './Dashboard/Components/DashboardAppointmentModal.vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            today_total: 0,
            confirmed_total: 0,
            completed_total: 0,
            week_total: 0,
        }),
    },
    appointments: {
        type: Array,
        default: () => [],
    },
    weekAppointments: {
        type: Array,
        default: () => [],
    },
    selectedDate: {
        type: String,
        default: () => new Date().toISOString().split('T')[0],
    },
    startOfWeek: {
        type: String,
        default: () => {
            const d = new Date();
            const day = d.getDay();
            d.setDate(d.getDate() - day);
            return d.toISOString().split('T')[0];
        },
    },
    endOfWeek: {
        type: String,
        default: () => {
            const d = new Date();
            const day = d.getDay();
            d.setDate(d.getDate() + (6 - day));
            return d.toISOString().split('T')[0];
        },
    },
    services: {
        type: Array,
        default: () => [],
    },
});

const activeView = ref('day');
const showModal = ref(false);
const selectedAppointment = ref(null);
const datePickerValue = ref(props.selectedDate);

const startHour = 8;
const endHour = 20;
const dayNames = ['DOMINGO', 'SEGUNDA', 'TERÇA', 'QUARTA', 'QUINTA', 'SEXTA', 'SÁBADO'];

const todayStr = new Date().toISOString().split('T')[0];
const isToday = computed(() => props.selectedDate === todayStr);

const selectedDateFormatted = computed(() => {
    const d = new Date(props.selectedDate + 'T00:00:00');
    return d.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
});

const prevDay = computed(() => {
    const d = new Date(props.selectedDate + 'T00:00:00');
    d.setDate(d.getDate() - 1);
    return d.toISOString().split('T')[0];
});

const nextDay = computed(() => {
    const d = new Date(props.selectedDate + 'T00:00:00');
    d.setDate(d.getDate() + 1);
    return d.toISOString().split('T')[0];
});

const weekDays = computed(() => {
    const days = [];
    const start = new Date(props.startOfWeek + 'T00:00:00');
    for (let i = 0; i < 7; i++) {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        const dateStr = d.toISOString().split('T')[0];
        const countForDay = props.weekAppointments.filter(a => {
            const aptDate = new Date(a.appointment_date + 'T00:00:00').toISOString().split('T')[0];
            return aptDate === dateStr;
        }).length;
        days.push({
            dateStr,
            dayNameShort: d.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', ''),
            dayNum: d.getDate(),
            isSel: dateStr === props.selectedDate,
            isCurrentDay: dateStr === todayStr,
            count: countForDay,
        });
    }
    return days;
});

const weekHeaderDays = computed(() => {
    const days = [];
    const start = new Date(props.startOfWeek + 'T00:00:00');
    for (let i = 0; i < 7; i++) {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        const dateStr = d.toISOString().split('T')[0];
        const countThisDay = props.weekAppointments.filter(a => {
            const aptDate = new Date(a.appointment_date + 'T00:00:00').toISOString().split('T')[0];
            return aptDate === dateStr;
        }).length;
        days.push({
            dateStr,
            dayName: dayNames[i],
            dayNum: d.getDate(),
            monthShort: d.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', ''),
            isDayActive: dateStr === props.selectedDate,
            count: countThisDay,
        });
    }
    return days;
});

const timelineHours = computed(() => {
    const hours = [];
    for (let h = startHour; h <= endHour; h++) {
        const hourString = String(h).padStart(2, '0') + ':00';
        const matchingAppointments = props.appointments.filter(apt => {
            const aptHour = parseInt(apt.appointment_time.substring(0, 2), 10);
            return aptHour === h;
        });
        hours.push({
            hour: h,
            hourString,
            matchingAppointments,
        });
    }
    return hours;
});

const weekMatrixHours = computed(() => {
    const hours = [];
    for (let h = startHour; h <= endHour; h++) {
        const hourString = String(h).padStart(2, '0') + ':00';
        const start = new Date(props.startOfWeek + 'T00:00:00');
        const dayCols = [];
        for (let col = 0; col < 7; col++) {
            const colDate = new Date(start);
            colDate.setDate(start.getDate() + col);
            const colDateStr = colDate.toISOString().split('T')[0];
            const cellAppointments = props.weekAppointments.filter(apt => {
                const aptDate = new Date(apt.appointment_date + 'T00:00:00').toISOString().split('T')[0];
                const aptHour = parseInt(apt.appointment_time.substring(0, 2), 10);
                return aptDate === colDateStr && aptHour === h;
            });
            dayCols.push({
                colDateStr,
                cellAppointments,
            });
        }
        hours.push({
            hour: h,
            hourString,
            dayCols,
        });
    }
    return hours;
});

const switchAgendaView = (view) => {
    activeView.value = view;
    localStorage.setItem('agendae_admin_view', view);
};

const goToDate = (date) => {
    router.get(route('admin.dashboard'), { date: date }, { preserveState: true });
};

const handleDatePickerChange = () => {
    if (datePickerValue.value) {
        goToDate(datePickerValue.value);
    }
};

const statusForm = useForm({ status: 'pending' });

const openAppointmentModal = (apt) => {
    const duration = apt.service?.duration_minutes ?? 30;
    const start = new Date(apt.appointment_date + 'T' + apt.appointment_time);
    const end = new Date(start.getTime() + duration * 60000);
    const timeDisplay = start.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    const endTimeDisplay = end.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    const dateDisplay = new Date(apt.appointment_date + 'T00:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const servicePrice = Number(apt.service?.price ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    selectedAppointment.value = {
        id: apt.id,
        client_name: apt.client_name,
        client_email: apt.client_email,
        client_phone: apt.client_phone,
        service_name: apt.service?.name ?? 'Serviço',
        service_price: servicePrice,
        date: dateDisplay,
        time: `${timeDisplay} - ${endTimeDisplay}`,
        duration: duration + ' min',
        status: apt.status,
        notes: apt.notes ?? 'Nenhuma observação',
    };
    statusForm.status = apt.status;
    showModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const closeModal = () => {
    showModal.value = false;
    selectedAppointment.value = null;
    document.body.classList.remove('overflow-hidden');
};

const submitStatusChange = (newStatus) => {
    if (!selectedAppointment.value) return;
    statusForm.status = newStatus;
    statusForm.patch(route('admin.appointments.update-status', selectedAppointment.value.id), {
        onSuccess: () => {
            closeModal();
        },
    });
};

onMounted(() => {
    const savedView = localStorage.getItem('agendae_admin_view');
    if (savedView === 'day' || savedView === 'week') {
        activeView.value = savedView;
    }

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && showModal.value) {
            closeModal();
        }
    });
});
</script>

<template>
    <AdminLayout>
        <Head title="Painel Geral - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Dashboard Operacional</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Visão geral do dia, agendamentos da semana e métricas</p>
        </template>

        <div class="space-y-6">
            <!-- 4 Top KPI Cards -->
            <DashboardKpis :stats="stats" />

            <!-- Agenda View Section -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="goToDate(prevDay)"
                                class="w-8 h-8 rounded-lg border flex items-center justify-center text-xs opacity-70 hover:opacity-100 transition-opacity"
                                title="Dia anterior"
                            >
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <button
                                type="button"
                                @click="goToDate(todayStr)"
                                class="px-2.5 py-1 rounded-lg border text-xs font-bold transition-all"
                                :class="isToday ? 'bg-indigo-600 text-white' : 'opacity-70 hover:opacity-100'"
                            >
                                Hoje
                            </button>
                            <button
                                type="button"
                                @click="goToDate(nextDay)"
                                class="w-8 h-8 rounded-lg border flex items-center justify-center text-xs opacity-70 hover:opacity-100 transition-opacity"
                                title="Próximo dia"
                            >
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>

                        <span class="text-xs sm:text-sm font-extrabold capitalize" style="color: var(--text-heading);">
                            {{ selectedDateFormatted }}
                        </span>
                    </div>

                    <!-- Day / Week switch -->
                    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl self-start sm:self-auto">
                        <button
                            type="button"
                            @click="switchAgendaView('day')"
                            :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', activeView === 'day' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'opacity-60 hover:opacity-100']"
                        >
                            Dia
                        </button>
                        <button
                            type="button"
                            @click="switchAgendaView('week')"
                            :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', activeView === 'week' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'opacity-60 hover:opacity-100']"
                        >
                            Semana
                        </button>
                    </div>
                </div>

                <!-- Timeline / Matrix View -->
                <DashboardTimelineDay
                    v-if="activeView === 'day'"
                    :timeline-hours="timelineHours"
                    @open-appointment="openAppointmentModal"
                />

                <DashboardMatrixWeek
                    v-else
                    :week-header-days="weekHeaderDays"
                    :week-matrix-hours="weekMatrixHours"
                    @open-appointment="openAppointmentModal"
                />
            </div>
        </div>

        <!-- Appointment Status / Details Modal -->
        <DashboardAppointmentModal
            :show="showModal"
            :appointment="selectedAppointment"
            :status-form="statusForm"
            @close="closeModal"
            @status-change="submitStatusChange"
        />
    </AdminLayout>
</template>
