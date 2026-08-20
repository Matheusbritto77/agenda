<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();

const props = defineProps({
    teamMembers: {
        type: Array,
        default: () => []
    },
    services: {
        type: Array,
        default: () => []
    },
    auth: {
        type: Object,
        default: () => ({})
    },
    appDomain: {
        type: String,
        default: 'agendae.app'
    }
});

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    const userPerms = page.props.auth?.permissions || [];
    return userPerms.includes(permission);
};

const loggedUser = computed(() => props.auth?.user || {});
const loggedMember = computed(() => props.teamMembers.find(m => m.email === loggedUser.value.email));
const canManageTeam = computed(() => hasPermission('team.edit') || hasPermission('team.create'));
const canDeleteTeam = computed(() => hasPermission('team.delete'));

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showResetModal = ref(false);
const editingMember = ref(null);
const deletingMember = ref(null);
const resettingMember = ref(null);

const createActiveTab = ref('basic');
const editActiveTab = ref('basic');

const createAvatarPreview = ref('');
const editAvatarPreview = ref('');
const createDomainType = ref('subdomain');
const editDomainType = ref('subdomain');

const formatCurrency = (val) => Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const getInitials = (name) => (name || 'A').substring(0, 2).toUpperCase();

const createForm = useForm({
    name: '',
    job_title: '',
    role_id: 'professional',
    email: '',
    phone: '',
    password: '',
    avatar: null,
    avatar_url: '',
    commission_rate: 0.00,
    service_commissions: {},
    domain_type: 'subdomain',
    subdomain: '',
    custom_domain: '',
    services: [],
    bio: '',
    is_active: true,
});

const editForm = useForm({
    id: null,
    name: '',
    job_title: '',
    role_id: 'professional',
    email: '',
    phone: '',
    avatar: null,
    avatar_url: '',
    commission_rate: 0.00,
    service_commissions: {},
    domain_type: 'subdomain',
    subdomain: '',
    custom_domain: '',
    services: [],
    bio: '',
    is_active: true,
});

const deleteForm = useForm({});
const resetForm = useForm({});
const toggleForm = useForm({});

const openCreateModal = () => {
    createForm.reset();
    createForm.role_id = 'professional';
    createForm.commission_rate = 0.00;
    createForm.service_commissions = {};
    createForm.services = [];
    createForm.is_active = true;
    createDomainType.value = 'subdomain';
    createAvatarPreview.value = '';
    createActiveTab.value = 'basic';
    showCreateModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openEditModal = (member) => {
    editingMember.value = member;
    editForm.id = member.id;
    editForm.name = member.name || '';
    editForm.job_title = member.job_title || member.role_title || '';
    editForm.role_id = member.role_id || 'professional';
    editForm.email = member.email || '';
    editForm.phone = member.phone || '';
    editForm.avatar_url = member.avatar_url || '';
    editForm.commission_rate = Number(member.commission_rate || 0);
    editForm.service_commissions = { ...(member.service_commissions || {}) };
    editForm.subdomain = member.subdomain || '';
    editForm.custom_domain = member.custom_domain || '';
    editForm.bio = member.bio || '';
    editForm.is_active = !!member.is_active;
    editForm.services = [...(member.services || [])];
    editDomainType.value = member.custom_domain ? 'custom' : 'subdomain';
    editAvatarPreview.value = member.avatar_url || '';
    editActiveTab.value = 'basic';
    showEditModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openDeleteModal = (member) => {
    deletingMember.value = member;
    showDeleteModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openResetModal = (member) => {
    resettingMember.value = member;
    showResetModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    document.body.classList.remove('overflow-hidden');
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingMember.value = null;
    document.body.classList.remove('overflow-hidden');
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletingMember.value = null;
    document.body.classList.remove('overflow-hidden');
};

const closeResetModal = () => {
    showResetModal.value = false;
    resettingMember.value = null;
    document.body.classList.remove('overflow-hidden');
};

const handleBackdropClickCreate = (e) => { if (e.target === e.currentTarget) closeCreateModal(); };
const handleBackdropClickEdit = (e) => { if (e.target === e.currentTarget) closeEditModal(); };
const handleBackdropClickDelete = (e) => { if (e.target === e.currentTarget) closeDeleteModal(); };
const handleBackdropClickReset = (e) => { if (e.target === e.currentTarget) closeResetModal(); };

const previewCreateAvatar = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => { createAvatarPreview.value = e.target.result; };
        reader.readAsDataURL(file);
        createForm.avatar = file;
    }
};

const previewCreateAvatarUrl = () => {
    createAvatarPreview.value = createForm.avatar_url || '';
};

const previewEditAvatar = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => { editAvatarPreview.value = e.target.result; };
        reader.readAsDataURL(file);
        editForm.avatar = file;
    }
};

const previewEditAvatarUrl = () => {
    editAvatarPreview.value = editForm.avatar_url || '';
};

const toggleCreateDomainType = (type) => {
    createDomainType.value = type;
    createForm.domain_type = type;
};

