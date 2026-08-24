<script setup>
import ClientPortalLayout from '@/Layouts/ClientPortalLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';
import PortalHeroBanner from './Components/PortalHeroBanner.vue';
import PortalAppointmentsTab from './Components/PortalAppointmentsTab.vue';
import PortalCompaniesTab from './Components/PortalCompaniesTab.vue';
import PortalBadgesTab from './Components/PortalBadgesTab.vue';
import PortalCouponsTab from './Components/PortalCouponsTab.vue';
import PortalCompanyReviewModal from './Components/PortalCompanyReviewModal.vue';

const props = defineProps({
    client: {
        type: Object,
        default: () => ({ name: 'Cliente', email: '' }),
    },
    activeCompany: {
        type: Object,
        default: null,
    },
    summary: {
        type: Object,
        default: () => ({ appointments: 0, completed: 0, companies: 0, reviews: 0 }),
    },
    badges: {
        type: Array,
        default: () => [],
    },
    coupons: {
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

const copiedCouponId = ref(null);
const copyCouponCode = (coupon) => {
    navigator.clipboard.writeText(coupon.code);
    copiedCouponId.value = coupon.id;
    setTimeout(() => {
        copiedCouponId.value = null;
    }, 2500);
};

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

const selectCompany = (companyId) => {
    router.post(route('client.companies.select', companyId || 'all'), {}, {
        preserveScroll: true,
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

const filteredAppointments = computed(() => {
    if (appointmentFilter.value === 'all') return props.appointments;
    if (appointmentFilter.value === 'upcoming') {
        return props.appointments.filter(
            (apt) => apt.status === 'confirmed' || apt.status === 'pending'
        );
    }
    return props.appointments.filter((apt) => apt.status === appointmentFilter.value);
});

const statusBadge = (status) => {
    switch (status) {
        case 'confirmed':
            return {
                label: 'Confirmado',
                classes: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30',
                icon: 'fa-solid fa-circle-check',
            };
        case 'completed':
            return {
                label: 'Concluído',
                classes: 'bg-blue-500/15 text-blue-700 dark:text-blue-300 border border-blue-500/30',
                icon: 'fa-solid fa-check-double',
            };
        case 'cancelled':
            return {
                label: 'Cancelado',
                classes: 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30',
                icon: 'fa-solid fa-circle-xmark',
            };
        default:
            return {
                label: 'Pendente',
                classes: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30',
                icon: 'fa-regular fa-clock',
            };
    }
};

const firstName = computed(() => props.client.name.split(' ')[0]);
const portalCustomization = computed(() => props.activeCompany?.portal_customization || props.activeCompany || {});
const portalPrimaryColor = computed(() => portalCustomization.value.primary_color || '#6366f1');
const portalSecondaryColor = computed(() => portalCustomization.value.secondary_color || '#06b6d4');
const colorWithAlpha = (color, alpha) => {
    const value = color.replace('#', '');
    const normalized = value.length === 3 ? value.split('').map((character) => character + character).join('') : value;
    return `#${normalized}${alpha}`;
};
const activeTabStyle = (tab) => activeTab.value === tab
    ? { backgroundColor: portalPrimaryColor.value, boxShadow: `0 8px 20px ${colorWithAlpha(portalPrimaryColor.value, '33')}` }
    : {};
</script>

<template>
    <ClientPortalLayout
        :title="activeCompany ? (activeCompany.welcome_title || activeCompany.name) : 'Minha Área - Agendae'"
        :active-company="activeCompany"
        :companies="companies"
    >
        <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Hero / Active Company Branded Header -->
            <PortalHeroBanner
                :client="client"
                :active-company="activeCompany"
                :summary="summary"
                :companies="companies"
                @select-company="selectCompany"
                @switch-tab="activeTab = $event"
                @open-company-review="openCompanyReviewModal"
            />

            <!-- Hero Profile Banner & Quick Stats -->
            <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white/90 dark:bg-slate-900/90 p-6 sm:p-8 shadow-xl shadow-indigo-500/5 backdrop-blur-xl transition-all">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="flex items-center gap-4 sm:gap-5 min-w-0">
                        <div
                            class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl text-white flex items-center justify-center font-black text-2xl sm:text-3xl shadow-xl shrink-0 ring-4 ring-white/50 dark:ring-slate-800/50"
                            :style="{ background: `linear-gradient(135deg, ${portalPrimaryColor}, ${portalSecondaryColor})`, boxShadow: `0 16px 32px ${colorWithAlpha(portalPrimaryColor, '33')}` }"
                        >
                            {{ firstName.charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                                    {{ firstName }}
                                </h2>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-xs font-black border"
                                    :style="{ color: portalPrimaryColor, borderColor: colorWithAlpha(portalPrimaryColor, '33'), backgroundColor: colorWithAlpha(portalPrimaryColor, '12') }"
                                >
                                    {{ activeCompany?.badge?.name || 'Cliente VIP' }}
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-xl">
                                {{ activeCompany ? ('Gerencie seus agendamentos e experiências exclusivas com ' + activeCompany.name + '.') : 'Gerencie seus agendamentos em todos os estabelecimentos que você frequenta.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Quick stats pills -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3.5 sm:p-4 rounded-2xl border border-slate-200/70 dark:border-slate-800/70 bg-slate-50/70 dark:bg-slate-950/50 text-center">
                            <span class="block text-2xl sm:text-3xl font-black" :style="{ color: portalPrimaryColor }">{{ summary.appointments }}</span>
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
                                ? 'text-white shadow-md'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                        ]"
                        :style="activeTabStyle('appointments')"
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
                                ? 'text-white shadow-md'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                        ]"
                        :style="activeTabStyle('companies')"
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
                                ? 'text-white shadow-md'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                        ]"
                        :style="activeTabStyle('badges')"
                    >
                        <i class="fa-solid fa-award text-xs"></i>
                        <span>Medalhas & Fidelidade</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'coupons'"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                            activeTab === 'coupons'
                                ? 'text-white shadow-md'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                        ]"
                        :style="activeTabStyle('coupons')"
                    >
                        <i class="fa-solid fa-ticket text-xs"></i>
                        <span>Cupons & Descontos</span>
                        <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-black" :class="activeTab === 'coupons' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                            {{ coupons.length }}
                        </span>
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
            <PortalAppointmentsTab
                v-if="activeTab === 'appointments'"
                :filtered-appointments="filteredAppointments"
                :companies="companies"
                :review-forms="reviewForms"
                :quick-compliments="quickCompliments"
                :status-badge="statusBadge"
                :get-rating-label="getRatingLabel"
                :active-company="activeCompany"
                @switch-tab="activeTab = $event"
                @append-compliment="appendCompliment"
                @save-review="saveReview"
            />

            <!-- TAB 2: EMPRESAS & BARBEARIAS VISITADAS -->
            <PortalCompaniesTab
                v-else-if="activeTab === 'companies'"
                :companies="companies"
                @select-company="selectCompany"
                @open-company-review="openCompanyReviewModal"
            />

            <!-- TAB 3: MEDALHAS & FIDELIDADE -->
            <PortalBadgesTab
                v-else-if="activeTab === 'badges'"
                :badges="badges"
                :active-company="activeCompany"
            />

            <!-- TAB 4: CUPONS & DESCONTOS -->
            <PortalCouponsTab
                v-else-if="activeTab === 'coupons'"
                :coupons="coupons"
                :active-company="activeCompany"
                :copied-coupon-id="copiedCouponId"
                @copy-coupon="copyCouponCode"
            />
        </div>

        <!-- MODAL: AVALIAÇÃO PÚBLICA DO ESTABELECIMENTO -->
        <PortalCompanyReviewModal
            :show="companyReviewModalOpen"
            :company="activeReviewCompany"
            :company-review-form="companyReviewForm"
            :company-quick-compliments="companyQuickCompliments"
            :get-rating-label="getRatingLabel"
            @close="closeCompanyReviewModal"
            @save="saveCompanyReview"
            @append-compliment="appendCompanyCompliment"
        />
    </ClientPortalLayout>
</template>
