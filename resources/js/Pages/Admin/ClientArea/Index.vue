<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();

const props = defineProps({
    clients: { type: Object, default: () => ({ data: [], links: [] }) },
    serviceReviews: { type: Object, default: () => ({ data: [], links: [] }) },
    companyReviews: { type: Object, default: () => ({ data: [], links: [] }) },
    portalCustomization: { type: Object, default: () => ({}) },
    coupons: { type: Array, default: () => [] },
    loyaltyTiers: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    scopeLabel: { type: String, default: 'Toda a empresa' },
});

const activeTab = ref('clients');
const couponSubTab = ref('coupons');
const selectedClient = ref(null);
const showClientModal = ref(false);
const showEditModal = ref(false);
const showCouponModal = ref(false);
const editingCoupon = ref(null);
const showGiftModal = ref(false);
const moderationBusy = ref(null);

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    return (page.props.auth?.permissions || []).includes(permission);
};

const filterForm = useForm({
    search: props.filters.search || '',
    review_service: props.filters.review_service || '',
    review_rating: props.filters.review_rating || '',
    review_visibility: props.filters.review_visibility || '',
});

const editForm = useForm({
    name: '',
    phone: '',
});

const couponForm = useForm({
    code: '',
    description: '',
    discount_type: 'percentage',
    discount_value: 10,
    min_spend: '',
    max_uses: '',
    expires_at: '',
    is_active: true,
    client_account_id: '',
});

const giftForm = useForm({
    client_account_id: '',
    code: '',
    description: '',
    discount_type: 'percentage',
    discount_value: 15,
    expires_at: '',
});

const loyaltyTiersList = ref(props.loyaltyTiers?.length ? JSON.parse(JSON.stringify(props.loyaltyTiers)) : []);
const loyaltyForm = useForm({ tiers: [] });

const openCreateCoupon = () => {
    editingCoupon.value = null;
    couponForm.reset();
    couponForm.code = 'PROMO' + Math.floor(1000 + Math.random() * 9000);
    couponForm.discount_type = 'percentage';
    couponForm.discount_value = 10;
    couponForm.is_active = true;
    couponForm.clearErrors();
    showCouponModal.value = true;
};

const openEditCoupon = (coupon) => {
    editingCoupon.value = coupon;
    couponForm.code = coupon.code;
    couponForm.description = coupon.description || '';
    couponForm.discount_type = coupon.discount_type;
    couponForm.discount_value = coupon.discount_value;
    couponForm.min_spend = coupon.min_spend || '';
    couponForm.max_uses = coupon.max_uses || '';
    couponForm.expires_at = coupon.expires_at || '';
    couponForm.is_active = coupon.is_active;
    couponForm.client_account_id = coupon.client_account_id || '';
    couponForm.clearErrors();
    showCouponModal.value = true;
};

const closeCouponModal = () => {
    showCouponModal.value = false;
    editingCoupon.value = null;
    couponForm.reset();
};

const saveCoupon = () => {
    if (editingCoupon.value) {
        couponForm.put(route('admin.client-area.coupons.update', editingCoupon.value.id), {
            preserveScroll: true,
            onSuccess: closeCouponModal,
        });
    } else {
        couponForm.post(route('admin.client-area.coupons.store'), {
            preserveScroll: true,
            onSuccess: closeCouponModal,
        });
    }
};

const toggleCoupon = (coupon) => {
    router.patch(route('admin.client-area.coupons.toggle', coupon.id), {}, {
        preserveScroll: true,
    });
};

const deleteCoupon = (coupon) => {
    if (confirm(`Tem certeza que deseja excluir o cupom ${coupon.code}?`)) {
        router.delete(route('admin.client-area.coupons.destroy', coupon.id), {
            preserveScroll: true,
        });
    }
};

const openGiftModal = (client) => {
    selectedClient.value = client;
    giftForm.client_account_id = client.id;
    giftForm.code = 'PRESENTE' + (client.name ? client.name.split(' ')[0].toUpperCase() : 'VIP') + Math.floor(100 + Math.random() * 900);
    giftForm.description = `Cupom presente especial para ${client.name}`;
    giftForm.discount_type = 'percentage';
    giftForm.discount_value = 15;
    const exp = new Date();
    exp.setDate(exp.getDate() + 30);
    giftForm.expires_at = exp.toISOString().split('T')[0];
    giftForm.clearErrors();
    showGiftModal.value = true;
};

const closeGiftModal = () => {
    showGiftModal.value = false;
    giftForm.reset();
};

const submitGiftCoupon = () => {
    giftForm.post(route('admin.client-area.coupons.gift'), {
        preserveScroll: true,
        onSuccess: closeGiftModal,
    });
};

const addLoyaltyTier = () => {
    const nextMin = loyaltyTiersList.value.length ? Math.max(...loyaltyTiersList.value.map(t => t.minimum || 0)) + 5 : 1;
    loyaltyTiersList.value.push({
        name: 'Nível ' + (loyaltyTiersList.value.length + 1),
        minimum: nextMin,
        icon: 'trophy',
        color: '#6366f1',
        reward: 'Desconto / Cortesia especial',
    });
};

const removeLoyaltyTier = (idx) => {
    loyaltyTiersList.value.splice(idx, 1);
};

const saveLoyaltyTiers = () => {
    loyaltyForm.tiers = loyaltyTiersList.value;
    loyaltyForm.post(route('admin.client-area.loyalty-tiers.update'), {
        preserveScroll: true,
    });
};

const customForm = useForm({
    portal_welcome_title: props.portalCustomization?.welcome_title || '',
    portal_welcome_subtitle: props.portalCustomization?.welcome_subtitle || '',
    portal_announcement: props.portalCustomization?.announcement || '',
    portal_announcement_enabled: !!props.portalCustomization?.announcement_enabled,
    portal_primary_color: props.portalCustomization?.primary_color || '#6366f1',
    portal_secondary_color: props.portalCustomization?.secondary_color || '#06b6d4',
    portal_show_loyalty_badges: props.portalCustomization?.show_loyalty_badges !== false,
    portal_show_reviews: props.portalCustomization?.show_reviews !== false,
    portal_show_professionals: props.portalCustomization?.show_professionals !== false,
    portal_show_service_prices: props.portalCustomization?.show_service_prices !== false,
    portal_support_whatsapp: props.portalCustomization?.support_whatsapp || '',
    portal_custom_instructions: props.portalCustomization?.custom_instructions || '',
    banner_image: null,
    logo_image: null,
});

const bannerPreview = ref(props.portalCustomization?.banner_url || null);
const logoPreview = ref(props.portalCustomization?.logo_url || null);

const handleBannerUpload = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        customForm.banner_image = file;
        bannerPreview.value = URL.createObjectURL(file);
    }
};

const handleLogoUpload = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        customForm.logo_image = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const colorPresets = [
    { primary: '#6366f1', secondary: '#06b6d4', label: 'Índigo & Ciano' },
    { primary: '#8b5cf6', secondary: '#ec4899', label: 'Roxo & Pink' },
    { primary: '#0ea5e9', secondary: '#3b82f6', label: 'Azul Real' },
    { primary: '#10b981', secondary: '#059669', label: 'Esmeralda' },
    { primary: '#f59e0b', secondary: '#d97706', label: 'Ouro & Âmbar' },
    { primary: '#f43f5e', secondary: '#be123c', label: 'Rubi & Rose' },
    { primary: '#0f172a', secondary: '#475569', label: 'Dark Slate' },
];