const toggleEditDomainType = (type) => {
    editDomainType.value = type;
    editForm.domain_type = type;
};

const submitCreate = () => {
    createForm.post(route('admin.team.store'), {
        onSuccess: () => { closeCreateModal(); createForm.reset(); },
        preserveScroll: true,
    });
};

const submitEdit = () => {
    if (!editingMember.value) return;
    editForm.put(route('admin.team.update', editingMember.value.id), {
        onSuccess: () => { closeEditModal(); },
        preserveScroll: true,
    });
};

const submitDelete = () => {
    if (!deletingMember.value) return;
    deleteForm.delete(route('admin.team.destroy', deletingMember.value.id), {
        onSuccess: () => { closeDeleteModal(); },
        preserveScroll: true,
    });
};

const submitReset = () => {
    if (!resettingMember.value) return;
    resetForm.post(route('admin.team.reset-password', resettingMember.value.id), {
        onSuccess: () => { closeResetModal(); },
        preserveScroll: true,
    });
};

const toggleStatus = (member) => {
    toggleForm.patch(route('admin.team.toggle-status', member.id), { preserveScroll: true });
};

const isServiceSelected = (svcId, list) => (list || []).includes(String(svcId)) || (list || []).includes(Number(svcId));

const toggleServiceSelection = (svcId, list, formServices) => {
    const idx = formServices.value.findIndex(s => String(s) === String(svcId));
    if (idx >= 0) formServices.value.splice(idx, 1);
    else formServices.value.push(svcId);
};

const getMemberDomainDisplay = (member) => {
    if (member.custom_domain) return member.custom_domain;
    if (member.subdomain) return `${member.subdomain}.${props.appDomain}`;
    return null;
};

const getMemberServices = (member) => {
    const ids = member.services || [];
    if (!ids || ids.length === 0) return null;
    return props.services.filter(s => ids.includes(String(s.id)) || ids.includes(Number(s.id)));
};

onMounted(() => {
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            showCreateModal.value = false;
            showEditModal.value = false;
            showDeleteModal.value = false;
            showResetModal.value = false;
            document.body.classList.remove('overflow-hidden');
        }
    });
});
</script>

