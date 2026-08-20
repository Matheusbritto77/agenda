<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TeamMemberCard from './Components/TeamMemberCard.vue';
import TeamMemberModal from './Components/TeamMemberModal.vue';
import TeamResetPasswordModal from './Components/TeamResetPasswordModal.vue';
import TeamDeleteModal from './Components/TeamDeleteModal.vue';

const page = usePage();

const props = defineProps({
    teamMembers: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Array,
        default: () => [],
    },
    auth: {
        type: Object,
        default: () => ({}),
    },
    appDomain: {
        type: String,
        default: 'agendae.app',
    },
});

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    const userPerms = page.props.auth?.permissions || [];
    return userPerms.includes(permission);
};

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showResetModal = ref(false);
const editingMember = ref(null);
const deletingMember = ref(null);
const resettingMember = ref(null);

const createAvatarPreview = ref('');
const editAvatarPreview = ref('');

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
    createAvatarPreview.value = '';
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
    editAvatarPreview.value = member.avatar_url || '';
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

const handleCreateFileChange = (file) => {
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => { createAvatarPreview.value = e.target.result; };
        reader.readAsDataURL(file);
        createForm.avatar = file;
    }
};

const handleEditFileChange = (file) => {
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => { editAvatarPreview.value = e.target.result; };
        reader.readAsDataURL(file);
        editForm.avatar = file;
    }
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
        <Head title="Equipe & Profissionais - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Equipe de Atendimento</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Profissionais, permissões, comissões e páginas públicas individuais</p>
        </template>

        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight" style="color: var(--text-heading);">Membros da Equipe</h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Gerencie os profissionais que atendem no seu negócio.</p>
                </div>
                <button
                    v-if="hasPermission('team.create')"
                    type="button"
                    @click="openCreateModal"
                    class="btn btn-primary self-start sm:self-auto py-2.5 px-4"
                >
                    <i class="fa-solid fa-user-plus text-xs"></i>
                    <span>Novo Profissional</span>
                </button>
            </div>

            <!-- Team Members Cards -->
            <TeamMemberCard
                :team-members="teamMembers"
                :services="services"
                :can-manage="hasPermission('team.edit')"
                :can-delete="hasPermission('team.delete')"
                :app-domain="appDomain"
                @open-create="openCreateModal"
                @open-edit="openEditModal"
                @open-delete="openDeleteModal"
                @open-reset="openResetModal"
                @toggle-status="toggleStatus"
            />
        </div>

        <!-- Create Modal -->
        <TeamMemberModal
            :show="showCreateModal"
            :is-editing="false"
            :form="createForm"
            :services="services"
            :avatar-preview="createAvatarPreview"
            :app-domain="appDomain"
            @close="closeCreateModal"
            @submit="submitCreate"
            @file-change="handleCreateFileChange"
        />

        <!-- Edit Modal -->
        <TeamMemberModal
            :show="showEditModal"
            :is-editing="true"
            :form="editForm"
            :services="services"
            :avatar-preview="editAvatarPreview"
            :app-domain="appDomain"
            @close="closeEditModal"
            @submit="submitEdit"
            @file-change="handleEditFileChange"
        />

        <!-- Reset Password Modal -->
        <TeamResetPasswordModal
            :show="showResetModal"
            :member="resettingMember"
            :is-resetting="resetForm.processing"
            @close="closeResetModal"
            @confirm="submitReset"
        />

        <!-- Delete Modal -->
        <TeamDeleteModal
            :show="showDeleteModal"
            :member="deletingMember"
            :is-deleting="deleteForm.processing"
            @close="closeDeleteModal"
            @confirm="submitDelete"
        />
    </AdminLayout>
</template>
