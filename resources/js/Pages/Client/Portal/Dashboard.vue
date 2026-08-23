<script setup>
import ClientPortalLayout from '@/Layouts/ClientPortalLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';

const props = defineProps({
    client: {
        type: Object,
        default: () => ({ name: 'Cliente', email: '' }),
    },
    summary: {
        type: Object,
        default: () => ({ appointments: 0, completed: 0, companies: 0, reviews: 0 }),
    },
    badges: {
        type: Array,
        default: () => [],
    },
    companies: {
        type: Array,
        default: () => [],
    },
    appointments: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('appointments');
const appointmentFilter = ref('all'); // 'all', 'upcoming', 'completed', 'cancelled'
const reviewModalOpen = ref(false);
const activeReviewAppointment = ref(null);

const companyReviewModalOpen = ref(false);
const activeReviewCompany = ref(null);
const companyReviewForm = useForm({
    rating: 5,
    comment: '',
    hoverRating: 0,
});

const companyQuickCompliments = [
    'Ambiente incrível e acolhedor ✨',
    'Excelente atendimento desde a recepção 👏',
    'Profissionais altamente qualificados ⭐',
    'Pontualidade e organização nota 10 ⏱️',
    'Super recomendo a todos! 🔥',
    'Estrutura e serviços impecáveis 🏆',
];

const appendCompanyCompliment = (text) => {
    if (!companyReviewForm.comment) {
        companyReviewForm.comment = text;
    } else if (!companyReviewForm.comment.includes(text)) {
        companyReviewForm.comment = `${companyReviewForm.comment} ${text}`;
    }
};

const openCompanyReviewModal = (company) => {
    activeReviewCompany.value = company;
    companyReviewForm.rating = company.company_review?.rating || 5;
    companyReviewForm.comment = company.company_review?.comment || '';
    companyReviewForm.hoverRating = 0;
    companyReviewModalOpen.value = true;
};

const closeCompanyReviewModal = () => {
    companyReviewModalOpen.value = false;
    activeReviewCompany.value = null;
};

const saveCompanyReview = () => {
    if (!activeReviewCompany.value?.id) return;
    companyReviewForm.post(route('client.companies.review', activeReviewCompany.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeCompanyReviewModal();
        },
    });
};

const reviewForms = reactive(
    Object.fromEntries(
        props.appointments.map((item) => [
            item.id,
            {
                rating: item.review?.rating || 5,
                comment: item.review?.comment || '',
                saving: false,
                isEditing: false,
                hoverRating: 0,
            },
        ])
    )
);

const quickCompliments = [
    'Atendimento excelente! ⭐',
    'Profissional super pontual e atencioso 👏',
    'Ambiente agradável e limpo ✨',
    'Serviço impecável, recomendo! 💈',
    'Superou as expectativas 🏆',
    'Com certeza voltarei mais vezes 🔥',
];

const appendCompliment = (appointmentId, text) => {
    const form = reviewForms[appointmentId];
    if (!form) return;
    if (!form.comment) {
        form.comment = text;
    } else if (!form.comment.includes(text)) {
        form.comment = `${form.comment} ${text}`;
    }
};

const getRatingLabel = (rating) => {
    switch (rating) {
        case 5: return 'Excelente! Incrível';
        case 4: return 'Muito Bom';
        case 3: return 'Bom / Regular';
        case 2: return 'Insatisfeito';
        case 1: return 'Muito Insatisfeito';
        default: return 'Selecione uma nota';
    }
};

const saveReview = (appointment) => {
    const form = reviewForms[appointment.id];
    if (!form) return;
    form.saving = true;

    router.put(
        route('client.reviews.store', appointment.id),
        {
            rating: form.rating,
            comment: form.comment,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                form.saving = false;
                form.isEditing = false;
                if (reviewModalOpen.value && activeReviewAppointment.value?.id === appointment.id) {
                    reviewModalOpen.value = false;
                    activeReviewAppointment.value = null;
                }
            },
            onError: () => {
                form.saving = false;
            },
            onFinish: () => {
                form.saving = false;
            },
        }
    );
};

const openReviewModal = (appointment) => {
    activeReviewAppointment.value = appointment;
    reviewModalOpen.value = true;
};

