<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            today_total: 0,
            confirmed_total: 0,
            completed_total: 0,
            week_total: 0,
        })
    },
    appointments: {
        type: Array,
        default: () => []
    },
    weekAppointments: {
        type: Array,
        default: () => []
    },
    selectedDate: {
        type: String,
        default: () => new Date().toISOString().split('T')[0]
    },
    startOfWeek: {
        type: String,
        default: () => {
            const d = new Date();
            const day = d.getDay();
            d.setDate(d.getDate() - day);
            return d.toISOString().split('T')[0];
        }
    },
    endOfWeek: {
        type: String,
        default: () => {
            const d = new Date();
            const day = d.getDay();
            d.setDate(d.getDate() + (6 - day));
            return d.toISOString().split('T')[0];
        }
    },
    services: {
        type: Array,
        default: () => []
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

const selectedDateObj = computed(() => new Date(props.selectedDate + 'T00:00:00'));

const selectedDateFormatted = computed(() => {
    const d = selectedDateObj.value;
    return d.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
});

const todayDateFormatted = computed(() => {
    return new Date().toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
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

const startOfWeekFormatted = computed(() => {
    return new Date(props.startOfWeek + 'T00:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
});

const endOfWeekFormatted = computed(() => {
    return new Date(props.endOfWeek + 'T00:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
});

const statusClass = (status) => {
    switch (status) {
        case 'confirmed': return 'event-confirmed';
        case 'completed': return 'event-completed';
        case 'cancelled': return 'event-cancelled';
        default: return 'event-pending';
    }
};

const statusLabel = (status) => {
    switch (status) {
        case 'confirmed': return 'Confirmado';
        case 'completed': return 'Concluído';
        case 'cancelled': return 'Cancelado';
        default: return 'Pendente';
    }
};

const switchAgendaView = (view) => {
    activeView.value = view;
    localStorage.setItem('agendae_admin_view', view);
};

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
        time: activeView.value === 'week' ? timeDisplay : `${timeDisplay} - ${endTimeDisplay}`,
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

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        closeModal();
    }
};

const whatsAppUrl = computed(() => {
    if (!selectedAppointment.value?.client_phone) return '#';
    const cleanPhone = selectedAppointment.value.client_phone.replace(/\D/g, '');
    return 'https://wa.me/55' + cleanPhone;
});

const navigateDate = (date) => {
    router.get(route('admin.dashboard', { date }));
};

const onDateChange = (event) => {
    const value = event.target.value;
    if (value) {
        router.get(route('admin.dashboard', { date: value }));
    }
};

const statusForm = useForm({
    status: 'pending',
});

const submitStatusChange = (newStatus) => {
    if (!selectedAppointment.value) return;
    statusForm.status = newStatus;
    statusForm.patch(route('admin.appointments.status.update', selectedAppointment.value.id), {
        onSuccess: () => {
            closeModal();
        },
        onError: (errors) => {
            console.error('Status update error:', errors);
        },
    });
};

onMounted(() => {
    const savedView = localStorage.getItem('agendae_admin_view') || 'day';
    activeView.value = savedView;
    datePickerValue.value = props.selectedDate;

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && showModal.value) {
            closeModal();
        }
    });
});

const getAppointmentTableTime = (apt) => {
    return new Date(apt.appointment_date + 'T' + apt.appointment_time).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
};

const getAppointmentServiceDuration = (apt) => {
    return (apt.service?.duration_minutes ?? 0) + ' min';
};