<template>
    <AdminLayout>
        <Head title="Time & Profissionais - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Time & Profissionais</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Gerencie especialistas, cargos, fotos, subdomínios e serviços</p>
        </template>

        <div class="space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight" style="color: var(--text-heading);">Equipe do Estabelecimento</h2>
                    <p class="text-xs sm:text-sm opacity-70">Cadastre profissionais, personalize cargos, roles no sistema, subdomínios exclusivos e associe serviços.</p>
                </div>
                <button
                    v-if="canManageTeam"
                    type="button"
                    @click="openCreateModal"
                    class="btn btn-primary self-start sm:self-auto py-2.5 px-4 rounded-xl shadow-lg shadow-indigo-600/30"
                >
                    <i class="fa-solid fa-user-plus text-xs"></i>
                    <span>+ Novo Profissional</span>
                </button>
                <button
                    v-else
                    type="button"
                    disabled
                    class="btn btn-outline opacity-50 cursor-not-allowed self-start sm:self-auto py-2.5 px-4 rounded-xl"
                    title="Apenas administradores e gerentes podem cadastrar profissionais"
                >
                    <i class="fa-solid fa-lock text-xs mr-1"></i>
                    <span>+ Novo Profissional</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="glass-card-3d p-5 rounded-2xl flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider opacity-60">Total no Time</span>
                        <h3 class="text-2xl font-black mt-0.5" style="color: var(--text-heading);">{{ teamMembers.length }}</h3>
                    </div>
                </div>

                <div class="glass-card-3d p-5 rounded-2xl flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider opacity-60">Profissionais Ativos</span>
                        <h3 class="text-2xl font-black mt-0.5 text-emerald-600 dark:text-emerald-400">{{ teamMembers.filter(m => m.is_active).length }}</h3>
                    </div>
                </div>

                <div class="glass-card-3d p-5 rounded-2xl flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/30 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-scissors"></i>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider opacity-60">Serviços Oferecidos</span>
                        <h3 class="text-2xl font-black mt-0.5" style="color: var(--text-heading);">{{ services.length }}</h3>
                    </div>
                </div>
            </div>

            <div v-if="teamMembers.length === 0" class="glass-card-3d p-12 text-center text-slate-500 dark:text-slate-400 rounded-3xl">
                <div class="w-16 h-16 rounded-3xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto mb-4 text-3xl shadow-lg shadow-indigo-500/10">
                    <i class="fa-solid fa-users"></i>
                </div>
                <h3 class="text-lg font-bold" style="color: var(--text-heading);">Nenhum profissional cadastrado</h3>
                <p class="text-xs opacity-70 mt-1.5 max-w-md mx-auto">
                    Adicione membros da equipe para permitir que seus clientes escolham com quem desejam ser atendidos no agendamento online.
                </p>
                <div class="mt-5">
                    <button v-if="canManageTeam" type="button" @click="openCreateModal" class="btn btn-primary text-xs py-2.5 px-5 rounded-xl">
                        <i class="fa-solid fa-plus text-xs mr-1"></i>
                        <span>Cadastrar Primeiro Profissional</span>
                    </button>
                </div>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div v-for="member in teamMembers" :key="member.id" class="glass-card-3d p-6 rounded-3xl flex flex-col justify-between relative overflow-hidden group">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-14 h-14 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center shrink-0 shadow-md">
                                <img v-if="member.avatar_url" :src="member.avatar_url" :alt="member.name" class="w-full h-full object-cover" loading="lazy">
                                <div v-else class="w-full h-full bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-extrabold text-lg">
                                    {{ getInitials(member.name) }}
                                </div>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold" style="color: var(--text-heading);">{{ member.name }}</h3>
                                <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                        {{ member.job_title || member.role_title || 'Profissional do Time' }}
                                    </span>
                                    <span :class="['inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full border', member.role_badge_color]">
                                        <i :class="[member.role_icon, 'text-[9px]']"></i>
                                        {{ member.role_name }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20" title="Taxa de comissão">
                                        <i class="fa-solid fa-percent text-[9px]"></i>
                                        Comissão: {{ formatCurrency(member.commission_rate) }}%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span v-if="member.is_active" class="badge badge-confirmed">Ativo</span>
                            <span v-else class="badge badge-cancelled">Inativo</span>
                        </div>
                    </div>

                    <div class="space-y-2.5 my-3 text-xs">
                        <div v-if="member.subdomain || member.custom_domain" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 truncate">
                                <i class="fa-solid fa-globe text-indigo-500 dark:text-indigo-400 shrink-0"></i>
                                <span class="font-mono text-[11px] truncate">
                                    {{ getMemberDomainDisplay(member) }}
                                </span>
                            </div>
                            <a :href="route('booking.shortcut', { subdomain: member.subdomain || member.custom_domain })" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline shrink-0 text-[11px] font-bold" title="Abrir página de agendamento do profissional">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>

                        <div v-if="member.phone || member.email" class="flex flex-col gap-1 text-[11px] opacity-75">
                            <div v-if="member.phone" class="flex items-center gap-1.5">
                                <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                                <span>{{ member.phone }}</span>
                            </div>
                            <div v-if="member.email" class="flex items-center gap-1.5">
                                <i class="fa-regular fa-envelope text-indigo-400"></i>
                                <span class="truncate">{{ member.email }}</span>
                            </div>
                        </div>

                        <div class="pt-2 border-t" style="border-color: var(--border);">
                            <span class="text-[11px] font-bold uppercase tracking-wider opacity-60 block mb-1.5">Serviços Atendidos:</span>
                            <div class="flex flex-wrap gap-1">
                                <template v-if="!member.services || member.services.length === 0">
                                    <span class="text-[11px] px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-semibold border border-indigo-500/20">
                                        Todos os Serviços
                                    </span>
                                </template>
                                <template v-else>
                                    <template v-if="getMemberServices(member) && getMemberServices(member).length > 0">
                                        <span v-for="svc in getMemberServices(member)" :key="svc.id" class="text-[11px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-medium">
                                            {{ svc.name }}
                                        </span>
                                    </template>
                                    <span v-else class="text-[11px] opacity-60">Nenhum serviço vinculado</span>
                                </template>
                            </div>
                        </div>
                        </div>

                        <div class="pt-3 border-t mt-3 space-y-2.5" style="border-color: var(--border);">
                        <!-- Row 1: Primary Actions -->
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-if="canManageTeam"
                                type="button"
                                @click="openEditModal(member)"
                                class="btn btn-outline py-2 px-3 text-xs rounded-xl flex items-center justify-center gap-1.5 hover:border-indigo-500 hover:text-indigo-600"
                                title="Editar Profissional"
                            >
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                <span>Editar</span>
                            </button>
                            <button v-else type="button" disabled class="btn btn-outline py-2 px-3 text-xs rounded-xl opacity-50 cursor-not-allowed flex items-center justify-center gap-1.5" title="Sem permissão">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                <span>Editar</span>
                            </button>

                            <button
                                v-if="canDeleteTeam"
                                type="button"
                                @click="openDeleteModal(member)"
                                class="btn btn-danger py-2 px-3 text-xs rounded-xl flex items-center justify-center gap-1.5"
                                title="Remover Profissional"
                            >
                                <i class="fa-solid fa-trash-can text-xs"></i>
                                <span>Remover</span>
                            </button>
                            <button v-else type="button" disabled class="btn btn-danger py-2 px-3 text-xs rounded-xl opacity-50 cursor-not-allowed flex items-center justify-center gap-1.5" title="Apenas administradores podem remover profissionais">
                                <i class="fa-solid fa-lock text-xs"></i>
                                <span>Remover</span>
                            </button>
                        </div>

                        <!-- Row 2: Secondary Config Actions -->
                        <div class="flex items-center justify-between gap-2 pt-1">
                            <button
                                v-if="canManageTeam"
                                type="button"
                                @click="toggleStatus(member)"
                                class="btn btn-outline py-1.5 px-2.5 text-[11px] rounded-xl flex items-center gap-1"
                                :title="member.is_active ? 'Desativar Profissional' : 'Ativar Profissional'"
                            >
                                <i :class="['fa-solid text-xs', member.is_active ? 'fa-toggle-on text-emerald-500' : 'fa-toggle-off text-slate-400']"></i>
                                <span>{{ member.is_active ? 'Ativo' : 'Inativo' }}</span>
                            </button>
                            <button v-else type="button" disabled class="btn btn-outline py-1.5 px-2.5 text-[11px] rounded-xl opacity-50 cursor-not-allowed flex items-center gap-1" title="Apenas administradores e gerentes podem alterar status">
                                <i :class="['fa-solid text-xs', member.is_active ? 'fa-toggle-on text-emerald-500' : 'fa-toggle-off text-slate-400']"></i>
                                <span>{{ member.is_active ? 'Ativo' : 'Inativo' }}</span>
                            </button>

                            <template v-if="member.email">
                                <button
                                    v-if="canManageTeam"
                                    type="button"
                                    @click="openResetModal(member)"
                                    class="btn btn-outline py-1.5 px-2.5 text-[11px] rounded-xl hover:border-amber-500 hover:text-amber-600 flex items-center gap-1"
                                    title="Redefinir Senha"
                                >
                                    <i class="fa-solid fa-key text-[10px]"></i>
                                    <span>Senha</span>
                                </button>
                                <button v-else type="button" disabled class="btn btn-outline py-1.5 px-2.5 text-[11px] rounded-xl opacity-50 cursor-not-allowed flex items-center gap-1" title="Sem permissão">
                                    <i class="fa-solid fa-key text-[10px]"></i>
                                    <span>Senha</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <Teleport to="body">
                <div v-if="showCreateModal" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click="handleBackdropClickCreate">
                    <div class="liquid-glass-card w-full max-w-xl p-6 sm:p-7 space-y-4 relative" @click.stop>
                        <div class="flex items-center justify-between pb-3 border-b" style="border-color: var(--border);">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shadow-md shadow-indigo-500/20">
                                    <i class="fa-solid fa-user-plus"></i>
                                </div>
                                <div>
                                    <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">Novo Profissional</h3>
                                    <p class="text-xs opacity-60">Adicione um membro ao time do seu estabelecimento</p>
                                </div>
                            </div>
                            <button type="button" @click="closeCreateModal" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <!-- Form Tabs -->
                        <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800/80">
                            <button
                                type="button"
                                @click="createActiveTab = 'basic'"
                                :class="[
                                    'flex-1 py-1.5 px-3 text-[11px] font-bold rounded-lg transition-all flex items-center justify-center gap-1.5',
                                    createActiveTab === 'basic' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'
                                ]"
                            >
                                <i class="fa-solid fa-user-gear"></i>
                                <span>Básico</span>
                            </button>
                            <button
                                type="button"
                                @click="createActiveTab = 'services'"
                                :class="[
                                    'flex-1 py-1.5 px-3 text-[11px] font-bold rounded-lg transition-all flex items-center justify-center gap-1.5',
                                    createActiveTab === 'services' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'
                                ]"
                            >
                                <i class="fa-solid fa-scissors"></i>
                                <span>Serviços e Comissões</span>
                            </button>
                            <button
                                type="button"
                                @click="createActiveTab = 'link'"
                                :class="[
                                    'flex-1 py-1.5 px-3 text-[11px] font-bold rounded-lg transition-all flex items-center justify-center gap-1.5',
                                    createActiveTab === 'link' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'
                                ]"
                            >
                                <i class="fa-solid fa-globe"></i>
                                <span>Link e Bio</span>
                            </button>
                        </div>

                        <form @submit.prevent="submitCreate" class="space-y-4">
                            <!-- Tab: Basic Info -->
                            <div v-show="createActiveTab === 'basic'" class="space-y-4">
                                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800/80 flex flex-col sm:flex-row items-center gap-4">
                                    <div class="w-16 h-16 rounded-2xl overflow-hidden bg-slate-200 dark:bg-slate-700 border border-indigo-500/30 flex items-center justify-center shrink-0 relative shadow-inner">
                                        <img v-if="createAvatarPreview" :src="createAvatarPreview" alt="Preview" class="w-full h-full object-cover">
                                        <div v-else class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                                            <i class="fa-solid fa-camera text-lg mb-0.5"></i>
                                            <span class="text-[8px] uppercase tracking-wider font-bold">Foto</span>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full space-y-1.5">
                                        <div>
                                            <label class="form-label text-[10px] font-bold block mb-0.5" for="create_member_avatar_file">
                                                Upload de Foto / Avatar
                                            </label>
                                            <input type="file" id="create_member_avatar_file" accept="image/*" class="form-control text-xs rounded-xl" @change="previewCreateAvatar">
                                        </div>
                                        <div>
                                            <input type="url" v-model="createForm.avatar_url" @input="previewCreateAvatarUrl" class="form-control text-[11px] rounded-xl" placeholder="Ou cole a URL da foto (https://...)"/>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold" for="create_member_name">Nome Completo *</label>
                                        <input type="text" id="create_member_name" v-model="createForm.name" class="form-control text-xs rounded-xl" placeholder="Ex: Carlos Silva" required>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold" for="create_member_job_title">Cargo / Especialidade *</label>
                                        <input type="text" id="create_member_job_title" v-model="createForm.job_title" class="form-control text-xs rounded-xl" placeholder="Ex: Barbeiro Sênior, Colorista" required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold" for="create_member_role_id">Cargo no Sistema *</label>
                                        <select id="create_member_role_id" v-model="createForm.role_id" class="form-control text-xs rounded-xl">
                                            <option value="professional">Profissional (Agenda Própria)</option>
                                            <option value="manager">Gerente (Gestão Operacional)</option>
                                            <option value="receptionist">Atendente (Atendimento Geral)</option>
                                            <option value="admin">Administrador (Acesso Total)</option>
                                        </select>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold" for="create_member_phone">WhatsApp / Telefone</label>
                                        <input type="tel" id="create_member_phone" v-model="createForm.phone" class="form-control text-xs rounded-xl" placeholder="(11) 99999-8888">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold" for="create_member_email">E-mail *</label>
                                        <input type="email" id="create_member_email" v-model="createForm.email" class="form-control text-xs rounded-xl" placeholder="carlos@exemplo.com" required>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold" for="create_member_password">Senha Inicial *</label>
                                        <input type="password" id="create_member_password" v-model="createForm.password" class="form-control text-xs rounded-xl" placeholder="Digite a senha inicial" required>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold">
                                        <input type="checkbox" :true-value="true" :false-value="false" v-model="createForm.is_active" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                        <span>Profissional Ativo para Receber Agendamentos</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Tab: Services & Commissions -->
                            <div v-show="createActiveTab === 'services'" class="space-y-4">
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800/80 space-y-3.5">
                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold block mb-1" for="create_member_commission_rate">Comissão Padrão (%)</label>
                                        <input type="number" step="0.01" min="0" max="100" id="create_member_commission_rate" v-model.number="createForm.commission_rate" class="form-control text-xs rounded-xl" placeholder="Ex: 30">
                                        <span class="text-[10px] opacity-60 block mt-1">Percentual aplicado a todos os serviços contratados.</span>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="form-label text-xs font-bold block">Comissões Personalizadas por Serviço (Opcional)</label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-32 overflow-y-auto p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 custom-scrollbar">
                                            <div v-for="svc in services" :key="svc.id" class="flex items-center justify-between gap-2 text-xs">
                                                <span class="truncate font-medium flex-1 text-slate-700 dark:text-slate-300">{{ svc.name }}</span>
                                                <div class="relative w-20 shrink-0">
                                                    <input type="number" step="0.01" min="0" max="100" v-model.number="createForm.service_commissions[svc.id]" class="form-control text-xs rounded-lg py-1 px-2 pr-5 text-right" placeholder="30">
                                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] opacity-50">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label text-xs font-bold mb-1.5 block">Serviços que este profissional realiza:</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-36 overflow-y-auto p-3 rounded-xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800/80 custom-scrollbar">
                                        <label v-for="svc in services" :key="svc.id" class="inline-flex items-center gap-2 text-xs font-medium cursor-pointer">
                                            <input type="checkbox" :value="svc.id" v-model="createForm.services" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                            <span class="truncate">{{ svc.name }} (R$ {{ formatCurrency(svc.price) }})</span>
                                        </label>
                                    </div>
                                    <span class="text-[11px] opacity-60 block mt-1">Deixe vazio para atender todos os serviços do estabelecimento por padrão.</span>
                                </div>
                            </div>

                            <!-- Tab: Public Link & Bio -->
                            <div v-show="createActiveTab === 'link'" class="space-y-4">
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800/80 space-y-3.5">
                                    <label class="form-label text-xs font-bold block mb-1">Página de Agendamento Online</label>
                                    <div class="flex items-center gap-4 text-xs font-semibold">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="radio" :value="'subdomain'" v-model="createDomainType" @change="toggleCreateDomainType('subdomain')" class="text-indigo-600 focus:ring-indigo-500">
                                            <span>Subdomínio Agendae</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="radio" :value="'custom'" v-model="createDomainType" @change="toggleCreateDomainType('custom')" class="text-indigo-600 focus:ring-indigo-500">
                                            <span>Domínio Customizado</span>
                                        </label>
                                    </div>

                                    <div v-if="createDomainType === 'subdomain'" class="form-group mb-0">
                                        <label class="form-label text-[11px] opacity-70" for="create_member_subdomain">Nome do Subdomínio</label>
                                        <div class="relative">
                                            <input type="text" id="create_member_subdomain" v-model="createForm.subdomain" class="form-control text-xs rounded-xl pr-24" placeholder="carlos">
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] opacity-50 font-mono">.{{ appDomain }}</span>
                                        </div>
                                    </div>

                                    <div v-if="createDomainType === 'custom'" class="form-group mb-0">
                                        <label class="form-label text-[11px] opacity-70" for="create_member_custom_domain">Domínio Próprio Completo</label>
                                        <input type="text" id="create_member_custom_domain" v-model="createForm.custom_domain" class="form-control text-xs rounded-xl" placeholder="carlos.meudominio.com.br">
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label text-xs font-bold mb-1 block" for="create_member_bio">Mini Bio / Descrição Curta</label>
                                    <textarea id="create_member_bio" v-model="createForm.bio" rows="3" class="form-control text-xs rounded-xl" placeholder="Apresente as especialidades do profissional aos clientes na página pública de agendamentos."></textarea>
                                </div>
                            </div>

                            <div class="pt-3.5 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                                <button type="button" @click="closeCreateModal" class="btn btn-outline py-2 px-4 text-xs font-bold rounded-xl">Cancelar</button>
                                <button type="submit" class="btn btn-primary py-2 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30" :disabled="createForm.processing">
                                    <i class="fa-solid fa-check text-xs"></i>
                                    <span>{{ createForm.processing ? 'Salvando...' : 'Cadastrar Profissional' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <!-- Edit Modal -->
            <Teleport to="body">
                <div v-if="showEditModal" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click="handleBackdropClickEdit">
                    <div class="liquid-glass-card w-full max-w-xl p-6 sm:p-7 space-y-4 relative" @click.stop>
                        <div class="flex items-center justify-between pb-3 border-b" style="border-color: var(--border);">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 flex items-center justify-center text-lg shadow-md shadow-amber-500/20">
                                    <i class="fa-solid fa-user-pen"></i>
                                </div>
                                <div>
                                    <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">Editar Profissional</h3>
                                    <p class="text-xs opacity-60">Altere dados, cargo, comissões, link ou serviços atendidos</p>
                                </div>
                            </div>
                            <button type="button" @click="closeEditModal" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <!-- Form Tabs -->
                        <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800/80">
                            <button
                                type="button"
                                @click="editActiveTab = 'basic'"
                                :class="[
                                    'flex-1 py-1.5 px-3 text-[11px] font-bold rounded-lg transition-all flex items-center justify-center gap-1.5',
                                    editActiveTab === 'basic' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'
                                ]"
                            >
                                <i class="fa-solid fa-user-gear"></i>
                                <span>Básico</span>
                            </button>
                            <button
                                type="button"
                                @click="editActiveTab = 'services'"
                                :class="[
                                    'flex-1 py-1.5 px-3 text-[11px] font-bold rounded-lg transition-all flex items-center justify-center gap-1.5',
                                    editActiveTab === 'services' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'
                                ]"
                            >
                                <i class="fa-solid fa-scissors"></i>
                                <span>Serviços e Comissões</span>
                            </button>
                            <button
                                type="button"
                                @click="editActiveTab = 'link'"
                                :class="[
                                    'flex-1 py-1.5 px-3 text-[11px] font-bold rounded-lg transition-all flex items-center justify-center gap-1.5',
                                    editActiveTab === 'link' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'
                                ]"
                            >
                                <i class="fa-solid fa-globe"></i>
                                <span>Link e Bio</span>
                            </button>
                        </div>

                        <form @submit.prevent="submitEdit" class="space-y-4">
                            <!-- Tab: Basic Info -->
                            <div v-show="editActiveTab === 'basic'" class="space-y-4">
                                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800/80 flex flex-col sm:flex-row items-center gap-4">
                                    <div class="w-16 h-16 rounded-2xl overflow-hidden bg-slate-200 dark:bg-slate-700 border border-amber-500/30 flex items-center justify-center shrink-0 relative shadow-inner">
                                        <img v-if="editAvatarPreview" :src="editAvatarPreview" alt="Preview" class="w-full h-full object-cover">
                                        <div v-else class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500">
                                            <i class="fa-solid fa-camera text-lg mb-0.5"></i>
                                            <span class="text-[8px] uppercase tracking-wider font-bold">Foto</span>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full space-y-1.5">
                                        <div>
                                            <label class="form-label text-[10px] font-bold block mb-0.5" for="edit_member_avatar_file">
                                                Alterar Foto / Avatar
                                            </label>
                                            <input type="file" id="edit_member_avatar_file" accept="image/*" class="form-control text-xs rounded-xl" @change="previewEditAvatar">
                                        </div>
                                        <div>
                                            <input type="url" v-model="editForm.avatar_url" @input="previewEditAvatarUrl" class="form-control text-[11px] rounded-xl" placeholder="Ou altere a URL da foto (https://...)"/>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold" for="edit_member_name">Nome Completo *</label>
                                        <input type="text" id="edit_member_name" v-model="editForm.name" class="form-control text-xs rounded-xl" required>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold" for="edit_member_job_title">Cargo / Especialidade *</label>
                                        <input type="text" id="edit_member_job_title" v-model="editForm.job_title" class="form-control text-xs rounded-xl" required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold" for="edit_member_role_id">Cargo no Sistema *</label>
                                        <select id="edit_member_role_id" v-model="editForm.role_id" class="form-control text-xs rounded-xl">
                                            <option value="professional">Profissional (Agenda Própria)</option>
                                            <option value="manager">Gerente (Gestão Operacional)</option>
                                            <option value="receptionist">Atendente (Atendimento Geral)</option>
                                            <option value="admin">Administrador (Acesso Total)</option>
                                        </select>
                                    </div>

                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold" for="edit_member_phone">WhatsApp / Telefone</label>
                                        <input type="tel" id="edit_member_phone" v-model="editForm.phone" class="form-control text-xs rounded-xl">
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label text-xs font-bold" for="edit_member_email">E-mail (opcional)</label>
                                    <input type="email" id="edit_member_email" v-model="editForm.email" class="form-control text-xs rounded-xl">
                                </div>

                                <div class="form-group mb-0">
                                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold">
                                        <input type="checkbox" :true-value="true" :false-value="false" v-model="editForm.is_active" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                        <span>Profissional Ativo</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Tab: Services & Commissions -->
                            <div v-show="editActiveTab === 'services'" class="space-y-4">
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800/80 space-y-3.5">
                                    <div class="form-group mb-0">
                                        <label class="form-label text-xs font-bold block mb-1" for="edit_member_commission_rate">Comissão Padrão (%)</label>
                                        <input type="number" step="0.01" min="0" max="100" id="edit_member_commission_rate" v-model.number="editForm.commission_rate" class="form-control text-xs rounded-xl" placeholder="Ex: 30">
                                        <span class="text-[10px] opacity-60 block mt-1">Percentual aplicado a todos os serviços.</span>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="form-label text-xs font-bold block">Comissões Personalizadas por Serviço (Opcional)</label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-32 overflow-y-auto p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 custom-scrollbar">
                                            <div v-for="svc in services" :key="svc.id" class="flex items-center justify-between gap-2 text-xs">
                                                <span class="truncate font-medium flex-1 text-slate-700 dark:text-slate-300">{{ svc.name }}</span>
                                                <div class="relative w-20 shrink-0">
                                                    <input type="number" step="0.01" min="0" max="100" v-model.number="editForm.service_commissions[svc.id]" class="form-control text-xs rounded-lg py-1 px-2 pr-5 text-right" placeholder="30">
                                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] opacity-50">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label text-xs font-bold mb-1.5 block">Serviços que este profissional realiza:</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-36 overflow-y-auto p-3 rounded-xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800/80 custom-scrollbar">
                                        <label v-for="svc in services" :key="svc.id" class="inline-flex items-center gap-2 text-xs font-medium cursor-pointer">
                                            <input type="checkbox" :value="svc.id" v-model="editForm.services" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                                            <span class="truncate">{{ svc.name }} (R$ {{ formatCurrency(svc.price) }})</span>
                                        </label>
                                    </div>
                                    <span class="text-[11px] opacity-60 block mt-1">Deixe vazio para atender todos os serviços do estabelecimento por padrão.</span>
                                </div>
                            </div>

                            <!-- Tab: Public Link & Bio -->
                            <div v-show="editActiveTab === 'link'" class="space-y-4">
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800/80 space-y-3.5">
                                    <label class="form-label text-xs font-bold block mb-1">Página de Agendamento Online</label>
                                    <div class="flex items-center gap-4 text-xs font-semibold">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="radio" :value="'subdomain'" v-model="editDomainType" @change="toggleEditDomainType('subdomain')" class="text-indigo-600 focus:ring-indigo-500">
                                            <span>Subdomínio Agendae</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="radio" :value="'custom'" v-model="editDomainType" @change="toggleEditDomainType('custom')" class="text-indigo-600 focus:ring-indigo-500">
                                            <span>Domínio Customizado</span>
                                        </label>
                                    </div>

                                    <div v-if="editDomainType === 'subdomain'" class="form-group mb-0">
                                        <label class="form-label text-[11px] opacity-70" for="edit_member_subdomain">Nome do Subdomínio</label>
                                        <div class="relative">
                                            <input type="text" id="edit_member_subdomain" v-model="editForm.subdomain" class="form-control text-xs rounded-xl pr-24">
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] opacity-50 font-mono">.{{ appDomain }}</span>
                                        </div>
                                    </div>

                                    <div v-if="editDomainType === 'custom'" class="form-group mb-0">
                                        <label class="form-label text-[11px] opacity-70" for="edit_member_custom_domain">Domínio Próprio Completo</label>
                                        <input type="text" id="edit_member_custom_domain" v-model="editForm.custom_domain" class="form-control text-xs rounded-xl" placeholder="carlos.meudominio.com.br">
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label text-xs font-bold mb-1 block" for="edit_member_bio">Mini Bio / Descrição Curta</label>
                                    <textarea id="edit_member_bio" v-model="editForm.bio" rows="3" class="form-control text-xs rounded-xl"></textarea>
                                </div>
                            </div>

                            <div class="pt-3.5 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                                <button type="button" @click="closeEditModal" class="btn btn-outline py-2 px-4 text-xs font-bold rounded-xl">Cancelar</button>
                                <button type="submit" class="btn btn-primary py-2 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30" :disabled="editForm.processing">
                                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                                    <span>{{ editForm.processing ? 'Salvando...' : 'Salvar Alterações' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <!-- Delete Modal -->
            <Teleport to="body">
                <div v-if="showDeleteModal" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click="handleBackdropClickDelete">
                    <div class="liquid-glass-card w-full max-w-md p-6 space-y-4 relative" @click.stop>
                        <div class="flex items-center gap-3 pb-3 border-b" style="border-color: var(--border);">
                            <div class="w-10 h-10 rounded-2xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-rose-600 dark:text-rose-400">Remover Profissional</h3>
                                <p class="text-xs opacity-60">Excluir membro da equipe</p>
                            </div>
                        </div>

                        <p class="text-xs sm:text-sm" style="color: var(--text);">
                            Tem certeza de que deseja remover <strong class="font-bold text-indigo-600 dark:text-indigo-400">{{ deletingMember?.name }}</strong> do time? Os agendamentos anteriores serão preservados.
                        </p>

                        <form @submit.prevent="submitDelete" class="pt-3 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                            <button type="button" @click="closeDeleteModal" class="btn btn-outline py-2 px-3.5 text-xs font-bold rounded-xl">Cancelar</button>
                            <button type="submit" class="btn btn-danger py-2 px-4 text-xs font-bold rounded-xl" :disabled="deleteForm.processing">
                                <i class="fa-solid fa-trash-can text-xs mr-1"></i>
                                <span>{{ deleteForm.processing ? 'Removendo...' : 'Sim, Remover' }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </Teleport>

            <!-- Reset Password Modal -->
            <Teleport to="body">
                <div v-if="showResetModal" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click="handleBackdropClickReset">
                    <div class="liquid-glass-card w-full max-w-md p-6 space-y-4 relative" @click.stop>
                        <div class="flex items-center gap-3 pb-3 border-b" style="border-color: var(--border);">
                            <div class="w-10 h-10 rounded-2xl bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 flex items-center justify-center text-lg">
                                <i class="fa-solid fa-key"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-amber-600 dark:text-amber-400">Redefinir Senha</h3>
                                <p class="text-xs opacity-60">Alterar senha do profissional</p>
                            </div>
                        </div>

                        <p class="text-xs sm:text-sm" style="color: var(--text);">
                            Tem certeza de que deseja redefinir a senha de <strong class="font-bold text-indigo-600 dark:text-indigo-400">{{ resettingMember?.name }}</strong>? A senha temporária será definida como <code class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded font-mono font-bold text-slate-800 dark:text-slate-200">agendae123</code> e ele será obrigado a alterá-la no próximo acesso.
                        </p>

                        <form @submit.prevent="submitReset" class="pt-3 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                            <button type="button" @click="closeResetModal" class="btn btn-outline py-2 px-3.5 text-xs font-bold rounded-xl">Cancelar</button>
                            <button type="submit" class="btn btn-primary bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 py-2 px-4 text-xs font-bold rounded-xl shadow-lg shadow-amber-500/20 text-white border-0" :disabled="resetForm.processing">
                                <i class="fa-solid fa-key text-xs mr-1"></i>
                                <span>{{ resetForm.processing ? 'Processando...' : 'Sim, Redefinir' }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </Teleport>

        </div>
    </AdminLayout>
</template>

<style scoped>
.glass-card-3d {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.04), inset 0 1px 1px 0 rgba(255, 255, 255, 0.8);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
html.dark .glass-card-3d {
    background: rgba(18, 24, 36, 0.6);
    border: 1px solid rgba(6, 182, 212, 0.15);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2), inset 0 1px 0 0 rgba(255, 255, 255, 0.05);
}
.glass-card-3d:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow: 0 20px 40px 0 rgba(99, 102, 241, 0.08);
    border-color: rgba(99, 102, 241, 0.3);
}
html.dark .glass-card-3d:hover {
    box-shadow: 0 20px 40px 0 rgba(0, 0, 0, 0.4), 0 0 15px rgba(6, 182, 212, 0.1);
    border-color: rgba(6, 182, 212, 0.4);
}
.btn-danger {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
}
.btn-danger:hover {
    opacity: 0.98;
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 24px rgba(220, 38, 38, 0.5);
}
.btn-danger:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
</style>