const closeReviewModal = () => {
    reviewModalOpen.value = false;
    activeReviewAppointment.value = null;
};

const filteredAppointments = computed(() => {
    if (appointmentFilter.value === 'all') return props.appointments;
    if (appointmentFilter.value === 'upcoming') {
        return props.appointments.filter(a => a.status === 'confirmed' || a.status === 'pending');
    }
    if (appointmentFilter.value === 'completed') {
        return props.appointments.filter(a => a.status === 'completed');
    }
    if (appointmentFilter.value === 'cancelled') {
        return props.appointments.filter(a => a.status === 'cancelled');
    }
    return props.appointments;
});

const statusBadge = (status) => {
    switch (status) {
        case 'confirmed':
            return {
                label: 'Confirmado',
                icon: 'fa-solid fa-circle-check',
                classes: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25',
            };
        case 'completed':
            return {
                label: 'Concluído',
                icon: 'fa-solid fa-check-double',
                classes: 'bg-blue-500/15 text-blue-700 dark:text-blue-300 border border-blue-500/25',
            };
        case 'cancelled':
            return {
                label: 'Cancelado',
                icon: 'fa-solid fa-circle-xmark',
                classes: 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/25',
            };
        default:
            return {
                label: 'Pendente',
                icon: 'fa-solid fa-clock',
                classes: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/25',
            };
    }
};

const firstName = computed(() => {
    return props.client?.name ? props.client.name.split(' ')[0] : 'Cliente';
});
</script>

