<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();

const props = defineProps({
    appointments: {
        type: Array,
        default: () => []
    },
    status: {
        type: String,
        default: ''
    },
    date: {
        type: String,
        default: ''
    },
    pagination: {
        type: Object,
        default: () => ({})
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

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const statusClass = (status) => {
    switch (status) {
        case 'confirmed':
            return 'badge-confirmed';
        case 'pending':
            return 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30';
        case 'completed':
            return 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30';
        case 'cancelled':
            return 'badge-cancelled';
        default:
            return 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300';
    }
};

const statusLabel = (status) => {
    switch (status) {
        case 'confirmed':
            return 'Confirmado';
        case 'pending':
            return 'Pendente';
        case 'completed':
            return 'Concluído';
        case 'cancelled':
            return 'Cancelado';
        default:
            return status;
    }
};

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
    const servicePrice = formatCurrency(appointment.service?.price ?? 0);
    const startTime = (appointment.start_time || '00:00').substring(0, 5);
    const endTime = (appointment.end_time || '00:00').substring(0, 5);
    const dateDisplay = formatDate(appointment.appointment_date);

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

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        closeDetailModal();
    }
};

const whatsAppUrl = computed(() => {
    if (!selectedAppointment.value?.customer_phone) return '#';
    const cleanPhone = selectedAppointment.value.customer_phone.replace(/\D/g, '');
    return 'https://wa.me/55' + cleanPhone;
});

const submitStatusChange = (newStatus) => {
    if (!selectedAppointment.value) return;
    statusForm.status = newStatus;
    statusForm.patch(route('admin.appointments.update-status', selectedAppointment.value.id), {
        onSuccess: () => {
            closeDetailModal();
        },
    });
};

const today = computed(() => new Date());

const startOfMonth = computed(() => {
    const d = new Date(today.value.getFullYear(), today.value.getMonth(), 1);
    return d;
});

const endOfMonth = computed(() => {
    const d = new Date(today.value.getFullYear(), today.value.getMonth() + 1, 0);
    return d;
});

const startDayOfWeek = computed(() => {
    return startOfMonth.value.getDay();
});

const daysInMonth = computed(() => {
    return endOfMonth.value.getDate();
});

const monthFormatted = computed(() => {
    return today.value.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
});

const groupedByDate = computed(() => {
    const grouped = {};
    props.appointments.forEach((app) => {
        const dateKey = new Date(app.appointment_date + 'T00:00:00').toISOString().split('T')[0];
        if (!grouped[dateKey]) {
            grouped[dateKey] = [];
        }
        grouped[dateKey].push(app);
    });
    return grouped;
});

const calendarDays = computed(() => {
    const days = [];
    const start = startOfMonth.value;
    for (let i = 0; i < startDayOfWeek.value; i++) {
        days.push({ isEmpty: true });
    }
    for (let day = 1; day <= daysInMonth.value; day++) {
        const d = new Date(start.getFullYear(), start.getMonth(), day);
        const dateStr = d.toISOString().split('T')[0];
        const todayStr = today.value.toISOString().split('T')[0];
        const appsForDay = groupedByDate.value[dateStr] || [];
        days.push({
            isEmpty: false,
            day: day,
            dateStr: dateStr,
            isToday: dateStr === todayStr,
            appointments: appsForDay,
        });
    }
    return days;
});

const calendarStatusClass = (status) => {
    switch (status) {
        case 'confirmed':
            return 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30';
        case 'cancelled':
            return 'bg-rose-500/20 text-rose-600 dark:text-rose-300 border border-rose-500/30';
        default:
            return 'bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 border border-indigo-500/30';
    }
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
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Consulte, filtre e gerencie os agendamentos dos clientes em tempo real.</p>
                </div>
            </div>

            <div class="card p-5 space-y-4">
                <div class="flex items-center justify-between gap-4 pb-3 border-b" style="border-color: var(--border);">
                    <h3 class="text-sm font-bold flex items-center gap-2" style="color: var(--text-heading);">
                        <i class="fa-solid fa-sliders text-indigo-500"></i>
                        <span>Filtros e Modo de Visualização</span>
                    </h3>

                    <div class="inline-flex rounded-xl p-1 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800" role="tablist">
                        <button
                            type="button"
                            @click="switchView('table')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                activeView === 'table'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'opacity-70 hover:opacity-100'
                            ]"
                        >
                            <i class="fa-solid fa-list-ul mr-1"></i> Lista / Tabela
                        </button>
                        <button
                            type="button"
                            @click="switchView('calendar')"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                activeView === 'calendar'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'opacity-70 hover:opacity-100'
                            ]"
                        >
                            <i class="fa-solid fa-calendar-days mr-1"></i> Calendário Agenda
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submitFilter" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="filter_status" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Status</label>
                        <select
                            id="filter_status"
                            v-model="filterForm.status"
                            class="form-control text-xs sm:text-sm py-2 px-3 rounded-xl"
                        >
                            <option value="">Todos os Status</option>
                            <option value="pending">Pendente</option>
                            <option value="confirmed">Confirmado</option>
                            <option value="completed">Concluído</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>

                    <div>
                        <label for="filter_date" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Data</label>
                        <input
                            type="date"
                            id="filter_date"
                            v-model="filterForm.date"
                            class="form-control text-xs sm:text-sm py-2 px-3 rounded-xl"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="flex-1 btn btn-primary text-xs py-2 px-3 rounded-xl">
                            <i class="fa-solid fa-filter text-xs"></i>
                            <span>Filtrar</span>
                        </button>
                        <button
                            v-if="filterForm.status || filterForm.date"
                            type="button"
                            @click="clearFilters"
                            class="btn btn-outline text-xs py-2 px-3 rounded-xl"
                        >
                            <span>Limpar</span>
                        </button>
                    </div>
                </form>
            </div>

            <div v-if="activeView === 'table'" class="card overflow-hidden p-0">
                <template v-if="appointments.length === 0">
                    <div class="p-12 text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 flex items-center justify-center text-2xl mx-auto">
                            <i class="fa-solid fa-calendar-xmark"></i>
                        </div>
                        <div class="max-w-xs mx-auto">
                            <h3 class="text-base font-bold" style="color: var(--text-heading);">Nenhum agendamento encontrado</h3>
                            <p class="text-xs opacity-70 mt-1">Não foram encontrados registros para os filtros selecionados.</p>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4">Cliente</th>
                                    <th class="px-6 py-4">Serviço</th>
                                    <th class="px-6 py-4">Data & Horário</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Ações & Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="appointment in appointments" :key="appointment.id">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 flex items-center justify-center font-bold shrink-0 text-sm">
                                                {{ appointment.customer_name ? appointment.customer_name.charAt(0).toUpperCase() : 'A' }}
                                            </div>
                                            <div class="min-w-0">
                                                <span class="font-bold block truncate" style="color: var(--text-heading);">{{ appointment.customer_name }}</span>
                                                <span class="text-xs opacity-70 block truncate">{{ appointment.customer_email }} • {{ appointment.customer_phone }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="font-semibold block" style="color: var(--text-heading);">{{ appointment.service?.name ?? 'N/A' }}</span>
                                        <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">R$ {{ formatCurrency(appointment.service?.price ?? 0) }}</span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="font-semibold block" style="color: var(--text-heading);">
                                            <i class="fa-regular fa-calendar text-indigo-500 dark:text-indigo-400 mr-1"></i>
                                            {{ formatDate(appointment.appointment_date) }}
                                        </span>
                                        <span class="text-xs opacity-70">
                                            <i class="fa-regular fa-clock opacity-60 mr-1"></i>
                                            {{ (appointment.start_time || '').substring(0, 5) }} - {{ (appointment.end_time || '').substring(0, 5) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span :class="['badge', statusClass(appointment.status)]">
                                            {{ statusLabel(appointment.status) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <button
                                            type="button"
                                            class="btn btn-outline py-1.5 px-3 text-xs rounded-xl"
                                            @click="openDetailModal(appointment)"
                                        >
                                            <i class="fa-solid fa-eye text-xs"></i>
                                            <span>Detalhes / Gerenciar</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="pagination.links" class="p-4 border-t" style="border-color: var(--border);">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="text-xs opacity-70">Mostrando {{ pagination.from || 0 }} a {{ pagination.to || 0 }} de {{ pagination.total || 0 }} resultados</span>
                            <div class="inline-flex items-center gap-1">
                                <Link
                                    v-for="(link, idx) in pagination.links"
                                    :key="idx"
                                    :href="link.url || '#'"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                        link.active
                                            ? 'bg-indigo-600 text-white shadow-sm'
                                            : link.url
                                                ? 'btn-outline'
                                                : 'opacity-30 cursor-not-allowed pointer-events-none',
                                        !link.url ? 'btn btn-outline' : ''
                                    ]"
                                ></Link>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div v-if="activeView === 'calendar'" class="card p-5 space-y-4">
                <div class="flex items-center justify-between gap-4 pb-3 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-indigo-500 text-lg"></i>
                        <h3 class="text-base font-extrabold" style="color: var(--text-heading);">Grade Mensal da Agenda</h3>
                    </div>
                    <span class="text-xs opacity-70">Clique em qualquer compromisso ou dia para abrir os detalhes</span>
                </div>

                <div class="space-y-3">
                    <div class="text-center font-bold text-base text-indigo-600 dark:text-indigo-400 capitalize">
                        {{ monthFormatted }}
                    </div>

                    <div class="grid grid-cols-7 gap-1 text-center font-extrabold text-xs opacity-60 uppercase py-2">
                        <div>Dom</div><div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div>
                    </div>

                    <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
                        <template v-for="(cell, idx) in calendarDays" :key="idx">
                            <div
                                v-if="cell.isEmpty"
                                class="min-h-[70px] sm:min-h-[90px] p-1.5 rounded-xl bg-slate-100/50 dark:bg-slate-900/30 border border-transparent opacity-30"
                            ></div>
                            <div
                                v-else
                                :class="[
                                    'min-h-[80px] sm:min-h-[100px] p-2 rounded-xl border flex flex-col justify-between transition-all',
                                    cell.isToday
                                        ? 'bg-indigo-500/10 border-indigo-500/40 ring-1 ring-indigo-500/30'
                                        : 'bg-slate-50 dark:bg-slate-900/70 border-slate-200 dark:border-slate-800'
                                ]"
                            >
                                <div class="flex items-center justify-between text-xs">
                                    <span :class="['font-extrabold', cell.isToday ? 'text-indigo-600 dark:text-indigo-400' : 'opacity-80']">{{ cell.day }}</span>
                                    <span
                                        v-if="cell.appointments.length > 0"
                                        class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-600 text-white shrink-0"
                                    >
                                        {{ cell.appointments.length }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 gap-1 mt-1 overflow-hidden">
                                    <div
                                        v-for="appItem in cell.appointments.slice(0, 2)"
                                        :key="appItem.id"
                                        @click="openDetailModal(appItem)"
                                        :class="[
                                            'min-h-[28px] py-1 px-1.5 rounded-lg text-[10px] font-bold flex items-center justify-between gap-1 cursor-pointer transition-all hover:scale-[1.02]',
                                            calendarStatusClass(appItem.status)
                                        ]"
                                        :title="`${appItem.customer_name} - ${(appItem.start_time || '').substring(0, 5)}`"
                                    >
                                        <span class="truncate">{{ appItem.customer_name }}</span>
                                        <span class="shrink-0 font-extrabold opacity-80">{{ (appItem.start_time || '').substring(0, 5) }}</span>
                                    </div>
                                    <span v-if="cell.appointments.length > 2" class="text-[9px] opacity-60 block font-bold text-center">+{{ cell.appointments.length - 2 }} mais</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <Teleport to="body">
                <div
                    v-if="showDetailModal && selectedAppointment"
                    class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4"
                    @click="handleBackdropClick"
                >
                    <div class="liquid-glass-card w-full max-w-lg p-6 sm:p-7 space-y-5 relative" @click.stop>
                        <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shadow-md shadow-indigo-500/20">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-extrabold" style="color: var(--text-heading);">{{ selectedAppointment.customer_name }}</h3>
                                    <p class="text-xs opacity-60">{{ selectedAppointment.service_name }} ({{ selectedAppointment.duration }})</p>
                                </div>
                            </div>
                            <button type="button" @click="closeDetailModal" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
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
                                    <span class="text-[11px] font-bold uppercase tracking-wider opacity-60 block">Investimento Total</span>
                                    <span class="font-extrabold text-emerald-600 dark:text-emerald-400 block text-sm">R$ {{ selectedAppointment.service_price }}</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800">
                                    <span class="opacity-80 flex items-center gap-2">
                                        <i class="fa-regular fa-envelope text-indigo-500"></i>
                                        <span>{{ selectedAppointment.customer_email || 'Não informado' }}</span>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800">
                                    <span class="opacity-80 flex items-center gap-2">
                                        <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                                        <span>{{ selectedAppointment.customer_phone || 'Não informado' }}</span>
                                    </span>
                                    <a
                                        v-if="selectedAppointment.customer_phone"
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
                                <span class="text-xs font-bold uppercase tracking-wider opacity-70 block mb-2">Atualizar Status do Atendimento:</span>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <button
                                        v-if="hasPermission('appointments.edit')"
                                        type="button"
                                        @click="submitStatusChange('confirmed')"
                                        class="btn btn-outline text-xs py-2 px-2 hover:border-emerald-500 hover:text-emerald-600 rounded-xl"
                                        :disabled="statusForm.processing"
                                    >
                                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                        <span>Confirmar</span>
                                    </button>

                                    <button
                                        v-if="hasPermission('appointments.edit')"
                                        type="button"
                                        @click="submitStatusChange('completed')"
                                        class="btn btn-outline text-xs py-2 px-2 hover:border-blue-500 hover:text-blue-600 rounded-xl"
                                        :disabled="statusForm.processing"
                                    >
                                        <i class="fa-solid fa-flag-checkered text-blue-500"></i>
                                        <span>Concluir</span>
                                    </button>

                                    <button
                                        v-if="hasPermission('appointments.edit')"
                                        type="button"
                                        @click="submitStatusChange('pending')"
                                        class="btn btn-outline text-xs py-2 px-2 hover:border-amber-500 hover:text-amber-600 rounded-xl"
                                        :disabled="statusForm.processing"
                                    >
                                        <i class="fa-solid fa-hourglass-half text-amber-500"></i>
                                        <span>Pendente</span>
                                    </button>

                                    <button
                                        v-if="hasPermission('appointments.cancel')"
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
                            <button type="button" @click="closeDetailModal" class="btn btn-outline text-xs py-2 px-4 rounded-xl">
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AdminLayout>
</template>