const getAppointmentServicePrice = (apt) => {
    return Number(apt.service?.price ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <AdminLayout>
        <Head title="Agenda Visual & Painel - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Agenda Operacional</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Visualização de compromissos diários e semanais em tempo real</p>
        </template>

        <div class="space-y-6 max-w-full overflow-x-hidden">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="card p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Hoje</span>
                        <h3 class="text-2xl font-extrabold" style="color: var(--text-heading);">{{ stats.today_total }}</h3>
                        <span class="text-[11px] text-indigo-500 dark:text-indigo-400 font-semibold">{{ todayDateFormatted }}</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                </div>

                <div class="card p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Confirmados</span>
                        <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ stats.confirmed_total }}</h3>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">Garantidos</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="card p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Concluídos</span>
                        <h3 class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ stats.completed_total }}</h3>
                        <span class="text-[11px] text-blue-500 font-semibold">Finalizados</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-flag-checkered"></i>
                    </div>
                </div>

                <div class="card p-4 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Na Semana</span>
                        <h3 class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ stats.week_total }}</h3>
                        <span class="text-[11px] text-amber-500 font-semibold">Acumulado</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-calendar-week"></i>
                    </div>
                </div>
            </div>

            <div class="card p-4 sm:p-5">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

                    <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                        <button @click="navigateDate(prevDay)" class="btn btn-outline py-2 px-3 text-xs" title="Dia Anterior">
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </button>

                        <button
                            @click="navigateDate(todayStr)"
                            :class="['btn py-2 px-3.5 text-xs font-bold', isToday ? 'btn-primary' : 'btn-outline']"
                        >
                            Hoje
                        </button>

                        <button @click="navigateDate(nextDay)" class="btn btn-outline py-2 px-3 text-xs" title="Próximo Dia">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>

                        <div class="flex items-center gap-2 ml-1">
                            <h2 class="text-base sm:text-lg font-extrabold tracking-tight capitalize" style="color: var(--text-heading);">
                                {{ selectedDateFormatted }}
                            </h2>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                        <div class="relative">
                            <input
                                type="date"
                                :value="datePickerValue"
                                @change="onDateChange"
                                class="form-control text-xs py-2 px-2.5 rounded-xl cursor-pointer w-auto"
                            >
                        </div>

                        <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800" role="tablist">
                            <button
                                type="button"
                                @click="switchAgendaView('day')"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                    activeView === 'day'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'opacity-70 hover:opacity-100'
                                ]"
                            >
                                <i class="fa-solid fa-calendar-day mr-1"></i> Dia
                            </button>
                            <button
                                type="button"
                                @click="switchAgendaView('week')"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                    activeView === 'week'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'opacity-70 hover:opacity-100'
                                ]"
                            >
                                <i class="fa-solid fa-calendar-week mr-1"></i> Grade Semanal (DOM-SÁB)
                            </button>
                            <button
                                type="button"
                                @click="switchAgendaView('table')"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                    activeView === 'table'
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'opacity-70 hover:opacity-100'
                                ]"
                            >
                                <i class="fa-solid fa-list-ul mr-1"></i> Lista
                            </button>
                        </div>
                    </div>

                </div>

                <div class="mt-4 pt-4 border-t overflow-x-auto pb-1 custom-scrollbar" style="border-color: var(--border);">
                    <div class="flex items-center gap-2 min-w-max sm:min-w-0 sm:grid sm:grid-cols-7">
                        <Link
                            v-for="(day, idx) in weekDays"
                            :key="idx"
                            :href="route('admin.dashboard', { date: day.dateStr })"
                            :class="[
                                'weekday-tab-btn border rounded-xl p-2.5 text-center flex flex-col items-center justify-center min-w-[76px] sm:min-w-0 flex-1 relative transition-all',
                                day.isSel
                                    ? 'active border-slate-200 dark:border-slate-800'
                                    : 'bg-slate-100 dark:bg-slate-900/80 hover:bg-slate-200 dark:hover:bg-slate-800 border-slate-200 dark:border-slate-800'
                            ]"
                        >
                            <span class="text-[10px] uppercase font-bold tracking-wider opacity-70 block">
                                {{ day.dayNameShort }}
                            </span>
                            <span :class="['text-base font-extrabold block my-0.5', day.isCurrentDay && !day.isSel ? 'text-indigo-500 font-black' : '']">
                                {{ day.dayNum }}
                            </span>
                            <div class="flex items-center gap-1">
                                <template v-if="day.count > 0">
                                    <span :class="['w-1.5 h-1.5 rounded-full', day.isSel ? 'bg-white' : 'bg-emerald-500']"></span>
                                    <span class="text-[10px] font-semibold">{{ day.count }}</span>
                                </template>
                                <span v-else class="text-[10px] opacity-40">-</span>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <div v-if="activeView === 'day'" class="card p-0 overflow-hidden">
                <div class="p-4 border-b flex items-center justify-between flex-wrap gap-2" style="border-color: var(--border);">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i class="fa-solid fa-clock text-sm"></i>
                        </div>
                        <h3 class="font-extrabold text-sm sm:text-base" style="color: var(--text-heading);">
                            Linha do Tempo ({{ appointments.length }} {{ appointments.length === 1 ? 'Agendamento' : 'Agendamentos' }})
                        </h3>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Confirmado</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Concluído</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Cancelado</span>
                    </div>
                </div>

                <div class="relative overflow-y-auto max-h-[680px] custom-scrollbar p-2 sm:p-4 divide-y divide-slate-100 dark:divide-slate-800/60">
                    <div class="relative min-w-full space-y-1">
                        <div
                            v-for="(hourData, idx) in timelineHours"
                            :key="idx"
                            class="agenda-time-row flex items-start py-2"
                        >
                            <div class="agenda-time-column font-bold text-xs opacity-70 flex items-center justify-end h-8">
                                {{ hourData.hourString }}
                            </div>

                            <div class="flex-1 pl-2 pr-1">
                                <template v-if="hourData.matchingAppointments.length > 0">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 w-full">
                                        <div
                                            v-for="apt in hourData.matchingAppointments"
                                            :key="apt.id"
                                            :class="['event-card min-h-[48px] py-2.5 px-3 flex items-center justify-between gap-2 text-left rounded-xl transition-all', statusClass(apt.status)]"
                                            @click="openAppointmentModal(apt)"
                                        >
                                            <div class="min-w-0 flex-1">
                                                <span class="font-extrabold text-xs sm:text-sm block truncate">{{ apt.client_name }}</span>
                                                <span class="text-[11px] opacity-80 block truncate">{{ apt.service?.name ?? 'Serviço' }} ({{ apt.service?.duration_minutes ?? 30 }} min)</span>
                                            </div>
                                            <div class="text-right shrink-0 ml-2">
                                                <span class="font-black text-xs block text-indigo-600 dark:text-indigo-300 whitespace-nowrap">{{ new Date(apt.appointment_date + 'T' + apt.appointment_time).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                                <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-black/10 dark:bg-white/10 block mt-0.5">{{ statusLabel(apt.status) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div v-if="appointments.length === 0" class="text-center py-16 px-4">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700 flex items-center justify-center mx-auto mb-3 text-2xl">
                            <i class="fa-regular fa-calendar-xmark"></i>
                        </div>
                        <h4 class="text-sm sm:text-base font-bold" style="color: var(--text-heading);">Nenhum horário marcado para hoje</h4>
                        <p class="text-xs opacity-70 mt-1 max-w-sm mx-auto">
                            A agenda está livre. Novos agendamentos públicos aparecerão automaticamente aqui.
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="activeView === 'week'" class="card p-0 overflow-hidden">
                <div class="p-4 border-b flex items-center justify-between flex-wrap gap-2" style="border-color: var(--border);">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <i class="fa-solid fa-calendar-week text-sm"></i>
                        </div>
                        <h3 class="font-extrabold text-sm sm:text-base" style="color: var(--text-heading);">
                            Grade Semanal 7 Colunas (DOM a SÁB • {{ startOfWeekFormatted }} a {{ endOfWeekFormatted }})
                        </h3>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                        Total de {{ weekAppointments.length }} agendamentos na semana
                    </span>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <div class="min-w-[840px]">
                        <div class="flex border-b" style="border-color: var(--border); background-color: var(--background-subtle);">
                            <div class="agenda-time-column py-4 border-r flex items-center justify-end pr-3" style="border-color: var(--border);">
                                <span class="text-[11px] font-extrabold uppercase opacity-60">Horário</span>
                            </div>
                            <div
                                v-for="(wh, idx) in weekHeaderDays"
                                :key="idx"
                                :class="[
                                    'flex-1 py-3 px-2 text-center border-r transition-all',
                                    wh.isDayActive ? 'bg-indigo-500/15 ring-2 ring-inset ring-indigo-500/40' : 'hover:bg-slate-100/60 dark:hover:bg-slate-900/60'
                                ]"
                                style="border-color: var(--border);"
                            >
                                <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">
                                    {{ wh.dayName }}
                                </span>

                                <div class="flex items-baseline justify-center gap-1 my-0.5">
                                    <span :class="['text-2xl sm:text-3xl font-black tracking-tight', wh.isDayActive ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-900 dark:text-slate-100']">
                                        {{ wh.dayNum }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 capitalize">
                                        {{ wh.monthShort }}
                                    </span>
                                </div>

                                <div class="mt-1 flex items-center justify-center">
                                    <span v-if="wh.count > 0" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-600 text-white shadow-sm shadow-indigo-600/30 whitespace-nowrap">
                                        {{ wh.count }} {{ wh.count === 1 ? 'agendamento' : 'agendamentos' }}
                                    </span>
                                    <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-semibold text-slate-400 dark:text-slate-600 bg-slate-200/50 dark:bg-slate-800/50 whitespace-nowrap">
                                        Livre
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="relative overflow-y-auto max-h-[640px] custom-scrollbar divide-y divide-slate-100 dark:divide-slate-800/60">
                            <div
                                v-for="(wh, idx) in weekMatrixHours"
                                :key="idx"
                                class="agenda-time-row flex items-stretch"
                            >
                                <div class="agenda-time-column font-bold text-xs opacity-60 flex items-center justify-end border-r" style="border-color: var(--border);">
                                    {{ wh.hourString }}
                                </div>

                                <div
                                    v-for="(col, colIdx) in wh.dayCols"
                                    :key="colIdx"
                                    class="flex-1 agenda-grid-col p-1.5 flex flex-col gap-1.5 overflow-hidden"
                                >
                                    <div
                                        v-for="apt in col.cellAppointments"
                                        :key="apt.id"
                                        :class="['event-card min-h-[46px] p-2 flex flex-col justify-between', statusClass(apt.status)]"
                                        @click="openAppointmentModal(apt)"
                                    >
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="font-extrabold text-xs block truncate">{{ apt.client_name }}</span>
                                            <span class="font-bold text-[10px] shrink-0 opacity-90">{{ new Date(apt.appointment_date + 'T' + apt.appointment_time).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                        </div>
                                        <span class="text-[10px] opacity-80 block truncate mt-0.5">{{ apt.service?.name ?? 'Serviço' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeView === 'table'" class="card p-0 overflow-hidden">
                <div class="p-4 border-b flex items-center justify-between flex-wrap gap-2" style="border-color: var(--border);">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i class="fa-solid fa-list-check text-sm"></i>
                        </div>
                        <h3 class="font-extrabold text-sm sm:text-base" style="color: var(--text-heading);">
                            Compromissos Detalhados ({{ new Date(selectedDate + 'T00:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }) }})
                        </h3>
                    </div>
                </div>

                <template v-if="appointments.length === 0">
                    <div class="text-center py-12 px-4 opacity-70">
                        <p class="text-sm font-semibold">Nenhum agendamento registrado nesta data.</p>
                    </div>
                </template>
                <template v-else>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th>Horário</th>
                                    <th>Cliente</th>
                                    <th>Contato</th>
                                    <th>Serviço</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="appointment in appointments" :key="appointment.id">
                                    <td>
                                        <span class="font-extrabold text-indigo-600 dark:text-indigo-400 text-sm whitespace-nowrap">
                                            <i class="fa-regular fa-clock text-xs mr-1"></i>
                                            {{ getAppointmentTableTime(appointment) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-bold block" style="color: var(--text-heading);">{{ appointment.client_name }}</span>
                                        <span v-if="appointment.notes" class="text-[11px] opacity-70 block max-w-xs truncate">{{ appointment.notes }}</span>
                                    </td>
                                    <td>
                                        <div class="text-xs opacity-80 flex items-center gap-1.5">
                                            <i class="fa-regular fa-envelope opacity-60"></i>
                                            <span class="truncate max-w-[160px]">{{ appointment.client_email }}</span>
                                        </div>
                                        <div class="text-xs opacity-90 flex items-center gap-1.5 mt-1">
                                            <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                                            <span>{{ appointment.client_phone }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-semibold block">{{ appointment.service?.name ?? 'N/A' }}</span>
                                        <span class="text-[11px] opacity-70">{{ getAppointmentServiceDuration(appointment) }}</span>
                                    </td>
                                    <td>
                                        <strong class="text-emerald-600 dark:text-emerald-400 font-bold whitespace-nowrap">
                                            R$ {{ getAppointmentServicePrice(appointment) }}
                                        </strong>
                                    </td>
                                    <td>
                                        <span v-if="appointment.status === 'confirmed'" class="badge badge-confirmed">Confirmado</span>
                                        <span v-else-if="appointment.status === 'completed'" class="badge bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30">Concluído</span>
                                        <span v-else class="badge badge-cancelled">Cancelado</span>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <button
                                            type="button"
                                            class="btn btn-outline py-1 px-2.5 text-xs rounded-xl"
                                            @click="openAppointmentModal(appointment)"
                                        >
                                            <i class="fa-solid fa-eye text-xs"></i>
                                            <span>Detalhes</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>

            <Teleport to="body">
                <div
                    v-if="showModal && selectedAppointment"
                    class="liquid-glass-backdrop fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click="handleBackdropClick"
                >
                    <div class="liquid-glass-card w-full max-w-lg p-6 sm:p-7 space-y-5 relative" @click.stop>

                        <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shadow-md shadow-indigo-500/20">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-extrabold" style="color: var(--text-heading);">{{ selectedAppointment.client_name }}</h3>
                                    <p class="text-xs opacity-60">{{ selectedAppointment.service_name }} ({{ selectedAppointment.duration }})</p>
                                </div>
                            </div>
                            <button type="button" @click="closeModal" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div class="grid grid-cols-2 gap-3 p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800">
                                <div>
                                    <span class="text-[11px] font-bold uppercase tracking-wider opacity-60 block">Data & Horário</span>
                                    <span class="font-extrabold text-indigo-600 dark:text-indigo-400 block text-sm">{{ selectedAppointment.date }} às {{ selectedAppointment.time }}</span>
                                </div>
                                <div>
                                    <span class="text-[11px] font-bold uppercase tracking-wider opacity-60 block">Investimento</span>
                                    <span class="font-extrabold text-emerald-600 dark:text-emerald-400 block text-sm">R$ {{ selectedAppointment.service_price }}</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800">
                                    <span class="opacity-80 flex items-center gap-2">
                                        <i class="fa-regular fa-envelope text-indigo-500"></i>
                                        <span>{{ selectedAppointment.client_email || 'Não informado' }}</span>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800">
                                    <span class="opacity-80 flex items-center gap-2">
                                        <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                                        <span>{{ selectedAppointment.client_phone || 'Não informado' }}</span>
                                    </span>
                                    <a
                                        v-if="selectedAppointment.client_phone"
                                        :href="whatsAppUrl"
                                        target="_blank"
                                        class="px-2.5 py-1 rounded-lg bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500 hover:text-white font-bold text-[11px] transition-colors flex items-center gap-1.5"
                                    >
                                        <i class="fa-brands fa-whatsapp"></i>
                                        <span>Abrir WhatsApp</span>
                                    </a>
                                </div>

                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 text-xs">
                                    <span class="font-bold opacity-70 block mb-0.5">Observações:</span>
                                    <p class="opacity-90 italic">{{ selectedAppointment.notes }}</p>
                                </div>
                            </div>

                            <div class="pt-2">
                                <span class="text-xs font-bold uppercase tracking-wider opacity-70 block mb-2">Alterar Status do Atendimento:</span>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        type="button"
                                        @click="submitStatusChange('confirmed')"
                                        class="btn btn-outline text-xs py-2 px-2 hover:border-emerald-500 hover:text-emerald-600 rounded-xl"
                                        :disabled="statusForm.processing"
                                    >
                                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                        <span>Confirmar</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="submitStatusChange('completed')"
                                        class="btn btn-outline text-xs py-2 px-2 hover:border-blue-500 hover:text-blue-600 rounded-xl"
                                        :disabled="statusForm.processing"
                                    >
                                        <i class="fa-solid fa-flag-checkered text-blue-500"></i>
                                        <span>Concluir</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="submitStatusChange('cancelled')"
                                        class="btn btn-outline text-xs py-2 px-2 hover:border-rose-500 hover:text-rose-600 rounded-xl"
                                        :disabled="statusForm.processing"
                                    >
                                        <i class="fa-solid fa-ban text-rose-500"></i>
                                        <span>Cancelar</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-3 border-t" style="border-color: var(--border);">
                            <button type="button" @click="closeModal" class="btn btn-outline text-xs py-2 px-4 rounded-xl">
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AdminLayout>
</template>

<style scoped>
.agenda-time-column {
    width: 68px;
    min-width: 68px;
    text-align: right;
    padding-right: 0.75rem;
    user-select: none;
}

.agenda-time-row {
    height: 64px;
    min-height: 64px;
    max-height: 64px;
    border-bottom: 1px solid var(--border);
    position: relative;
    transition: background-color 0.2s ease;
}

.agenda-time-row:hover {
    background-color: var(--surface-hover);
}

.agenda-time-row::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 68px;
    right: 0;
    height: 1px;
    border-top: 1px dashed rgba(148, 163, 184, 0.15);
    pointer-events: none;
}

.agenda-grid-col {
    border-right: 1px solid var(--border);
    position: relative;
    min-width: 140px;
    transition: background-color 0.2s ease;
}

.agenda-grid-col:last-child {
    border-right: none;
}

.agenda-grid-col:hover {
    background-color: rgba(99, 102, 241, 0.02);
}

:deep(html.dark) .agenda-grid-col:hover {
    background-color: rgba(99, 102, 241, 0.04);
}

.event-card {
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1), filter 0.3s ease, border-color 0.3s ease;
    user-select: none;
    overflow: hidden;
    backdrop-filter: blur(14px) saturate(180%);
    -webkit-backdrop-filter: blur(14px) saturate(180%);
    will-change: transform;
}

.event-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 25px -4px rgba(0, 0, 0, 0.22), 0 0 15px rgba(99, 102, 241, 0.2);
    filter: brightness(1.06);
    z-index: 10;
}

.event-confirmed {
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-left: 4px solid #10b981;
    color: #065f46;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.08);
}

:deep(html.dark) .event-confirmed {
    background: rgba(16, 185, 129, 0.18);
    border: 1px solid rgba(16, 185, 129, 0.35);
    border-left: 4px solid #34d399;
    color: #a7f3d0;
    box-shadow: 0 2px 10px rgba(16, 185, 129, 0.12);
}

.event-pending {
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-left: 4px solid #f59e0b;
    color: #92400e;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.08);
}

:deep(html.dark) .event-pending {
    background: rgba(245, 158, 11, 0.18);
    border: 1px solid rgba(245, 158, 11, 0.35);
    border-left: 4px solid #fbbf24;
    color: #fde68a;
    box-shadow: 0 2px 10px rgba(245, 158, 11, 0.12);
}

.event-completed {
    background: rgba(59, 130, 246, 0.12);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-left: 4px solid #3b82f6;
    color: #1e40af;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.08);
}

:deep(html.dark) .event-completed {
    background: rgba(59, 130, 246, 0.18);
    border: 1px solid rgba(59, 130, 246, 0.35);
    border-left: 4px solid #60a5fa;
    color: #bfdbfe;
    box-shadow: 0 2px 10px rgba(59, 130, 246, 0.12);
}

.event-cancelled {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.22);
    border-left: 4px solid #ef4444;
    color: #991b1b;
    opacity: 0.75;
}

:deep(html.dark) .event-cancelled {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-left: 4px solid #f87171;
    color: #fecaca;
    opacity: 0.75;
}

.weekday-tab-btn {
    transition: all 0.2s ease;
}

.weekday-tab-btn.active {
    background: var(--primary-gradient);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
    transform: scale(1.03);
}

@media (max-width: 640px) {
    .agenda-time-column {
        width: 52px;
        min-width: 52px;
        font-size: 0.72rem;
        padding-right: 0.4rem;
    }
    .agenda-time-row::after {
        left: 52px;
    }
}
</style>