<template>
    <ClientPortalLayout title="Minha Área">
        <div class="space-y-8">
            <!-- Hero Profile Banner -->
            <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white/90 dark:bg-slate-900/90 p-6 sm:p-8 shadow-xl shadow-indigo-500/5 backdrop-blur-xl transition-all">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="flex items-center gap-4 sm:gap-5 min-w-0">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-500 text-white flex items-center justify-center font-black text-2xl sm:text-3xl shadow-xl shadow-indigo-500/30 shrink-0 ring-4 ring-white/50 dark:ring-slate-800/50">
                            {{ firstName.charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                                    Olá, {{ firstName }}!
                                </h1>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 border border-indigo-500/20">
                                    Cliente VIP
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-xl">
                                Gerencie seus agendamentos, acesse as páginas das suas empresas favoritas e compartilhe suas avaliações.
                            </p>
                        </div>
                    </div>

                    <!-- Quick stats pills -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3.5 sm:p-4 rounded-2xl border border-slate-200/70 dark:border-slate-800/70 bg-slate-50/70 dark:bg-slate-950/50 text-center">
                            <span class="block text-2xl sm:text-3xl font-black text-indigo-600 dark:text-cyan-400">{{ summary.appointments }}</span>
                            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-500">Agendamentos</span>
                        </div>
                        <div class="p-3.5 sm:p-4 rounded-2xl border border-slate-200/70 dark:border-slate-800/70 bg-slate-50/70 dark:bg-slate-950/50 text-center">
                            <span class="block text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ summary.completed }}</span>
                            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-500">Concluídos</span>
                        </div>
                        <div class="p-3.5 sm:p-4 rounded-2xl border border-slate-200/70 dark:border-slate-800/70 bg-slate-50/70 dark:bg-slate-950/50 text-center">
                            <span class="block text-2xl sm:text-3xl font-black text-purple-600 dark:text-purple-400">{{ summary.companies }}</span>
                            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-500">Empresas</span>
                        </div>
                        <div class="p-3.5 sm:p-4 rounded-2xl border border-slate-200/70 dark:border-slate-800/70 bg-slate-50/70 dark:bg-slate-950/50 text-center">
                            <span class="block text-2xl sm:text-3xl font-black text-amber-500">{{ summary.reviews }}</span>
                            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-500">Avaliações</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Navigation Tabs Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                <div class="flex flex-wrap gap-2 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                    <button
                        type="button"
                        @click="activeTab = 'appointments'"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                            activeTab === 'appointments'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                        ]"
                    >
                        <i class="fa-solid fa-calendar-check text-xs"></i>
                        <span>Meus Agendamentos</span>
                        <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-black" :class="activeTab === 'appointments' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                            {{ appointments.length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'companies'"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                            activeTab === 'companies'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                        ]"
                    >
                        <i class="fa-solid fa-building-store text-xs"></i>
                        <span>Empresas & Avaliações</span>
                        <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-black" :class="activeTab === 'companies' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                            {{ companies.length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'badges'"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                            activeTab === 'badges'
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                        ]"
                    >
                        <i class="fa-solid fa-award text-xs"></i>
                        <span>Medalhas & Fidelidade</span>
                    </button>
                </div>

                <!-- Sub-filters when on Appointments Tab -->
                <div v-if="activeTab === 'appointments' && appointments.length > 0" class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-900 p-1 rounded-xl">
                    <button
                        type="button"
                        @click="appointmentFilter = 'all'"
                        :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', appointmentFilter === 'all' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-cyan-400 shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white']"
                    >
                        Todos
                    </button>
                    <button
                        type="button"
                        @click="appointmentFilter = 'upcoming'"
                        :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', appointmentFilter === 'upcoming' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-cyan-400 shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white']"
                    >
                        Próximos
                    </button>
                    <button
                        type="button"
                        @click="appointmentFilter = 'completed'"
                        :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', appointmentFilter === 'completed' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-cyan-400 shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white']"
                    >
                        Concluídos
                    </button>
                </div>
            </div>

            <!-- TAB 1: MEUS AGENDAMENTOS -->
            <div v-if="activeTab === 'appointments'" class="space-y-4">
                <div v-if="filteredAppointments.length === 0" class="rounded-3xl border border-dashed border-slate-300 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 p-12 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 flex items-center justify-center mx-auto text-2xl">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Nenhum agendamento encontrado</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                            Você não possui agendamentos com este filtro no momento. Visite a página de uma empresa para agendar!
                        </p>
                    </div>
                    <button
                        v-if="companies.length > 0"
                        type="button"
                        @click="activeTab = 'companies'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500 transition-all cursor-pointer shadow-md"
                    >
                        <i class="fa-solid fa-building-store"></i>
                        <span>Ver Empresas Visitadas</span>
                    </button>
                </div>

                <div v-else class="space-y-5">
                    <article
                        v-for="appointment in filteredAppointments"
                        :key="appointment.id"
                        class="rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-all overflow-hidden"
                    >
                        <!-- Main appointment header/body -->
                        <div class="p-5 sm:p-6 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2.5">
                                        <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">
                                            {{ appointment.service }}
                                        </h3>
                                        <span :class="['inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold', statusBadge(appointment.status).classes]">
                                            <i :class="statusBadge(appointment.status).icon" class="text-[10px]"></i>
                                            <span>{{ statusBadge(appointment.status).label }}</span>
                                        </span>
                                    </div>

                                    <!-- Company & Professional info -->
                                    <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                                        <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200">
                                            <i class="fa-solid fa-store text-indigo-500 text-xs"></i>
                                            <span>{{ appointment.company }}</span>
                                        </div>
                                        <div v-if="appointment.professional" class="flex items-center gap-1.5 font-medium text-slate-500 dark:text-slate-400">
                                            <i class="fa-solid fa-user text-xs"></i>
                                            <span>Atendido por: <strong class="text-slate-700 dark:text-slate-300">{{ appointment.professional }}</strong></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date & Time Badge + Price -->
                                <div class="flex sm:flex-col items-center sm:items-end justify-between gap-2 border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100 dark:border-slate-800">
                                    <div class="text-left sm:text-right">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-black">
                                            <i class="fa-regular fa-calendar text-indigo-500"></i>
                                            <span>{{ appointment.date }}</span>
                                            <span class="opacity-40">|</span>
                                            <i class="fa-regular fa-clock text-indigo-500"></i>
                                            <span>{{ appointment.time }}</span>
                                        </div>
                                        <span class="block text-[11px] font-bold text-slate-400 mt-1">Duração: {{ appointment.duration_minutes }} min</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-base sm:text-lg font-black text-indigo-600 dark:text-cyan-400">{{ appointment.service_price }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions bar (Visit company page / Re-book) -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-3">
                                    <a
                                        v-if="appointment.company_booking_url"
                                        :href="appointment.company_booking_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 font-bold text-indigo-600 dark:text-cyan-400 hover:underline"
                                    >
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[11px]"></i>
                                        <span>Visitar Página da Empresa</span>
                                    </a>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a
                                        v-if="appointment.company_booking_url"
                                        :href="appointment.company_booking_url"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 text-slate-700 dark:text-slate-200 hover:text-indigo-600 font-bold transition-all"
                                    >
                                        <i class="fa-solid fa-calendar-plus text-xs"></i>
                                        <span>Novo Agendamento</span>
                                    </a>

                                    <button
                                        v-if="appointment.can_review && (!appointment.review || reviewForms[appointment.id].isEditing)"
                                        type="button"
                                        @click="reviewForms[appointment.id].isEditing = !reviewForms[appointment.id].isEditing"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-400 hover:bg-amber-500/20 font-bold transition-all cursor-pointer"
                                    >
                                        <i class="fa-solid fa-star text-xs"></i>
                                        <span>{{ appointment.review ? 'Fechar Edição' : 'Avaliar Atendimento' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Interactive Review Box (For completed appointments) -->
                        <div v-if="appointment.can_review" class="border-t border-slate-200/70 dark:border-slate-800/70 bg-slate-50/70 dark:bg-slate-950/40 p-5 sm:p-6">
                            <!-- Already reviewed display state -->
                            <div v-if="appointment.review && !reviewForms[appointment.id].isEditing" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex gap-1 text-amber-400 text-base">
                                            <i v-for="s in 5" :key="s" class="fa-star" :class="s <= appointment.review.rating ? 'fa-solid text-amber-400' : 'fa-regular text-slate-300 dark:text-slate-700'"></i>
                                        </div>
                                        <span class="text-xs font-black text-slate-900 dark:text-white">{{ getRatingLabel(appointment.review.rating) }}</span>
                                        <span v-if="appointment.review.updated_at" class="text-[10px] text-slate-400 font-semibold">• Avaliado em {{ appointment.review.updated_at }}</span>
                                    </div>
                                    <p v-if="appointment.review.comment" class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 italic">
                                        "{{ appointment.review.comment }}"
                                    </p>
                                    <p v-else class="text-xs text-slate-400 italic">Você avaliou com {{ appointment.review.rating }} estrelas sem comentário adicional.</p>
                                </div>

                                <button
                                    type="button"
                                    @click="reviewForms[appointment.id].isEditing = true"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 transition-all self-start sm:self-auto cursor-pointer"
                                >
                                    <i class="fa-solid fa-pen-to-square text-xs text-indigo-500"></i>
                                    <span>Editar Avaliação</span>
                                </button>
                            </div>

                            <!-- Review Form (Creating or Editing) -->
                            <form v-else @submit.prevent="saveReview(appointment)" class="space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div>
                                        <h4 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                                            <i class="fa-solid fa-star text-amber-400"></i>
                                            <span>{{ appointment.review ? 'Editar sua avaliação' : 'Avaliação do Atendimento & Serviço' }}</span>
                                        </h4>
                                        <p class="text-xs text-slate-500">Seu feedback é enviado diretamente para a administração do estabelecimento e para o profissional responsável.</p>
                                    </div>

                                    <!-- Interactive Star Rating Picker -->
                                    <div class="flex items-center gap-1 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs self-start sm:self-auto">
                                        <button
                                            v-for="star in 5"
                                            :key="star"
                                            type="button"
                                            @click="reviewForms[appointment.id].rating = star"
                                            @mouseenter="reviewForms[appointment.id].hoverRating = star"
                                            @mouseleave="reviewForms[appointment.id].hoverRating = 0"
                                            class="p-1 text-2xl transition-transform hover:scale-125 focus:outline-none cursor-pointer"
                                            :title="getRatingLabel(star)"
                                        >
                                            <i
                                                class="fa-star"
                                                :class="star <= (reviewForms[appointment.id].hoverRating || reviewForms[appointment.id].rating) ? 'fa-solid text-amber-400 drop-shadow-xs' : 'fa-regular text-slate-300 dark:text-slate-700'"
                                            ></i>
                                        </button>
                                        <span class="ml-2 text-xs font-black text-slate-700 dark:text-slate-200 min-w-[110px]">
                                            {{ getRatingLabel(reviewForms[appointment.id].hoverRating || reviewForms[appointment.id].rating) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Quick Compliment Chips -->
                                <div class="space-y-1.5">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Elogios Rápidos (clique para adicionar):</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button
                                            v-for="chip in quickCompliments"
                                            :key="chip"
                                            type="button"
                                            @click="appendCompliment(appointment.id, chip)"
                                            class="px-2.5 py-1 rounded-xl text-[11px] font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-cyan-400 transition-all cursor-pointer shadow-2xs"
                                        >
                                            {{ chip }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Comment Textarea & Submit -->
                                <div class="space-y-2">
                                    <textarea
                                        v-model="reviewForms[appointment.id].comment"
                                        rows="2"
                                        maxlength="2000"
                                        placeholder="Escreva detalhes sobre o que você mais gostou ou pontos a melhorar... (opcional)"
                                        class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all shadow-xs resize-y min-h-[70px]"
                                    ></textarea>

                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-[11px] text-slate-400">
                                            {{ reviewForms[appointment.id].comment ? reviewForms[appointment.id].comment.length : 0 }} / 2000 caracteres
                                        </span>

                                        <div class="flex items-center gap-2">
                                            <button
                                                v-if="appointment.review"
                                                type="button"
                                                @click="reviewForms[appointment.id].isEditing = false"
                                                class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 cursor-pointer"
                                            >
                                                Cancelar
                                            </button>
                                            <button
                                                type="submit"
                                                :disabled="reviewForms[appointment.id].saving"
                                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black shadow-md shadow-indigo-600/30 transition-all cursor-pointer disabled:opacity-50"
                                            >
                                                <i v-if="reviewForms[appointment.id].saving" class="fa-solid fa-spinner fa-spin"></i>
                                                <i v-else class="fa-solid fa-paper-plane text-xs"></i>
                                                <span>{{ reviewForms[appointment.id].saving ? 'Enviando...' : (appointment.review ? 'Atualizar Avaliação' : 'Enviar Avaliação') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </article>
                </div>
            </div>

            <!-- TAB 2: EMPRESAS & BARBEARIAS VISITADAS -->
            <div v-else-if="activeTab === 'companies'" class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">Empresas & Estabelecimentos</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Acesse a página pública de agendamento e avalie suas experiências.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 border border-indigo-500/20 self-start sm:self-auto">
                        {{ companies.length }} empresa(s) visitada(s)
                    </span>
                </div>

                <div v-if="companies.length === 0" class="rounded-3xl border border-dashed border-slate-300 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 p-12 text-center space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 flex items-center justify-center mx-auto text-xl">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Nenhuma empresa no histórico</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Assim que você realizar seu primeiro agendamento, o estabelecimento aparecerá aqui.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <article
                        v-for="company in companies"
                        :key="company.id"
                        class="rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group"
                    >
                        <div class="p-5 sm:p-6 space-y-4">
                            <!-- Company Header -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl overflow-hidden bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-500 text-white flex items-center justify-center font-black text-base shadow-md shrink-0">
                                        <img v-if="company.logo_url" :src="company.logo_url" :alt="company.name" class="w-full h-full object-cover" />
                                        <i v-else class="fa-solid fa-store"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-black text-base text-slate-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-cyan-400 transition-colors">
                                            {{ company.name }}
                                        </h3>
                                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 block">
                                            {{ company.services_count }} atendimento(s) realizado(s)
                                        </span>
                                    </div>
                                </div>

                                <span v-if="company.badge" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/25 shrink-0">
                                    {{ company.badge.name }}
                                </span>
                            </div>

                            <!-- Professionals list -->
                            <div v-if="company.professionals && company.professionals.length" class="space-y-1 text-xs">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Profissionais que te atenderam:</span>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="prof in company.professionals"
                                        :key="prof"
                                        class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-[11px]"
                                    >
                                        {{ prof }}
                                    </span>
                                </div>
                            </div>

                            <!-- Existing Company Review Display -->
                            <div v-if="company.company_review" class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-between gap-2">
                                <div class="space-y-0.5">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400 block">Sua Avaliação Pública</span>
                                    <div class="flex items-center gap-1 text-amber-400 text-xs">
                                        <i v-for="s in 5" :key="s" class="fa-star" :class="s <= company.company_review.rating ? 'fa-solid' : 'fa-regular'"></i>
                                        <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 ml-1">{{ company.company_review.rating }}.0</span>
                                    </div>
                                    <p v-if="company.company_review.comment" class="text-[11px] text-slate-600 dark:text-slate-300 italic truncate max-w-[200px]">
                                        "{{ company.company_review.comment }}"
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="openCompanyReviewModal(company)"
                                    class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition-all cursor-pointer shadow-2xs"
                                >
                                    Editar
                                </button>
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="p-5 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/40 space-y-2">
                            <!-- Direct link to visit the public company page -->
                            <a
                                v-if="company.booking_url"
                                :href="company.booking_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full py-2.5 px-4 rounded-xl text-xs font-black bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-white border border-slate-200 dark:border-slate-700 transition-all flex items-center justify-center gap-2 shadow-xs"
                            >
                                <i class="fa-solid fa-globe text-cyan-500"></i>
                                <span>Visitar Página da Empresa</span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px] opacity-60"></i>
                            </a>

                            <div class="grid grid-cols-2 gap-2">
                                <a
                                    v-if="company.booking_url"
                                    :href="company.booking_url"
                                    class="py-2 px-3 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-500 text-white transition-all flex items-center justify-center gap-1.5 shadow-xs"
                                >
                                    <i class="fa-solid fa-calendar-plus text-xs"></i>
                                    <span>Agendar</span>
                                </a>

                                <button
                                    type="button"
                                    @click="openCompanyReviewModal(company)"
                                    class="py-2 px-3 rounded-xl text-xs font-black bg-amber-500/15 hover:bg-amber-500/25 text-amber-700 dark:text-amber-300 border border-amber-500/25 transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                >
                                    <i class="fa-solid fa-star text-xs"></i>
                                    <span>{{ company.company_review ? 'Editar Avaliação' : 'Avaliar Empresa' }}</span>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- TAB 3: MEDALHAS & FIDELIDADE -->
            <div v-else-if="activeTab === 'badges'" class="space-y-6">
                <section class="rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-cyan-600 p-6 sm:p-8 text-white shadow-xl shadow-indigo-600/20 relative overflow-hidden">
                    <div class="relative z-10 space-y-2">
                        <span class="text-xs font-black uppercase tracking-[0.2em] text-white/80">Programa de Conquistas</span>
                        <h2 class="text-2xl sm:text-3xl font-black">Suas Medalhas de Fidelidade</h2>
                        <p class="text-xs sm:text-sm text-white/80 max-w-xl">
                            A cada serviço concluído, você desbloqueia novas insígnias de cliente frequente e acumula reconhecimento especial nos estabelecimentos.
                        </p>
                    </div>
                </section>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div
                        v-for="badge in badges"
                        :key="badge.name"
                        :class="[
                            'rounded-3xl border p-5 text-center transition-all relative overflow-hidden flex flex-col justify-between',
                            badge.earned
                                ? 'border-amber-400/40 bg-white dark:bg-slate-900 shadow-md ring-2 ring-amber-400/20'
                                : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 opacity-50 grayscale'
                        ]"
                    >
                        <div class="space-y-3">
                            <div class="text-4xl mx-auto py-2">
                                {{ {sparkles:'✨', star:'⭐', heart:'💜', crown:'👑', trophy:'🏆'}[badge.icon] || '🎖️' }}
                            </div>
                            <div>
                                <h4 class="font-black text-sm text-slate-900 dark:text-white">{{ badge.name }}</h4>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block mt-0.5">
                                    {{ badge.minimum }} atendimento(s)
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black',
                                    badge.earned ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                                ]"
                            >
                                <i :class="badge.earned ? 'fa-solid fa-check' : 'fa-solid fa-lock'" class="text-[9px]"></i>
                                <span>{{ badge.earned ? 'Conquistado' : 'Bloqueado' }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: AVALIAÇÃO PÚBLICA DO ESTABELECIMENTO -->
            <Teleport to="body">
                <div
                    v-if="companyReviewModalOpen && activeReviewCompany"
                    class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md"
                    @click="closeCompanyReviewModal"
                >
                    <div class="w-full max-w-lg rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl p-6 sm:p-7 space-y-6 relative overflow-hidden" @click.stop>
                        <!-- Modal Header -->
                        <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-12 h-12 rounded-2xl overflow-hidden bg-gradient-to-tr from-indigo-600 to-cyan-500 text-white flex items-center justify-center font-black text-base shadow-md shrink-0">
                                    <img v-if="activeReviewCompany.logo_url" :src="activeReviewCompany.logo_url" :alt="activeReviewCompany.name" class="w-full h-full object-cover" />
                                    <i v-else class="fa-solid fa-store"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white truncate">
                                            {{ activeReviewCompany.name }}
                                        </h3>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 mt-1">
                                        <i class="fa-solid fa-globe text-[9px]"></i>
                                        <span>Avaliação Pública da Empresa</span>
                                    </span>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeCompanyReviewModal"
                                class="w-8 h-8 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 transition-all cursor-pointer"
                            >
                                <i class="fa-solid fa-xmark text-base"></i>
                            </button>
                        </div>

                        <form @submit.prevent="saveCompanyReview" class="space-y-5">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Sua Nota para o Estabelecimento</label>
                                    <span class="text-xs font-black text-amber-500">
                                        {{ getRatingLabel(companyReviewForm.hoverRating || companyReviewForm.rating) }}
                                    </span>
                                </div>

                                <!-- Star Rating Picker -->
                                <div class="flex items-center justify-center gap-2 bg-slate-50 dark:bg-slate-950/60 p-3.5 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                                    <button
                                        v-for="star in 5"
                                        :key="star"
                                        type="button"
                                        @click="companyReviewForm.rating = star"
                                        @mouseenter="companyReviewForm.hoverRating = star"
                                        @mouseleave="companyReviewForm.hoverRating = 0"
                                        class="p-1.5 text-3xl transition-transform hover:scale-125 focus:outline-none cursor-pointer"
                                        :title="getRatingLabel(star)"
                                    >
                                        <i
                                            class="fa-star"
                                            :class="star <= (companyReviewForm.hoverRating || companyReviewForm.rating) ? 'fa-solid text-amber-400 drop-shadow-sm' : 'fa-regular text-slate-300 dark:text-slate-700'"
                                        ></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Quick Compliments for Company -->
                            <div class="space-y-1.5">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Elogios Rápidos para a Empresa:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="chip in companyQuickCompliments"
                                        :key="chip"
                                        type="button"
                                        @click="appendCompanyCompliment(chip)"
                                        class="px-2.5 py-1 rounded-xl text-[11px] font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-cyan-400 transition-all cursor-pointer shadow-2xs"
                                    >
                                        {{ chip }}
                                    </button>
                                </div>
                            </div>

                            <!-- Testimonial Textarea -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Seu Depoimento / Comentário Público</label>
                                <textarea
                                    v-model="companyReviewForm.comment"
                                    rows="3"
                                    maxlength="2000"
                                    placeholder="Escreva como foi sua experiência geral com este estabelecimento, ambiente, atendimento e estrutura..."
                                    class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all shadow-xs resize-y min-h-[90px]"
                                ></textarea>
                                <div class="flex items-center justify-between text-[11px] text-slate-400">
                                    <span>Esta mensagem será exibida na página pública da empresa.</span>
                                    <span>{{ companyReviewForm.comment ? companyReviewForm.comment.length : 0 }} / 2000</span>
                                </div>
                            </div>

                            <!-- Modal Action Buttons -->
                            <div class="flex items-center justify-end gap-2.5 pt-2">
                                <button
                                    type="button"
                                    @click="closeCompanyReviewModal"
                                    class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-all cursor-pointer"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    :disabled="companyReviewForm.processing"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-xs font-black shadow-lg shadow-amber-500/25 transition-all cursor-pointer disabled:opacity-50"
                                >
                                    <i v-if="companyReviewForm.processing" class="fa-solid fa-spinner fa-spin"></i>
                                    <i v-else class="fa-solid fa-star text-xs"></i>
                                    <span>{{ companyReviewForm.processing ? 'Publicando...' : (activeReviewCompany?.company_review ? 'Atualizar Avaliação Pública' : 'Publicar Avaliação na Página') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </ClientPortalLayout>
</template>
