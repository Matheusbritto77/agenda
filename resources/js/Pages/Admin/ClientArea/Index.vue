<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ClientListTab from './Components/ClientListTab.vue';
import CouponsLoyaltyTab from './Components/CouponsLoyaltyTab.vue';
import ServiceReviewsTab from './Components/ServiceReviewsTab.vue';
import CompanyReviewsTab from './Components/CompanyReviewsTab.vue';
import PortalCustomizationTab from './Components/PortalCustomizationTab.vue';
import ClientHistoryModal from './Components/ClientHistoryModal.vue';
import ClientEditModal from './Components/ClientEditModal.vue';
import CouponModal from './Components/CouponModal.vue';
import GiftCouponModal from './Components/GiftCouponModal.vue';

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
    selectedClient.value = null;
    editForm.reset();
};

const saveClient = () => {
    if (!selectedClient.value?.id) return;
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

const currency = (value) => `R$ ${Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const paginationLabel = (label) => {
    if (label.includes('Previous')) return 'Anterior';
    if (label.includes('Next')) return 'Próxima';
    return label;
};

const statusLabel = (status) => ({
    confirmed: 'Confirmado',
    completed: 'Concluído',
    cancelled: 'Cancelado',
    pending: 'Pendente',
}[status] || status);

const statusClass = (status) => ({
    confirmed: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    completed: 'bg-blue-500/15 text-blue-600 dark:text-blue-400',
    cancelled: 'bg-rose-500/15 text-rose-600 dark:text-rose-400',
    pending: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
}[status] || 'bg-slate-500/15 text-slate-600');
</script>

<template>
    <AdminLayout title="Área do Cliente & Avaliações">
        <Head title="Área do Cliente & Avaliações" />

        <div class="space-y-6">
            <!-- Header Section -->
            <section class="glass-card-3d rounded-3xl p-6 sm:p-8 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-cyan-400">
                                <i class="fa-solid fa-users text-lg"></i>
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-500/10" style="color: var(--text-muted);">
                                {{ scopeLabel }}
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black" style="color: var(--text-heading);">
                            Área do Cliente, Cupons & Fidelidade
                        </h1>
                        <p class="text-sm max-w-3xl" style="color: var(--text-muted);">
                            Gerencie sua base de clientes, cupons de desconto, regras de fidelidade, modere feedbacks e personalize a experiência no portal.
                        </p>
                    </div>

                    <a
                        :href="portalCustomization.portal_url || '/cliente'"
                        target="_blank"
                        class="btn btn-outline self-start lg:self-center !py-2.5 !px-4 rounded-2xl flex items-center gap-2 text-xs font-bold shadow-xs"
                    >
                        <i class="fa-solid fa-arrow-up-right-from-square text-[11px] text-indigo-500"></i>
                        <span>Acessar Portal do Cliente</span>
                    </a>
                </div>
            </section>

            <!-- Navigation Tabs Bar -->
            <div class="flex flex-wrap gap-2 p-1.5 rounded-2xl border bg-slate-50/70 dark:bg-slate-950/40" style="border-color: var(--border);">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 cursor-pointer border"
                    :class="activeTab === tab.id
                        ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-600/25'
                        : 'bg-white/70 dark:bg-slate-900/70 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:border-indigo-500/50'"
                >
                    <i :class="tab.icon"></i>
                    <span>{{ tab.label }}</span>
                    <span v-if="tab.count !== undefined" class="px-1.5 py-0.5 rounded-md text-[10px]" :class="activeTab === tab.id ? 'bg-white/20' : 'bg-slate-500/10'">
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- Tab 1: Clients List -->
            <ClientListTab
                v-if="activeTab === 'clients'"
                :clients="clients"
                :filter-form="filterForm"
                :has-permission="hasPermission"
                :currency="currency"
                :pagination-label="paginationLabel"
                @submit-filters="submitFilters"
                @open-client="openClient"
                @open-edit="openEdit"
                @open-gift="openGiftModal"
            />

            <!-- Tab 2: Coupons & Loyalty -->
            <CouponsLoyaltyTab
                v-else-if="activeTab === 'coupons'"
                :coupons="coupons"
                :loyalty-tiers-list="loyaltyTiersList"
                v-model:coupon-sub-tab="couponSubTab"
                :loyalty-form="loyaltyForm"
                :has-permission="hasPermission"
                :currency="currency"
                @open-create-coupon="openCreateCoupon"
                @open-edit-coupon="openEditCoupon"
                @toggle-coupon="toggleCoupon"
                @delete-coupon="deleteCoupon"
                @add-loyalty-tier="addLoyaltyTier"
                @remove-loyalty-tier="removeLoyaltyTier"
                @save-loyalty-tiers="saveLoyaltyTiers"
            />

            <!-- Tab 3: Service Reviews -->
            <ServiceReviewsTab
                v-else-if="activeTab === 'service-reviews'"
                :service-reviews="serviceReviews"
                :services="services"
                :filter-form="filterForm"
                :moderation-busy="moderationBusy"
                :has-permission="hasPermission"
                :pagination-label="paginationLabel"
                @submit-filters="submitFilters"
                @clear-filters="clearReviewFilters"
                @toggle-service-review="toggleServiceReview"
            />

            <!-- Tab 4: Company Reviews -->
            <CompanyReviewsTab
                v-else-if="activeTab === 'company-reviews'"
                :company-reviews="companyReviews"
                :moderation-busy="moderationBusy"
                :has-permission="hasPermission"
                :pagination-label="paginationLabel"
                @toggle-company-review="toggleCompanyReview"
            />

            <!-- Tab 5: Portal Customization -->
            <PortalCustomizationTab
                v-else-if="activeTab === 'customization'"
                :portal-customization="portalCustomization"
                :custom-form="customForm"
                :logo-preview="logoPreview"
                :banner-preview="bannerPreview"
                :color-presets="colorPresets"
                @apply-color-preset="applyColorPreset"
                @handle-logo-upload="handleLogoUpload"
                @handle-banner-upload="handleBannerUpload"
                @save-customization="saveCustomization"
            />
        </div>

        <!-- Modals -->
        <ClientHistoryModal
            :show="showClientModal"
            :client="selectedClient"
            :status-class="statusClass"
            :status-label="statusLabel"
            :currency="currency"
            @close="closeClient"
        />

        <ClientEditModal
            :show="showEditModal"
            :client="selectedClient"
            :edit-form="editForm"
            @close="closeEdit"
            @save="saveClient"
        />

        <CouponModal
            :show="showCouponModal"
            :editing-coupon="editingCoupon"
            :coupon-form="couponForm"
            @close="closeCouponModal"
            @save="saveCoupon"
        />

        <GiftCouponModal
            :show="showGiftModal"
            :client="selectedClient"
            :gift-form="giftForm"
            @close="closeGiftModal"
            @submit="submitGiftCoupon"
        />
    </AdminLayout>
</template>