const applyColorPreset = (preset) => {
    customForm.portal_primary_color = preset.primary;
    customForm.portal_secondary_color = preset.secondary;
};

const saveCustomization = () => {
    customForm.post(route('admin.client-area.customization.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};

const tabs = computed(() => [
    { id: 'clients', label: 'Clientes', icon: 'fa-solid fa-users', count: props.stats.clients || 0 },
    { id: 'coupons', label: 'Cupons & Fidelidade', icon: 'fa-solid fa-ticket', count: props.coupons?.length || 0 },
    { id: 'service-reviews', label: 'Avaliações por serviço', icon: 'fa-solid fa-star-half-stroke', count: props.stats.service_reviews || 0 },
    { id: 'company-reviews', label: 'Avaliações públicas', icon: 'fa-solid fa-building-circle-check', count: props.companyReviews.total || 0 },
    { id: 'customization', label: 'Personalização do Portal', icon: 'fa-solid fa-wand-magic-sparkles' },
]);

const submitFilters = () => {
    router.get(route('admin.client-area.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const clearReviewFilters = () => {
    filterForm.review_service = '';
    filterForm.review_rating = '';
    filterForm.review_visibility = '';
    submitFilters();
};

const openClient = (client) => {
    selectedClient.value = client;
    showClientModal.value = true;
};

const closeClient = () => {
    showClientModal.value = false;
    selectedClient.value = null;
};

const openEdit = (client) => {
    selectedClient.value = client;
    editForm.name = client.name || '';
    editForm.phone = client.phone || '';
    editForm.clearErrors();
    showEditModal.value = true;
};

const closeEdit = () => {
    showEditModal.value = false;
    editForm.reset();
};

const saveClient = () => {
    editForm.patch(route('admin.client-area.clients.update', selectedClient.value.id), {
        preserveScroll: true,
        onSuccess: closeEdit,
    });
};

const toggleServiceReview = (review) => {
    moderationBusy.value = `service-${review.id}`;
    router.patch(route('admin.client-area.service-reviews.toggle-public', review.id), {}, {
        preserveScroll: true,
        onFinish: () => { moderationBusy.value = null; },
    });
};

const toggleCompanyReview = (review) => {
    moderationBusy.value = `company-${review.id}`;
    router.patch(route('admin.client-area.company-reviews.toggle-public', review.id), {}, {
        preserveScroll: true,
        onFinish: () => { moderationBusy.value = null; },
    });
};

const currency = (value) => Number(value || 0).toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const statusLabel = (status) => ({
    pending: 'Pendente',
    confirmed: 'Confirmado',
    completed: 'Concluído',
    cancelled: 'Cancelado',
}[status] || status);

const statusClass = (status) => ({
    pending: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    confirmed: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    completed: 'bg-blue-500/15 text-blue-600 dark:text-blue-400',
    cancelled: 'bg-rose-500/15 text-rose-600 dark:text-rose-400',
}[status] || 'bg-slate-500/15 text-slate-500');

const paginationLabel = (label) => {
    if (label.includes('Previous')) return 'Anterior';
    if (label.includes('Next')) return 'Próxima';
    return label.replace('&laquo;', '').replace('&raquo;', '').trim();
};
</script>

<template>
    <AdminLayout>
        <Head title="Área do Cliente - Agendae" />

        <template #header>
            <div class="min-w-0">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Área do Cliente</h1>
                <p class="text-xs opacity-60 hidden sm:block truncate">Relacionamento, histórico e avaliações dos seus clientes</p>
            </div>
        </template>

        <div class="space-y-6">
            <div v-if="page.props.flash?.success" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-bold text-emerald-700 dark:text-emerald-300">
                <i class="fa-solid fa-circle-check mr-2"></i>{{ page.props.flash.success }}
            </div>

            <section class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h2 class="text-2xl font-black tracking-tight" style="color: var(--text-heading);">Relacionamento com clientes</h2>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                            {{ scopeLabel }}
                        </span>
                    </div>
                    <p class="text-sm opacity-65">Consulte atendimentos, edite contatos locais e modere feedbacks sem alterar a conta de acesso do cliente.</p>
                </div>
            </section>

            <section class="grid grid-cols-2 xl:grid-cols-5 gap-3 sm:gap-4">
                <div class="glass-card-3d rounded-2xl p-4 sm:p-5" v-for="card in [
                    { label: 'Clientes', value: stats.clients || 0, icon: 'fa-users', colorClass: 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400' },
                    { label: 'Atendimentos', value: stats.appointments || 0, icon: 'fa-calendar-check', colorClass: 'bg-cyan-500/15 text-cyan-600 dark:text-cyan-400' },
                    { label: 'Concluídos', value: stats.completed || 0, icon: 'fa-circle-check', colorClass: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' },
                    { label: 'Feedbacks internos', value: stats.internal_reviews || 0, icon: 'fa-lock', colorClass: 'bg-amber-500/15 text-amber-600 dark:text-amber-400' },
                    { label: 'Nota média', value: stats.average_rating ? stats.average_rating + '/5' : '—', icon: 'fa-star', colorClass: 'bg-violet-500/15 text-violet-600 dark:text-violet-400' },
                ]" :key="card.label">
                    <div class="flex items-center gap-3">
                        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', card.colorClass]">
                            <i :class="`fa-solid ${card.icon}`"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider opacity-55 truncate">{{ card.label }}</p>
                            <p class="text-xl sm:text-2xl font-black" style="color: var(--text-heading);">{{ card.value }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold border transition-all"
                    :class="activeTab === tab.id
                        ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-600/25'
                        : 'bg-white/70 dark:bg-slate-900/70 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:border-indigo-500/50'"
                >
                    <i :class="tab.icon"></i>
                    <span>{{ tab.label }}</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px]" :class="activeTab === tab.id ? 'bg-white/20' : 'bg-slate-500/10'">{{ tab.count }}</span>
                </button>
            </div>

            <section v-if="activeTab === 'clients'" class="space-y-4">
                <form @submit.prevent="submitFilters" class="glass-card-3d rounded-2xl p-3 sm:p-4 flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input v-model="filterForm.search" type="search" class="form-control pl-9" placeholder="Buscar por nome, e-mail ou telefone" />
                    </div>
                    <button type="submit" class="btn btn-primary rounded-xl"><i class="fa-solid fa-search"></i>Buscar</button>
                </form>

                <div v-if="clients.data?.length" class="glass-card-3d rounded-3xl overflow-hidden p-0">
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead><tr><th>Cliente</th><th>Relacionamento</th><th>Última visita</th><th>Total concluído</th><th class="text-right">Ações</th></tr></thead>
                            <tbody>
                                <tr v-for="client in clients.data" :key="client.id">
                                    <td>
                                        <div class="flex items-center gap-3 min-w-[220px]">
                                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-cyan-500 text-white flex items-center justify-center font-black shrink-0">
                                                {{ (client.name || 'C').substring(0, 2).toUpperCase() }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-extrabold truncate" style="color: var(--text-heading);">{{ client.name }}</p>
                                                <p class="text-xs opacity-60 truncate">{{ client.email }}</p>
                                                <p v-if="client.phone" class="text-xs opacity-60">{{ client.phone }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><p class="font-bold">{{ client.appointments_count }} atendimentos</p><p class="text-xs opacity-55">{{ client.completed_count }} concluídos · {{ client.reviews_count }} avaliações</p></td>
                                    <td><span class="text-xs font-semibold">{{ client.last_visit || 'Sem visita registrada' }}</span></td>
                                    <td><span class="font-black text-emerald-600 dark:text-emerald-400">{{ currency(client.total_spent) }}</span></td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <button v-if="hasPermission('clients.edit')" type="button" @click="openGiftModal(client)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs text-purple-600 dark:text-purple-400 border-purple-300 dark:border-purple-800 hover:bg-purple-500/10">
                                                <i class="fa-solid fa-gift mr-1"></i>Presentear Cupom
                                            </button>
                                            <button type="button" @click="openClient(client)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Histórico</button>
                                            <button v-if="hasPermission('clients.edit')" type="button" @click="openEdit(client)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs"><i class="fa-solid fa-pen mr-1"></i>Editar</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else class="glass-card-3d rounded-3xl p-10 text-center">
                    <i class="fa-solid fa-user-group text-4xl text-indigo-400 mb-3"></i><h3 class="font-black text-lg">Nenhum cliente encontrado</h3><p class="text-sm opacity-60 mt-1">Os clientes aparecerão aqui após o primeiro agendamento.</p>
                </div>

                <div v-if="clients.last_page > 1" class="flex flex-wrap justify-center gap-2">
                    <Link v-for="link in clients.links" :key="link.label" :href="link.url || '#'" preserve-scroll preserve-state class="px-3 py-2 rounded-xl text-xs font-bold border" :class="[link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-800', !link.url ? 'opacity-40 pointer-events-none' : '']">{{ paginationLabel(link.label) }}</Link>
                </div>
            </section>

            <!-- TAB 2: CUPONS & FIDELIDADE -->
            <section v-if="activeTab === 'coupons'" class="space-y-6">
                <!-- Sub-nav bar: Cupons vs Níveis de Fidelidade -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-900 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            @click="couponSubTab = 'coupons'"
                            :class="['px-4 py-2 rounded-xl text-xs sm:text-sm font-black transition-all cursor-pointer flex items-center gap-2', couponSubTab === 'coupons' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
                        >
                            <i class="fa-solid fa-ticket"></i>
                            <span>Cupons de Desconto</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px]" :class="couponSubTab === 'coupons' ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-800'">
                                {{ coupons.length }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="couponSubTab = 'tiers'"
                            :class="['px-4 py-2 rounded-xl text-xs sm:text-sm font-black transition-all cursor-pointer flex items-center gap-2', couponSubTab === 'tiers' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
                        >
                            <i class="fa-solid fa-trophy"></i>
                            <span>Regras & Medalhas de Fidelidade</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px]" :class="couponSubTab === 'tiers' ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-800'">
                                {{ loyaltyTiersList.length }}
                            </span>
                        </button>
                    </div>

                    <div v-if="couponSubTab === 'coupons'" class="flex items-center gap-2">
                        <button
                            v-if="hasPermission('clients.edit')"
                            type="button"
                            @click="openCreateCoupon"
                            class="btn btn-primary rounded-xl text-xs font-black flex items-center gap-2"
                        >
                            <i class="fa-solid fa-plus"></i>
                            <span>Novo Cupom</span>
                        </button>
                    </div>
                </div>

                <!-- SUB-TAB 1: CUPONS LIST -->
                <div v-if="couponSubTab === 'coupons'" class="space-y-4">
                    <div v-if="coupons.length === 0" class="glass-card-3d rounded-3xl p-10 text-center space-y-3">
                        <i class="fa-solid fa-ticket text-4xl text-indigo-400 mb-2"></i>
                        <h3 class="font-black text-lg">Nenhum cupom cadastrado</h3>
                        <p class="text-sm opacity-60 max-w-sm mx-auto">
                            Crie cupons de desconto em percentual ou valor fixo para presentear clientes fiéis e atrair novos agendamentos!
                        </p>
                        <button
                            v-if="hasPermission('clients.edit')"
                            type="button"
                            @click="openCreateCoupon"
                            class="btn btn-primary rounded-xl text-xs font-bold mt-2"
                        >
                            <i class="fa-solid fa-plus mr-1.5"></i>Criar Primeiro Cupom
                        </button>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <article
                            v-for="coupon in coupons"
                            :key="coupon.id"
                            class="glass-card-3d rounded-2xl p-5 space-y-4 flex flex-col justify-between"
                        >
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="px-3 py-1 rounded-xl font-black text-xs tracking-wider uppercase" :class="coupon.is_valid ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : 'bg-slate-500/15 text-slate-500'">
                                        {{ coupon.formatted_discount }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="coupon.is_active ? 'bg-indigo-500/15 text-indigo-600 dark:text-cyan-400' : 'bg-rose-500/15 text-rose-500'">
                                        {{ coupon.is_active ? 'Ativo' : 'Pausado' }}
                                    </span>
                                </div>

                                <div>
                                    <h4 class="text-base font-black tracking-wider text-indigo-600 dark:text-cyan-400">
                                        {{ coupon.code }}
                                    </h4>
                                    <p class="text-xs opacity-75 mt-0.5">{{ coupon.description || 'Desconto promocional' }}</p>
                                </div>

                                <div class="p-3 rounded-xl bg-slate-500/5 text-xs space-y-1">
                                    <p v-if="coupon.client_name" class="font-bold text-purple-600 dark:text-purple-400">
                                        <i class="fa-solid fa-user-tag mr-1"></i>Exclusivo para: {{ coupon.client_name }}
                                    </p>
                                    <p v-if="coupon.min_spend">
                                        <i class="fa-solid fa-circle-info text-[10px] mr-1 opacity-50"></i>
                                        Gasto mínimo: {{ currency(coupon.min_spend) }}
                                    </p>
                                    <p>
                                        <i class="fa-solid fa-chart-pie text-[10px] mr-1 opacity-50"></i>
                                        Utilizações: <strong>{{ coupon.uses_count }}</strong>{{ coupon.max_uses ? ` de ${coupon.max_uses}` : ' (sem limite)' }}
                                    </p>
                                    <p v-if="coupon.expires_at_formatted">
                                        <i class="fa-solid fa-calendar-xmark text-[10px] mr-1 opacity-50"></i>
                                        Expira em: {{ coupon.expires_at_formatted }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="hasPermission('clients.edit')" class="flex items-center justify-between gap-2 pt-3 border-t" style="border-color: var(--border);">
                                <button
                                    type="button"
                                    @click="toggleCoupon(coupon)"
                                    class="btn btn-outline !px-2.5 !py-1.5 rounded-xl text-xs"
                                >
                                    <i :class="coupon.is_active ? 'fa-solid fa-pause' : 'fa-solid fa-play'"></i>
                                    <span>{{ coupon.is_active ? 'Pausar' : 'Ativar' }}</span>
                                </button>
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="openEditCoupon(coupon)"
                                        class="btn btn-outline !px-2.5 !py-1.5 rounded-xl text-xs"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteCoupon(coupon)"
                                        class="btn btn-outline !px-2.5 !py-1.5 rounded-xl text-xs text-rose-500 hover:text-rose-700"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <!-- SUB-TAB 2: LOYALTY TIERS & BADGES CONFIG -->
                <div v-else class="space-y-6">
                    <div class="rounded-3xl border border-indigo-500/25 bg-gradient-to-r from-indigo-900/30 via-slate-900/40 to-slate-950/60 p-5 sm:p-6 text-white flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-xl">
                        <div class="space-y-1">
                            <h3 class="text-lg sm:text-xl font-black text-white">Programa de Medalhas & Recompensas por Visitas</h3>
                            <p class="text-xs sm:text-sm text-slate-300 max-w-2xl">
                                Defina quantos atendimentos concluídos são necessários para o cliente desbloquear cada nível e informe o benefício que ele ganha (ex: desconto, brinde ou cortesia).
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                type="button"
                                @click="addLoyaltyTier"
                                class="btn btn-outline !text-white !border-white/20 hover:!bg-white/10 rounded-xl text-xs flex items-center gap-2"
                            >
                                <i class="fa-solid fa-plus text-xs"></i>
                                <span>Adicionar Nível</span>
                            </button>
                            <button
                                type="button"
                                @click="saveLoyaltyTiers"
                                :disabled="loyaltyForm.processing"
                                class="btn btn-primary rounded-xl text-xs flex items-center gap-2 shadow-lg shadow-indigo-600/30"
                            >
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>{{ loyaltyForm.processing ? 'Salvando...' : 'Salvar Regras' }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(tier, idx) in loyaltyTiersList"
                            :key="idx"
                            class="glass-card-3d rounded-2xl p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center gap-4 justify-between"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/15 text-amber-500 flex items-center justify-center text-lg font-black shrink-0">
                                    {{ {sparkles:'✨', star:'⭐', heart:'💜', crown:'👑', trophy:'🏆', gem:'💎'}[tier.icon] || '🎖️' }}
                                </div>
                                <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-indigo-500/10 text-indigo-600 dark:text-cyan-400">
                                    #{{ idx + 1 }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 flex-1">
                                <div>
                                    <label class="form-label text-[10px]">Nome da Medalha / Nível</label>
                                    <input v-model="tier.name" class="form-control text-xs font-bold" placeholder="Ex: Cliente VIP Ouro" required />
                                </div>
                                <div>
                                    <label class="form-label text-[10px]">Mínimo de Visitas</label>
                                    <input type="number" min="1" v-model.number="tier.minimum" class="form-control text-xs font-bold" required />
                                </div>
                                <div>
                                    <label class="form-label text-[10px]">Ícone</label>
                                    <select v-model="tier.icon" class="form-control text-xs font-bold">
                                        <option value="sparkles">✨ Brilho (sparkles)</option>
                                        <option value="star">⭐ Estrela (star)</option>
                                        <option value="heart">💜 Coração (heart)</option>
                                        <option value="crown">👑 Coroa (crown)</option>
                                        <option value="trophy">🏆 Troféu (trophy)</option>
                                        <option value="gem">💎 Diamante (gem)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label text-[10px]">Recompensa / Benefício</label>
                                    <input v-model="tier.reward" class="form-control text-xs" placeholder="Ex: 10% OFF no corte" />
                                </div>
                            </div>

                            <button
                                v-if="loyaltyTiersList.length > 1"
                                type="button"
                                @click="removeLoyaltyTier(idx)"
                                class="w-8 h-8 rounded-xl hover:bg-rose-500/10 text-rose-500 flex items-center justify-center self-end lg:self-center shrink-0 cursor-pointer"
                                title="Remover nível"
                            >
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="activeTab === 'service-reviews'" class="space-y-4">
                <form @submit.prevent="submitFilters" class="glass-card-3d rounded-2xl p-3 sm:p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <select v-model="filterForm.review_service" class="form-control"><option value="">Todos os serviços</option><option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option></select>
                    <select v-model="filterForm.review_rating" class="form-control"><option value="">Todas as notas</option><option v-for="rating in [5,4,3,2,1]" :key="rating" :value="rating">{{ rating }} estrelas</option></select>
                    <select v-model="filterForm.review_visibility" class="form-control"><option value="">Internas e públicas</option><option value="internal">Somente internas</option><option value="public">Publicadas</option></select>
                    <div class="flex gap-2"><button class="btn btn-primary rounded-xl flex-1" type="submit">Filtrar</button><button class="btn btn-outline rounded-xl !px-3" type="button" @click="clearReviewFilters"><i class="fa-solid fa-rotate-left"></i></button></div>
                </form>

                <div v-if="serviceReviews.data?.length" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <article v-for="review in serviceReviews.data" :key="review.id" class="glass-card-3d rounded-2xl p-5 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="font-black" style="color: var(--text-heading);">{{ review.service }}</p><p class="text-xs opacity-60">{{ review.client_name }} · {{ review.professional }}</p></div>
                            <span class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-extrabold" :class="review.is_public ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-slate-500/15 text-slate-600 dark:text-slate-400'"><i :class="review.is_public ? 'fa-solid fa-globe mr-1' : 'fa-solid fa-lock mr-1'"></i>{{ review.is_public ? 'Página pública' : 'Feedback interno' }}</span>
                        </div>
                        <div class="flex items-center gap-2"><div class="text-amber-400"><i v-for="star in 5" :key="star" class="fa-star mr-0.5" :class="star <= review.rating ? 'fa-solid' : 'fa-regular'"></i></div><strong>{{ review.rating }}/5</strong></div>
                        <p class="text-sm leading-relaxed italic" :class="review.comment ? 'opacity-85' : 'opacity-45'">{{ review.comment ? `“${review.comment}”` : 'Avaliação sem comentário.' }}</p>
                        <div class="flex items-center justify-between gap-3 pt-3 border-t" style="border-color: var(--border);"><span class="text-[11px] opacity-55">Atendimento: {{ review.appointment_date }} · Enviada: {{ review.created_at }}</span><button v-if="hasPermission('clients.reviews')" type="button" :disabled="moderationBusy === `service-${review.id}`" @click="toggleServiceReview(review)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs"><i :class="review.is_public ? 'fa-solid fa-eye-slash' : 'fa-solid fa-share-nodes'"></i>{{ review.is_public ? 'Tornar interna' : 'Publicar' }}</button></div>
                    </article>
                </div>
                <div v-else class="glass-card-3d rounded-3xl p-10 text-center"><i class="fa-solid fa-comments text-4xl text-amber-400 mb-3"></i><h3 class="font-black text-lg">Nenhuma avaliação de serviço</h3><p class="text-sm opacity-60 mt-1">Estes feedbacks são internos por padrão e só aparecem publicamente após moderação.</p></div>
                <div v-if="serviceReviews.last_page > 1" class="flex flex-wrap justify-center gap-2"><Link v-for="link in serviceReviews.links" :key="link.label" :href="link.url || '#'" preserve-scroll preserve-state class="px-3 py-2 rounded-xl text-xs font-bold border" :class="[link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-800', !link.url ? 'opacity-40 pointer-events-none' : '']">{{ paginationLabel(link.label) }}</Link></div>
            </section>

            <section v-if="activeTab === 'company-reviews'" class="space-y-4">
                <div class="rounded-2xl border border-cyan-500/25 bg-cyan-500/10 px-4 py-3 text-sm text-cyan-800 dark:text-cyan-200"><i class="fa-solid fa-circle-info mr-2"></i>Estas são as avaliações gerais da empresa. Diferentemente do feedback por serviço, elas são públicas por padrão.</div>
                <div v-if="companyReviews.data?.length" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <article v-for="review in companyReviews.data" :key="review.id" class="glass-card-3d rounded-2xl p-5 space-y-4">
                        <div class="flex justify-between gap-3"><div><p class="font-black" style="color: var(--text-heading);">{{ review.client_name }}</p><p class="text-xs opacity-55">{{ review.client_email }}</p></div><span class="px-2.5 py-1 h-fit rounded-full text-[10px] font-extrabold" :class="review.is_public ? 'bg-emerald-500/15 text-emerald-600' : 'bg-rose-500/15 text-rose-600'">{{ review.is_public ? 'Visível' : 'Oculta' }}</span></div>
                        <div class="text-amber-400"><i v-for="star in 5" :key="star" class="fa-star mr-0.5" :class="star <= review.rating ? 'fa-solid' : 'fa-regular'"></i><strong class="text-slate-800 dark:text-white ml-2">{{ review.rating }}/5</strong></div>
                        <p class="text-sm italic opacity-85">{{ review.comment ? `“${review.comment}”` : 'Avaliação sem comentário.' }}</p>
                        <div class="flex items-center justify-between pt-3 border-t" style="border-color: var(--border);"><span class="text-[11px] opacity-55">{{ review.created_at }}</span><button v-if="hasPermission('clients.reviews')" type="button" :disabled="moderationBusy === `company-${review.id}`" @click="toggleCompanyReview(review)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs"><i :class="review.is_public ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>{{ review.is_public ? 'Ocultar' : 'Publicar' }}</button></div>
                    </article>
                </div>
                <div v-else class="glass-card-3d rounded-3xl p-10 text-center"><i class="fa-solid fa-building-circle-check text-4xl text-cyan-400 mb-3"></i><h3 class="font-black text-lg">Nenhuma avaliação da empresa</h3><p class="text-sm opacity-60 mt-1">Quando clientes avaliarem o estabelecimento, os comentários aparecerão aqui.</p></div>
                <div v-if="companyReviews.last_page > 1" class="flex flex-wrap justify-center gap-2"><Link v-for="link in companyReviews.links" :key="link.label" :href="link.url || '#'" preserve-scroll preserve-state class="px-3 py-2 rounded-xl text-xs font-bold border" :class="[link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-800', !link.url ? 'opacity-40 pointer-events-none' : '']">{{ paginationLabel(link.label) }}</Link></div>
            </section>

            <!-- TAB 4: PERSONALIZAÇÃO DA ÁREA DO CLIENTE -->
            <section v-if="activeTab === 'customization'" class="space-y-6">
                <div class="rounded-3xl border border-indigo-500/25 bg-gradient-to-r from-indigo-900/30 via-slate-900/40 to-slate-950/60 p-5 sm:p-6 text-white flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-xl">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-500/20 text-cyan-300 border border-indigo-500/30">
                                Personalização do Estabelecimento
                            </span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black text-white">Espaço da Empresa na Área do Cliente</h2>
                        <p class="text-xs sm:text-sm text-slate-300 max-w-2xl">
                            Personalize títulos, cores, capa, comunicado, regras e recursos para quando seus clientes acessarem a área exclusiva da sua empresa.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            :href="portalCustomization.portal_url || '/cliente'"
                            target="_blank"
                            class="btn btn-outline !text-white !border-white/20 hover:!bg-white/10 rounded-xl text-xs flex items-center gap-2"
                        >
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                            <span>Ver Portal do Cliente</span>
                        </a>
                        <button
                            type="button"
                            @click="saveCustomization"
                            :disabled="customForm.processing"
                            class="btn btn-primary rounded-xl text-xs flex items-center gap-2 shadow-lg shadow-indigo-600/30"
                        >
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>{{ customForm.processing ? 'Salvando...' : 'Salvar Personalização' }}</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- LEFT COLUMN: EDIT FORM -->
                    <div class="lg:col-span-7 space-y-6">
                        <!-- Card 1: Visual & Cores -->
                        <div class="glass-card-3d rounded-3xl p-5 sm:p-6 space-y-5">
                            <div class="flex items-center gap-3 border-b pb-3" style="border-color: var(--border);">
                                <div class="w-9 h-9 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-cyan-400 flex items-center justify-center text-base shrink-0">
                                    <i class="fa-solid fa-palette"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-base" style="color: var(--text-heading);">Identidade Visual & Cores</h3>
                                    <p class="text-xs opacity-60">Defina a paleta e as imagens exibidas aos seus clientes no portal.</p>
                                </div>
                            </div>

                            <!-- Color Presets -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider opacity-70 block">Paletas Sugeridas</label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="preset in colorPresets"
                                        :key="preset.label"
                                        type="button"
                                        @click="applyColorPreset(preset)"
                                        class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-indigo-500 flex items-center gap-2 text-xs font-semibold transition-all cursor-pointer bg-white/50 dark:bg-slate-900/50"
                                        :class="customForm.portal_primary_color === preset.primary ? 'ring-2 ring-indigo-500' : ''"
                                    >
                                        <span class="w-3.5 h-3.5 rounded-full" :style="{ backgroundColor: preset.primary }"></span>
                                        <span class="w-3.5 h-3.5 rounded-full -ml-1" :style="{ backgroundColor: preset.secondary }"></span>
                                        <span>{{ preset.label }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="form-label text-xs">Cor Primária do Portal</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            v-model="customForm.portal_primary_color"
                                            class="w-10 h-10 rounded-xl border border-slate-300 dark:border-slate-700 cursor-pointer p-1 bg-transparent"
                                        />
                                        <input
                                            type="text"
                                            v-model="customForm.portal_primary_color"
                                            class="form-control text-xs font-mono uppercase"
                                            placeholder="#6366f1"
                                        />
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="form-label text-xs">Cor Secundária / Gradiente</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            v-model="customForm.portal_secondary_color"
                                            class="w-10 h-10 rounded-xl border border-slate-300 dark:border-slate-700 cursor-pointer p-1 bg-transparent"
                                        />
                                        <input
                                            type="text"
                                            v-model="customForm.portal_secondary_color"
                                            class="form-control text-xs font-mono uppercase"
                                            placeholder="#06b6d4"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Uploads: Logo & Banner -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t" style="border-color: var(--border);">
                                <div class="space-y-2">
                                    <label class="form-label text-xs">Logo no Portal</label>
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border flex items-center justify-center shrink-0">
                                            <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                                            <i v-else class="fa-solid fa-store opacity-40"></i>
                                        </div>
                                        <label class="btn btn-outline !py-2 !px-3 rounded-xl text-xs cursor-pointer">
                                            <i class="fa-solid fa-upload mr-1.5"></i>Alterar Logo
                                            <input type="file" @change="handleLogoUpload" accept="image/*" class="hidden" />
                                        </label>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="form-label text-xs">Capa / Banner do Espaço</label>
                                    <div class="flex items-center gap-3">
                                        <div class="w-16 h-12 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border flex items-center justify-center shrink-0">
                                            <img v-if="bannerPreview" :src="bannerPreview" class="w-full h-full object-cover" />
                                            <i v-else class="fa-solid fa-image opacity-40"></i>
                                        </div>
                                        <label class="btn btn-outline !py-2 !px-3 rounded-xl text-xs cursor-pointer">
                                            <i class="fa-solid fa-upload mr-1.5"></i>Alterar Capa
                                            <input type="file" @change="handleBannerUpload" accept="image/*" class="hidden" />
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Textos & Comunicados -->
                        <div class="glass-card-3d rounded-3xl p-5 sm:p-6 space-y-5">
                            <div class="flex items-center gap-3 border-b pb-3" style="border-color: var(--border);">
                                <div class="w-9 h-9 rounded-xl bg-purple-500/15 text-purple-600 dark:text-purple-400 flex items-center justify-center text-base shrink-0">
                                    <i class="fa-solid fa-signature"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-base" style="color: var(--text-heading);">Textos, Boas-Vindas & Comunicados</h3>
                                    <p class="text-xs opacity-60">Personalize o título, slogan e mensagens de destaque exibidas aos clientes.</p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <label class="form-label text-xs">Título de Boas-Vindas no Espaço</label>
                                    <input
                                        type="text"
                                        v-model="customForm.portal_welcome_title"
                                        class="form-control text-sm font-bold"
                                        placeholder="Ex: Bem-vindo ao Espaço Exclusivo Barbearia Alfa"
                                    />
                                </div>

                                <div>
                                    <label class="form-label text-xs">Subtítulo / Mensagem aos Clientes</label>
                                    <input
                                        type="text"
                                        v-model="customForm.portal_welcome_subtitle"
                                        class="form-control text-xs"
                                        placeholder="Ex: Acompanhe seus horários, histórico e acumule benefícios de fidelidade."
                                    />
                                </div>

                                <!-- Announcement Bar Config -->
                                <div class="p-4 rounded-2xl border border-amber-500/30 bg-amber-500/5 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-bullhorn text-amber-500"></i>
                                            <span class="text-xs font-bold text-amber-700 dark:text-amber-300">Comunicado / Aviso em Destaque</span>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" v-model="customForm.portal_announcement_enabled" class="sr-only peer" />
                                            <div class="w-10 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-amber-500"></div>
                                        </label>
                                    </div>
                                    <input
                                        type="text"
                                        v-model="customForm.portal_announcement"
                                        class="form-control text-xs"
                                        placeholder="Ex: 🎉 Neste mês de aniversário, clientes ganham corte de cortesia após 5 visitas!"
                                        :disabled="!customForm.portal_announcement_enabled"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Recursos, Módulos & Suporte -->
                        <div class="glass-card-3d rounded-3xl p-5 sm:p-6 space-y-5">
                            <div class="flex items-center gap-3 border-b pb-3" style="border-color: var(--border);">
                                <div class="w-9 h-9 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-base shrink-0">
                                    <i class="fa-solid fa-sliders"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-base" style="color: var(--text-heading);">Módulos, Regras & Contato</h3>
                                    <p class="text-xs opacity-60">Habilite ou restrinja o que os clientes podem ver e interagir no portal.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-center justify-between p-3 rounded-2xl border bg-white/50 dark:bg-slate-900/50 cursor-pointer" style="border-color: var(--border);">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-extrabold block">Medalhas de Fidelidade</span>
                                        <span class="text-[11px] opacity-60">Exibir conquistas por visitas</span>
                                    </div>
                                    <input type="checkbox" v-model="customForm.portal_show_loyalty_badges" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                </label>

                                <label class="flex items-center justify-between p-3 rounded-2xl border bg-white/50 dark:bg-slate-900/50 cursor-pointer" style="border-color: var(--border);">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-extrabold block">Avaliações & Feedbacks</span>
                                        <span class="text-[11px] opacity-60">Permitir avaliar atendimentos</span>
                                    </div>
                                    <input type="checkbox" v-model="customForm.portal_show_reviews" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                </label>

                                <label class="flex items-center justify-between p-3 rounded-2xl border bg-white/50 dark:bg-slate-900/50 cursor-pointer" style="border-color: var(--border);">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-extrabold block">Profissionais Atendentes</span>
                                        <span class="text-[11px] opacity-60">Mostrar equipe que atendeu</span>
                                    </div>
                                    <input type="checkbox" v-model="customForm.portal_show_professionals" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                </label>

                                <label class="flex items-center justify-between p-3 rounded-2xl border bg-white/50 dark:bg-slate-900/50 cursor-pointer" style="border-color: var(--border);">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-extrabold block">Preços & Gastos</span>
                                        <span class="text-[11px] opacity-60">Mostrar valores no histórico</span>
                                    </div>
                                    <input type="checkbox" v-model="customForm.portal_show_service_prices" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                </label>
                            </div>

                            <div class="space-y-3 pt-2 border-t" style="border-color: var(--border);">
                                <div>
                                    <label class="form-label text-xs">WhatsApp de Suporte aos Clientes no Portal</label>
                                    <div class="relative">
                                        <i class="fa-brands fa-whatsapp absolute left-3.5 top-1/2 -translate-y-1/2 text-emerald-500"></i>
                                        <input
                                            type="text"
                                            v-model="customForm.portal_support_whatsapp"
                                            class="form-control pl-9 text-xs"
                                            placeholder="(11) 98888-7777"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label text-xs">Orientações, Regras & Políticas de Atendimento</label>
                                    <textarea
                                        v-model="customForm.portal_custom_instructions"
                                        rows="2"
                                        class="form-control text-xs"
                                        placeholder="Ex: Solicitamos chegar com 10 minutos de antecedência. Cancelamentos devem ser feitos com no mínimo 2 horas de aviso."
                                    ></textarea>
                                </div>
                            </div>

                            <div class="pt-3 border-t flex justify-end" style="border-color: var(--border);">
                                <button
                                    type="button"
                                    @click="saveCustomization"
                                    :disabled="customForm.processing"
                                    class="btn btn-primary rounded-xl text-xs font-bold"
                                >
                                    <i class="fa-solid fa-floppy-disk mr-1.5"></i>
                                    {{ customForm.processing ? 'Salvando...' : 'Salvar Todas as Configurações' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: LIVE PREVIEW -->
                    <div class="lg:col-span-5 space-y-4">
                        <div class="sticky top-20 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black uppercase tracking-wider opacity-60 flex items-center gap-1.5">
                                    <i class="fa-solid fa-eye text-cyan-500"></i>
                                    Pré-visualização em Tempo Real
                                </span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    Ao Vivo
                                </span>
                            </div>

                            <!-- Mockup Container -->
                            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 p-3 sm:p-4 shadow-2xl space-y-3 overflow-hidden">
                                <!-- Mock Header -->
                                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 flex items-center justify-between shadow-xs">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-xl overflow-hidden border flex items-center justify-center shrink-0" :style="{ backgroundColor: customForm.portal_primary_color }">
                                            <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                                            <i v-else class="fa-solid fa-store text-white text-xs"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-black truncate">{{ portalCustomization.company_name }}</p>
                                            <span class="text-[9px] font-bold text-slate-400 block">Área do Cliente</span>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-cyan-400">
                                        Cliente VIP
                                    </span>
                                </div>

                                <!-- Mock Announcement -->
                                <div
                                    v-if="customForm.portal_announcement_enabled && customForm.portal_announcement"
                                    class="p-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300 text-[11px] font-bold flex items-center gap-2"
                                >
                                    <i class="fa-solid fa-bullhorn text-xs shrink-0"></i>
                                    <span class="truncate">{{ customForm.portal_announcement }}</span>
                                </div>

                                <!-- Mock Branded Hero Banner -->
                                <div
                                    class="rounded-2xl p-4 text-white space-y-3 shadow-lg relative overflow-hidden"
                                    :style="{
                                        background: `linear-gradient(135deg, ${customForm.portal_primary_color} 0%, ${customForm.portal_secondary_color} 100%)`
                                    }"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-white text-slate-900 flex items-center justify-center font-black text-sm shrink-0 overflow-hidden shadow-xs">
                                            <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                                            <i v-else class="fa-solid fa-store text-xs" :style="{ color: customForm.portal_primary_color }"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase bg-black/20 text-white/90">Espaço Ativo</span>
                                            <h4 class="font-black text-sm truncate">{{ customForm.portal_welcome_title || portalCustomization.company_name }}</h4>
                                            <p class="text-[10px] text-white/80 truncate">{{ customForm.portal_welcome_subtitle || 'Acompanhe seus horários e conquistas.' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-white text-slate-900 shadow-2xs flex items-center gap-1">
                                            <i class="fa-solid fa-calendar-plus text-[9px]" :style="{ color: customForm.portal_primary_color }"></i>
                                            Agendar
                                        </span>
                                        <span v-if="customForm.portal_support_whatsapp" class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-600 text-white shadow-2xs flex items-center gap-1">
                                            <i class="fa-brands fa-whatsapp text-[10px]"></i>
                                            Suporte
                                        </span>
                                        <span v-if="customForm.portal_show_reviews" class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-black/25 text-amber-300 flex items-center gap-1">
                                            <i class="fa-solid fa-star text-[9px]"></i>
                                            Avaliar
                                        </span>
                                    </div>

                                    <p v-if="customForm.portal_custom_instructions" class="text-[10px] text-white/80 border-t border-white/15 pt-2 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-info text-[9px]"></i>
                                        <span class="truncate">{{ customForm.portal_custom_instructions }}</span>
                                    </p>
                                </div>

                                <!-- Mock Loyalty Badge Card -->
                                <div v-if="customForm.portal_show_loyalty_badges" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-trophy"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold">Cliente VIP Ouro</p>
                                            <span class="text-[9px] text-slate-400">5 de 5 atendimentos concluídos</span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-600">Conquistada</span>
                                </div>

                                <!-- Mock Appointment Item -->
                                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 space-y-2">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-xs font-extrabold">Corte de Cabelo & Barba</p>
                                            <span v-if="customForm.portal_show_professionals" class="text-[10px] text-slate-400 block">Profissional: Carlos Eduardo</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-500/15 text-emerald-600">Confirmado</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] pt-1 border-t border-slate-100 dark:border-slate-800 text-slate-500">
                                        <span>📅 25/08 às 14:00</span>
                                        <strong v-if="customForm.portal_show_service_prices" class="text-slate-900 dark:text-white font-black">R$ 80,00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <Teleport to="body">
            <div v-if="showClientModal && selectedClient" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="closeClient">
                <div class="liquid-glass-card w-full max-w-3xl max-h-[90vh] overflow-y-auto p-5 sm:p-7 space-y-5">
                    <div class="flex items-start justify-between gap-4"><div><h3 class="text-xl font-black" style="color: var(--text-heading);">Histórico de {{ selectedClient.name }}</h3><p class="text-xs opacity-60">{{ selectedClient.appointments_count }} atendimentos registrados</p></div><button type="button" @click="closeClient" class="w-9 h-9 rounded-xl hover:bg-slate-500/10"><i class="fa-solid fa-xmark"></i></button></div>
                    <div class="space-y-3">
                        <div v-for="item in selectedClient.history" :key="item.id" class="rounded-2xl border p-4" style="border-color: var(--border); background: var(--surface-subtle);">
                            <div class="flex flex-wrap justify-between gap-3"><div><p class="font-extrabold">{{ item.service }}</p><p class="text-xs opacity-60">{{ item.professional }} · {{ item.date }}</p></div><div class="text-right"><span class="px-2 py-1 rounded-lg text-[10px] font-bold" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span><p class="font-black mt-1">{{ currency(item.price) }}</p></div></div>
                            <div v-if="item.review" class="mt-3 pt-3 border-t text-xs" style="border-color: var(--border);"><span class="text-amber-400 mr-2"><i v-for="star in 5" :key="star" class="fa-star" :class="star <= item.review.rating ? 'fa-solid' : 'fa-regular'"></i></span><span class="italic opacity-70">{{ item.review.comment || 'Sem comentário' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showEditModal && selectedClient" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="closeEdit">
                <form @submit.prevent="saveClient" class="liquid-glass-card w-full max-w-lg p-6 space-y-5">
                    <div class="flex justify-between gap-4"><div><h3 class="text-lg font-black">Editar contato do cliente</h3><p class="text-xs opacity-60 mt-1">A alteração vale para o histórico visível da empresa.</p></div><button type="button" @click="closeEdit"><i class="fa-solid fa-xmark"></i></button></div>
                    <div><label class="form-label">Nome</label><input v-model="editForm.name" class="form-control" required /><p v-if="editForm.errors.name" class="text-xs text-rose-500 mt-1">{{ editForm.errors.name }}</p></div>
                    <div><label class="form-label">Telefone</label><input v-model="editForm.phone" class="form-control" placeholder="(11) 99999-9999" /><p v-if="editForm.errors.phone" class="text-xs text-rose-500 mt-1">{{ editForm.errors.phone }}</p></div>
                    <div class="rounded-xl bg-slate-500/10 p-3 text-xs opacity-70"><i class="fa-solid fa-lock mr-1.5"></i>O e-mail <strong>{{ selectedClient.account_email }}</strong> identifica a conta global do cliente e não pode ser alterado pela empresa.</div>
                    <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline rounded-xl" @click="closeEdit">Cancelar</button><button type="submit" class="btn btn-primary rounded-xl" :disabled="editForm.processing"><i class="fa-solid fa-floppy-disk"></i>{{ editForm.processing ? 'Salvando...' : 'Salvar dados' }}</button></div>
                </form>
            </div>

            <!-- MODAL: CRIAR / EDITAR CUPOM -->
            <div v-if="showCouponModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="closeCouponModal">
                <form @submit.prevent="saveCoupon" class="liquid-glass-card w-full max-w-xl p-6 sm:p-7 space-y-5 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between gap-4 border-b pb-3" style="border-color: var(--border);">
                        <div>
                            <h3 class="text-lg font-black" style="color: var(--text-heading);">
                                {{ editingCoupon ? 'Editar Cupom de Desconto' : 'Novo Cupom de Desconto' }}
                            </h3>
                            <p class="text-xs opacity-60">Configure o código, tipo de desconto e regras de aplicação.</p>
                        </div>
                        <button type="button" @click="closeCouponModal" class="w-8 h-8 rounded-xl hover:bg-slate-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label text-xs font-bold block mb-1">Código do Cupom *</label>
                            <input
                                v-model="couponForm.code"
                                type="text"
                                class="form-control font-black text-sm uppercase tracking-wider"
                                placeholder="EX: VERAO15"
                                required
                            />
                            <p v-if="couponForm.errors.code" class="text-xs text-rose-500 mt-1">{{ couponForm.errors.code }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="form-label text-xs font-bold block mb-1">Descrição / Finalidade</label>
                            <input
                                v-model="couponForm.description"
                                type="text"
                                class="form-control text-xs"
                                placeholder="Ex: Desconto especial de boas-vindas"
                            />
                        </div>

                        <div>
                            <label class="form-label text-xs font-bold block mb-1">Tipo de Desconto *</label>
                            <select v-model="couponForm.discount_type" class="form-control text-xs font-bold" required>
                                <option value="percentage">Porcentagem (%)</option>
                                <option value="fixed">Valor Fixo (R$)</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label text-xs font-bold block mb-1">
                                {{ couponForm.discount_type === 'percentage' ? 'Percentual (%) *' : 'Valor (R$) *' }}
                            </label>
                            <input
                                v-model.number="couponForm.discount_value"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="form-control text-xs font-bold"
                                required
                            />
                            <p v-if="couponForm.errors.discount_value" class="text-xs text-rose-500 mt-1">{{ couponForm.errors.discount_value }}</p>
                        </div>

                        <div>
                            <label class="form-label text-xs font-bold block mb-1">Valor Mínimo do Serviço (R$)</label>
                            <input
                                v-model.number="couponForm.min_spend"
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control text-xs"
                                placeholder="Opcional"
                            />
                        </div>

                        <div>
                            <label class="form-label text-xs font-bold block mb-1">Limite Máximo de Usos</label>
                            <input
                                v-model.number="couponForm.max_uses"
                                type="number"
                                min="1"
                                class="form-control text-xs"
                                placeholder="Ilimitado se vazio"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="form-label text-xs font-bold block mb-1">Data de Validade (Expiração)</label>
                            <input
                                v-model="couponForm.expires_at"
                                type="date"
                                class="form-control text-xs"
                            />
                        </div>

                        <div class="sm:col-span-2 flex items-center gap-2 pt-2">
                            <input
                                type="checkbox"
                                id="coupon_is_active"
                                v-model="couponForm.is_active"
                                class="rounded"
                            />
                            <label for="coupon_is_active" class="text-xs font-bold cursor-pointer">
                                Cupom Ativo (disponível para uso)
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t" style="border-color: var(--border);">
                        <button type="button" @click="closeCouponModal" class="btn btn-outline text-xs rounded-xl">Cancelar</button>
                        <button type="submit" :disabled="couponForm.processing" class="btn btn-primary text-xs rounded-xl flex items-center gap-1.5 shadow-md">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>{{ couponForm.processing ? 'Salvando...' : (editingCoupon ? 'Atualizar Cupom' : 'Criar Cupom') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- MODAL: PRESENTEAR CUPOM EXCLUSIVO AO CLIENTE -->
            <div v-if="showGiftModal && selectedClient" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="closeGiftModal">
                <form @submit.prevent="submitGiftCoupon" class="liquid-glass-card w-full max-w-lg p-6 space-y-5">
                    <div class="flex items-start justify-between gap-4 border-b pb-3" style="border-color: var(--border);">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 rounded-lg bg-purple-500/15 text-purple-600 dark:text-purple-400">
                                    <i class="fa-solid fa-gift"></i>
                                </span>
                                <h3 class="text-lg font-black" style="color: var(--text-heading);">Presentear Cupom</h3>
                            </div>
                            <p class="text-xs opacity-60 mt-1">
                                Gere um voucher exclusivo e nominal para <strong>{{ selectedClient.name }}</strong>.
                            </p>
                        </div>
                        <button type="button" @click="closeGiftModal" class="w-8 h-8 rounded-xl hover:bg-slate-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label text-xs font-bold block mb-1">Código do Cupom *</label>
                            <input
                                v-model="giftForm.code"
                                type="text"
                                class="form-control font-black text-sm uppercase tracking-wider"
                                required
                            />
                            <p v-if="giftForm.errors.code" class="text-xs text-rose-500 mt-1">{{ giftForm.errors.code }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label text-xs font-bold block mb-1">Tipo de Desconto</label>
                                <select v-model="giftForm.discount_type" class="form-control text-xs font-bold">
                                    <option value="percentage">Porcentagem (%)</option>
                                    <option value="fixed">Valor Fixo (R$)</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-xs font-bold block mb-1">Valor do Desconto</label>
                                <input
                                    v-model.number="giftForm.discount_value"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="form-control text-xs font-bold"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="form-label text-xs font-bold block mb-1">Mensagem / Descrição do Presente</label>
                            <input
                                v-model="giftForm.description"
                                type="text"
                                class="form-control text-xs"
                                placeholder="Ex: Presente de aniversário / Cliente especial"
                            />
                        </div>

                        <div>
                            <label class="form-label text-xs font-bold block mb-1">Data de Validade</label>
                            <input
                                v-model="giftForm.expires_at"
                                type="date"
                                class="form-control text-xs"
                            />
                        </div>

                        <div class="p-3 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-xs text-purple-900 dark:text-purple-200">
                            <i class="fa-solid fa-sparkles mr-1.5 text-purple-500"></i>
                            Este cupom será exibido imediatamente na Área do Cliente de <strong>{{ selectedClient.name }}</strong> ({{ selectedClient.account_email }}).
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t" style="border-color: var(--border);">
                        <button type="button" @click="closeGiftModal" class="btn btn-outline text-xs rounded-xl">Cancelar</button>
                        <button type="submit" :disabled="giftForm.processing" class="btn btn-primary text-xs rounded-xl bg-purple-600 hover:bg-purple-500 text-white flex items-center gap-1.5 shadow-md">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>{{ giftForm.processing ? 'Enviando...' : 'Enviar Cupom Presente' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>
    </AdminLayout>
</template>
