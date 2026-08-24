<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    subUsers: {
        type: Array,
        default: () => []
    },
    teamMembers: {
        type: Array,
        default: () => []
    },
    roles: {
        type: Object,
        default: () => ({})
    },
    rolePermissions: {
        type: Object,
        default: () => ({})
    },
    permissionModules: {
        type: Object,
        default: () => ({})
    },
});

const activeTab = ref('roles'); // Default to roles tab since user is focused on roles & permissions
const selectedRole = ref('admin');
const selectedRolePermissions = ref([]);
const showCustomRoleModal = ref(false);
const showDeleteConfirmModal = ref(false);
const roleToDelete = ref(null);

const defaultRoleKeys = ['admin', 'manager', 'professional', 'receptionist'];
const isCustomRole = (key) => !defaultRoleKeys.includes(key);

const roleEntries = computed(() => Object.entries(props.roles || {}));
const defaultRoleId = computed(() => roleEntries.value[0]?.[0] || 'admin');
const roleTitles = computed(() => Object.fromEntries(roleEntries.value.map(([key, role]) => [key, role.name])));

const subUserEmailsForStats = computed(() => props.subUsers?.map(u => u.email).filter(Boolean) || []);
const teamMembersNotInSubUsers = computed(() => props.teamMembers.filter(m => !m.email || !subUserEmailsForStats.value.includes(m.email)));
const adminCount = computed(() => 1 + props.teamMembers.filter(m => m.role_id === 'admin').length);
const totalAccounts = computed(() => 1 + (props.subUsers?.length || 0) + teamMembersNotInSubUsers.value.length);
const roleCount = computed(() => roleEntries.value.length);

const permissionsForm = useForm({
    role: 'admin',
    permissions: [],
});

// Color presets for custom roles
const colorPresets = [
    { label: 'Índigo', badge_color: 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border-indigo-500/30', bg: 'bg-indigo-500', hex: '#6366f1' },
    { label: 'Ciano', badge_color: 'bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border-cyan-500/30', bg: 'bg-cyan-500', hex: '#06b6d4' },
    { label: 'Esmeralda', badge_color: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30', bg: 'bg-emerald-500', hex: '#10b981' },
    { label: 'Âmbar', badge_color: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30', bg: 'bg-amber-500', hex: '#f59e0b' },
    { label: 'Rosa', badge_color: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30', bg: 'bg-rose-500', hex: '#f43f5e' },
    { label: 'Púrpura', badge_color: 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border-purple-500/30', bg: 'bg-purple-500', hex: '#a855f7' },
    { label: 'Laranja', badge_color: 'bg-orange-500/15 text-orange-600 dark:text-orange-400 border-orange-500/30', bg: 'bg-orange-500', hex: '#f97316' },
    { label: 'Slate', badge_color: 'bg-slate-500/15 text-slate-700 dark:text-slate-300 border-slate-500/30', bg: 'bg-slate-500', hex: '#64748b' },
];

// Icon presets for custom roles
const iconPresets = [
    { label: 'Etiqueta', icon: 'fa-solid fa-user-tag' },
    { label: 'Coroa', icon: 'fa-solid fa-crown' },
    { label: 'Estrela', icon: 'fa-solid fa-star' },
    { label: 'Escudo', icon: 'fa-solid fa-shield-halved' },
    { label: 'Gravata', icon: 'fa-solid fa-user-tie' },
    { label: 'Checado', icon: 'fa-solid fa-user-check' },
    { label: 'Engrenagem', icon: 'fa-solid fa-user-gear' },
    { label: 'Maleta', icon: 'fa-solid fa-briefcase' },
    { label: 'Diamante', icon: 'fa-solid fa-gem' },
    { label: 'Raio', icon: 'fa-solid fa-bolt' },
    { label: 'Tesoura', icon: 'fa-solid fa-scissors' },
    { label: 'Spa', icon: 'fa-solid fa-spa' },
    { label: 'Headset', icon: 'fa-solid fa-headset' },
    { label: 'Varinha', icon: 'fa-solid fa-wand-magic-sparkles' },
    { label: 'Medalha', icon: 'fa-solid fa-award' },
    { label: 'Coração', icon: 'fa-solid fa-heart' },
];

const customRoleForm = useForm({
    name: '',
    role_id: '',
    role_id_manual: false,
    description: '',
    base_role_id: 'professional',
    icon: 'fa-solid fa-user-tag',
    badge_color: 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border-purple-500/30',
});

// Auto slug generation from role name
watch(() => customRoleForm.name, (newName) => {
    if (!customRoleForm.role_id_manual) {
        customRoleForm.role_id = (newName || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
});

const roleUpdateForms = ref({});

const getRoleUpdateForm = (memberId) => {
    if (!roleUpdateForms.value[memberId]) {
        roleUpdateForms.value[memberId] = useForm({
            role_id: defaultRoleId.value,
        });
    }
    return roleUpdateForms.value[memberId];
};

const switchTab = (tab) => {
    activeTab.value = tab;
};

const selectRole = (roleKey) => {
    selectedRole.value = roleKey;
    permissionsForm.role = roleKey;
    const activePerms = props.rolePermissions?.[roleKey] || [];
    selectedRolePermissions.value = [...activePerms];
    permissionsForm.permissions = [...activePerms];
};

const toggleAllPermissions = (check) => {
    const allPerms = [];
    for (const modKey in props.permissionModules) {
        const module = props.permissionModules[modKey];
        for (const permKey in module.permissions) {
            allPerms.push(permKey);
        }
    }
    permissionsForm.permissions = check ? allPerms : [];
    selectedRolePermissions.value = check ? allPerms : [];
};

const togglePermission = (permKey) => {
    const idx = permissionsForm.permissions.indexOf(permKey);
    if (idx >= 0) {
        permissionsForm.permissions.splice(idx, 1);
    } else {
        permissionsForm.permissions.push(permKey);
    }
    selectedRolePermissions.value = [...permissionsForm.permissions];
};

const isModuleFullySelected = (moduleKey) => {
    const mod = props.permissionModules[moduleKey];
    if (!mod || !mod.permissions) return false;
    const permKeys = Object.keys(mod.permissions);
    return permKeys.length > 0 && permKeys.every(k => permissionsForm.permissions.includes(k));
};

const toggleModulePermissions = (moduleKey) => {
    const mod = props.permissionModules[moduleKey];
    if (!mod || !mod.permissions) return;
    const permKeys = Object.keys(mod.permissions);
    const allSelected = isModuleFullySelected(moduleKey);

    if (allSelected) {
        permissionsForm.permissions = permissionsForm.permissions.filter(k => !permKeys.includes(k));
    } else {
        const currentSet = new Set(permissionsForm.permissions);
        permKeys.forEach(k => currentSet.add(k));
        permissionsForm.permissions = Array.from(currentSet);
    }
    selectedRolePermissions.value = [...permissionsForm.permissions];
};

const hasPermission = (permKey) => {
    return permissionsForm.permissions.includes(permKey);
};

const submitPermissions = () => {
    permissionsForm.post(route('admin.roles.permissions.update'), {
        preserveScroll: true,
    });
};

const openCreateRoleModal = () => {
    customRoleForm.reset();
    customRoleForm.base_role_id = 'professional';
    customRoleForm.icon = 'fa-solid fa-user-tag';
    customRoleForm.badge_color = 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border-purple-500/30';
    customRoleForm.role_id_manual = false;
    showCustomRoleModal.value = true;
};

const submitCustomRole = () => {
    customRoleForm.post(route('admin.roles.custom.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCustomRoleModal.value = false;
            const newRoleId = customRoleForm.role_id;
            customRoleForm.reset();
            router.reload({
                preserveScroll: true,
                onSuccess: () => {
                    if (newRoleId && props.roles[newRoleId]) {
                        selectRole(newRoleId);
                    }
                }
            });
        },
    });
};

const confirmDeleteRole = (roleKey, roleObj) => {
    roleToDelete.value = { key: roleKey, ...roleObj };
    showDeleteConfirmModal.value = true;
};

const deleteRole = () => {
    if (!roleToDelete.value) return;
    router.delete(route('admin.roles.custom.destroy', roleToDelete.value.key), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmModal.value = false;
            roleToDelete.value = null;
            selectRole('admin');
        },
    });
};

const submitRoleChange = (memberId) => {
    const form = getRoleUpdateForm(memberId);
    form.patch(route('admin.roles.team-member.update-role', memberId), {
        preserveScroll: true,
    });
};

const getInitials = (name) => (name || 'A').substring(0, 2).toUpperCase();

const onMountedFn = () => {
    selectRole(defaultRoleId.value);
    props.teamMembers.forEach(m => {
        const form = getRoleUpdateForm(m.id);
        form.role_id = m.role_id || defaultRoleId.value;
    });
};

onMounted(onMountedFn);
</script>

<template>
    <AdminLayout>
        <Head title="Cargos & Permissões - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Cargos & Permissões</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Controle de acesso granular, perfis personalizados e matriz de permissões</p>
        </template>

        <div class="space-y-6">

            <!-- Top Navigation & Actions Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight" style="color: var(--text-heading);">Acessos & Governança</h2>
                    <p class="text-xs sm:text-sm opacity-70">Defina o que cada membro ou cargo pode visualizar, editar e gerenciar no sistema.</p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <div class="inline-flex p-1.5 rounded-2xl bg-slate-200/60 dark:bg-slate-800/60 border border-slate-300/40 dark:border-slate-700/40 backdrop-blur-md">
                        <button
                            type="button"
                            @click="switchTab('roles')"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 cursor-pointer',
                                activeTab === 'roles'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-md shadow-slate-900/5 dark:shadow-black/20'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white'
                            ]"
                        >
                            <i class="fa-solid fa-shield-halved mr-1.5 text-xs"></i>
                            <span>Cargos & Permissões</span>
                        </button>
                        <button
                            type="button"
                            @click="switchTab('users')"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 cursor-pointer',
                                activeTab === 'users'
                                    ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-md shadow-slate-900/5 dark:shadow-black/20'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white'
                            ]"
                        >
                            <i class="fa-solid fa-users mr-1.5 text-xs"></i>
                            <span>Membros & Usuários</span>
                        </button>
                    </div>

                    <button
                        v-if="activeTab === 'roles'"
                        type="button"
                        @click="openCreateRoleModal"
                        class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer"
                    >
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Criar Cargo Personalizado</span>
                    </button>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 1: ROLES & PERMISSIONS MATRIX (MAIN TAB) -->
            <!-- ======================================================== -->
            <div v-if="activeTab === 'roles'" class="space-y-6">

                <!-- Roles Grid Selector -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Selecione o Cargo para Configurar</span>
                        <span class="text-xs font-semibold text-slate-500">{{ roleEntries.length }} cargos cadastrados</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div
                            v-for="(role, key) in roles"
                            :key="key"
                            @click="selectRole(key)"
                            :class="[
                                'glass-card-3d rounded-2xl p-5 cursor-pointer border-2 transition-all duration-200 relative group flex flex-col justify-between',
                                selectedRole === key
                                    ? 'border-indigo-600 bg-indigo-50/30 dark:bg-indigo-950/30 shadow-lg shadow-indigo-500/10 ring-2 ring-indigo-500/20'
                                    : 'border-slate-200/70 dark:border-slate-800/70 hover:border-slate-300 dark:hover:border-slate-700'
                            ]"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div :class="['w-11 h-11 rounded-2xl flex items-center justify-center text-lg border shadow-xs', role.badge_color]">
                                        <i :class="role.icon"></i>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <!-- Custom Role Badge -->
                                        <span
                                            v-if="isCustomRole(key)"
                                            class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/30"
                                        >
                                            Personalizado
                                        </span>
                                        <!-- Selection Indicator -->
                                        <span :class="['w-3 h-3 rounded-full', selectedRole === key ? 'bg-indigo-600 ring-4 ring-indigo-500/20' : 'bg-slate-300 dark:bg-slate-700']"></span>
                                    </div>
                                </div>

                                <h4 class="font-extrabold text-sm sm:text-base" style="color: var(--text-heading);">{{ role.name }}</h4>
                                <p class="text-[11px] opacity-70 mt-1 line-clamp-2 leading-relaxed">{{ role.description || 'Sem descrição cadastrada.' }}</p>
                            </div>

                            <div class="mt-4 pt-3 border-t flex items-center justify-between text-[11px]" style="border-color: var(--border);">
                                <span class="font-semibold text-slate-500 dark:text-slate-400">
                                    <i class="fa-solid fa-key text-[10px] mr-1 text-indigo-500"></i>
                                    {{ rolePermissions[key]?.length || 0 }} permissões
                                </span>

                                <!-- Delete button for custom roles -->
                                <button
                                    v-if="isCustomRole(key)"
                                    type="button"
                                    @click.stop="confirmDeleteRole(key, role)"
                                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 flex items-center justify-center transition-all cursor-pointer"
                                    title="Excluir Cargo"
                                >
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permission Matrix for Selected Role -->
                <form @submit.prevent="submitPermissions">
                    <input type="hidden" v-model="permissionsForm.role">

                    <div class="glass-card-3d rounded-3xl p-6 sm:p-8 space-y-6">
                        <!-- Matrix Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b" style="border-color: var(--border);">
                            <div class="flex items-center gap-3.5">
                                <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center text-xl border shadow-xs', roles[selectedRole]?.badge_color]">
                                    <i :class="roles[selectedRole]?.icon"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg sm:text-xl font-extrabold" style="color: var(--text-heading);">
                                            Permissões: {{ roleTitles[selectedRole] || selectedRole }}
                                        </h3>
                                        <span v-if="isCustomRole(selectedRole)" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/30">
                                            Custom
                                        </span>
                                    </div>
                                    <p class="text-xs opacity-60 mt-0.5">Marque os recursos que usuários com este cargo podem acessar no painel administrativo.</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <button
                                    type="button"
                                    @click="toggleAllPermissions(true)"
                                    class="px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/70 dark:bg-slate-800/70 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-bold transition-all cursor-pointer"
                                >
                                    <i class="fa-solid fa-check-double mr-1 text-emerald-500"></i>
                                    Marcar Todos
                                </button>
                                <button
                                    type="button"
                                    @click="toggleAllPermissions(false)"
                                    class="px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/70 dark:bg-slate-800/70 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-bold transition-all cursor-pointer"
                                >
                                    <i class="fa-solid fa-xmark mr-1 text-rose-500"></i>
                                    Desmarcar Todos
                                </button>
                            </div>
                        </div>

                        <!-- Modules Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <div
                                v-for="(module, modKey) in permissionModules"
                                :key="modKey"
                                class="p-5 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800/80 space-y-3.5 shadow-xs transition-all hover:border-slate-300 dark:hover:border-slate-700"
                            >
                                <div class="flex items-center justify-between pb-3 border-b border-slate-200/60 dark:border-slate-800/60">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs shrink-0">
                                            <i :class="module.icon"></i>
                                        </div>
                                        <h4 class="font-extrabold text-xs sm:text-sm" style="color: var(--text-heading);">{{ module.title }}</h4>
                                    </div>

                                    <!-- Quick module toggle -->
                                    <button
                                        type="button"
                                        @click="toggleModulePermissions(modKey)"
                                        class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
                                    >
                                        {{ isModuleFullySelected(modKey) ? 'Desmarcar' : 'Todos' }}
                                    </button>
                                </div>

                                <div class="space-y-2.5 pt-1">
                                    <label
                                        v-for="(permLabel, permKey) in module.permissions"
                                        :key="permKey"
                                        class="flex items-start gap-3 cursor-pointer p-2 rounded-xl transition-all hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 group"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="hasPermission(permKey)"
                                            @change="togglePermission(permKey)"
                                            class="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-600 cursor-pointer"
                                        >
                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors leading-tight">
                                            {{ permLabel }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Footer -->
                        <div class="pt-6 border-t flex flex-col sm:flex-row items-center justify-between gap-4" style="border-color: var(--border);">
                            <div class="flex items-center gap-2 text-xs opacity-70 text-center sm:text-left">
                                <i class="fa-solid fa-circle-info text-indigo-500"></i>
                                <span>As alterações salvas terão efeito imediato para todos os usuários com este perfil.</span>
                            </div>

                            <button
                                type="submit"
                                class="px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white font-black text-xs sm:text-sm flex items-center gap-2 shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50 cursor-pointer w-full sm:w-auto justify-center"
                                :disabled="permissionsForm.processing"
                            >
                                <i v-if="!permissionsForm.processing" class="fa-solid fa-floppy-disk"></i>
                                <i v-else class="fa-solid fa-spinner fa-spin"></i>
                                <span>{{ permissionsForm.processing ? 'Salvando...' : 'Salvar Permissões do Cargo' }}</span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>

            <!-- ======================================================== -->
            <!-- TAB 2: USERS & TEAM MEMBERS TABLE -->
            <!-- ======================================================== -->
            <div v-if="activeTab === 'users'" class="space-y-6">
                <!-- Stats Row -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="glass-card-3d p-5 rounded-2xl flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-users-gear"></i>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider opacity-60">Total de Contas</span>
                            <h3 class="text-2xl font-black mt-0.5" style="color: var(--text-heading);">{{ totalAccounts }}</h3>
                        </div>
                    </div>

                    <div class="glass-card-3d p-5 rounded-2xl flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider opacity-60">Administradores</span>
                            <h3 class="text-2xl font-black mt-0.5 text-emerald-600 dark:text-emerald-400">{{ adminCount }}</h3>
                        </div>
                    </div>

                    <div class="glass-card-3d p-5 rounded-2xl flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider opacity-60">Membros do Time</span>
                            <h3 class="text-2xl font-black mt-0.5 text-blue-600 dark:text-blue-400">{{ teamMembers.length }}</h3>
                        </div>
                    </div>

                    <div class="glass-card-3d p-5 rounded-2xl flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/30 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider opacity-60">Cargos Ativos</span>
                            <h3 class="text-2xl font-black mt-0.5" style="color: var(--text-heading);">{{ roleCount }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="glass-card-3d rounded-3xl overflow-hidden p-0">
                    <div class="p-5 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
                        <div>
                            <h3 class="text-base font-extrabold" style="color: var(--text-heading);">Contas & Membros da Equipe</h3>
                            <p class="text-xs opacity-60">Visualize os usuários e altere os cargos de acesso diretamente na tabela</p>
                        </div>
                        <Link :href="route('admin.team.index')" class="btn btn-outline py-1.5 px-3.5 text-xs font-bold rounded-xl self-start sm:self-auto">
                            <i class="fa-solid fa-user-plus text-xs mr-1"></i>
                            <span>Gerenciar Time & Especialistas</span>
                        </Link>
                    </div>

                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th>Usuário / Profissional</th>
                                    <th>Contato</th>
                                    <th>Cargo / Função</th>
                                    <th>Role Atual</th>
                                    <th>Alterar Cargo / Role</th>
                                    <th class="text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Owner Row -->
                                <tr v-for="user in users" :key="'owner-' + user.id" class="bg-indigo-500/5">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-extrabold text-sm shadow-md shrink-0">
                                                {{ getInitials(user.name) }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-extrabold text-sm" style="color: var(--text-heading);">{{ user.name }}</span>
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30">
                                                        Proprietário
                                                    </span>
                                                </div>
                                                <span class="text-[11px] opacity-60">Conta Principal do Estabelecimento</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-xs font-medium opacity-80 block">{{ user.email }}</span>
                                        <span class="text-[11px] opacity-60">Login Principal</span>
                                    </td>
                                    <td>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Diretor / Administrador</span>
                                    </td>
                                    <td>
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30">
                                            <i class="fa-solid fa-shield-halved text-[10px]"></i>
                                            <span>Administrador</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-xs opacity-50 italic">Inalterável (Dono)</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="badge badge-completed">Ativo</span>
                                    </td>
                                </tr>

                                <!-- Team Members Rows -->
                                <tr v-for="member in teamMembers" :key="'member-' + member.id">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-cyan-600 to-blue-600 flex items-center justify-center text-white font-extrabold text-xs shadow-xs shrink-0 overflow-hidden">
                                                <img v-if="member.avatar_url" :src="member.avatar_url" :alt="member.name" class="w-full h-full object-cover" />
                                                <span v-else>{{ getInitials(member.name) }}</span>
                                            </div>
                                            <div>
                                                <span class="font-extrabold text-sm block" style="color: var(--text-heading);">{{ member.name }}</span>
                                                <span class="text-[11px] opacity-60">{{ member.specialty || 'Profissional' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-xs font-medium opacity-80 block">{{ member.email || 'Sem e-mail cadastrado' }}</span>
                                        <span v-if="member.phone" class="text-[11px] opacity-60">{{ member.phone }}</span>
                                    </td>
                                    <td>
                                        <span class="text-xs font-semibold">{{ member.specialty || 'Atendimento' }}</span>
                                    </td>
                                    <td>
                                        <div :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border', roles[member.role_id]?.badge_color || 'bg-slate-500/15 text-slate-700 dark:text-slate-300 border-slate-500/30']">
                                            <i :class="roles[member.role_id]?.icon || 'fa-solid fa-user-tag'" class="text-[10px]"></i>
                                            <span>{{ roles[member.role_id]?.name || member.role_id || 'Profissional' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2 max-w-[200px]">
                                            <select
                                                v-model="getRoleUpdateForm(member.id).role_id"
                                                @change="submitRoleChange(member.id)"
                                                class="form-control text-xs py-1.5 px-2.5 rounded-xl"
                                                :disabled="getRoleUpdateForm(member.id).processing"
                                            >
                                                <option v-for="(role, key) in roles" :key="key" :value="key">
                                                    {{ role.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <template v-if="member.is_active">
                                            <span class="badge badge-completed">Ativo</span>
                                        </template>
                                        <template v-else>
                                            <span class="badge badge-cancelled">Inativo</span>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="teamMembers.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400 border-t" style="border-color: var(--border);">
                        <i class="fa-solid fa-users text-2xl mb-2 text-indigo-500"></i>
                        <p class="text-xs font-semibold">Nenhum profissional cadastrado no time ainda.</p>
                        <p class="text-[11px] opacity-70 mt-0.5">Cadastre profissionais na aba Time para atribuir cargos e permissões personalizadas.</p>
                        <Link :href="route('admin.team.index')" class="btn btn-primary text-xs py-2 px-4 rounded-xl mt-3 inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-user-plus text-xs"></i>
                            <span>Ir para Time & Profissionais</span>
                        </Link>
                    </div>
                </div>
            </div>

        </div>

        <!-- ======================================================== -->
        <!-- MODAL: CRIAR CARGO PERSONALIZADO (MODERNO & COMPLETO) -->
        <!-- ======================================================== -->
        <Teleport to="body">
            <div v-if="showCustomRoleModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="showCustomRoleModal = false">
                <div class="liquid-glass-card w-full max-w-2xl max-h-[92vh] overflow-y-auto p-6 sm:p-8 space-y-6 shadow-2xl rounded-3xl animate-in fade-in zoom-in-95 duration-200">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-cyan-500 text-white flex items-center justify-center text-lg shadow-md shrink-0">
                                <i class="fa-solid fa-shield-cat"></i>
                            </div>
                            <div>
                                <h3 class="text-lg sm:text-xl font-extrabold" style="color: var(--text-heading);">Criar Cargo Personalizado</h3>
                                <p class="text-xs opacity-60">Crie um novo perfil de acesso e herde as permissões de um cargo base</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showCustomRoleModal = false"
                            class="w-9 h-9 rounded-xl hover:bg-slate-500/10 flex items-center justify-center transition-colors cursor-pointer"
                        >
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <!-- Form Content -->
                    <form @submit.prevent="submitCustomRole" class="space-y-5">
                        <!-- Role Name & Slug -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label text-xs font-bold block mb-1">Nome do Cargo *</label>
                                <input
                                    v-model="customRoleForm.name"
                                    type="text"
                                    class="form-control text-xs sm:text-sm font-bold rounded-xl"
                                    placeholder="Ex: Supervisor de Atendimento"
                                    required
                                />
                                <p v-if="customRoleForm.errors.name" class="text-rose-500 text-xs mt-1">{{ customRoleForm.errors.name }}</p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="form-label text-xs font-bold block">Identificador Interno (Slug)</label>
                                    <button
                                        type="button"
                                        @click="customRoleForm.role_id_manual = !customRoleForm.role_id_manual"
                                        class="text-[10px] text-indigo-500 font-bold hover:underline cursor-pointer"
                                    >
                                        {{ customRoleForm.role_id_manual ? 'Automático' : 'Editar Manual' }}
                                    </button>
                                </div>
                                <input
                                    v-model="customRoleForm.role_id"
                                    :readonly="!customRoleForm.role_id_manual"
                                    type="text"
                                    class="form-control text-xs sm:text-sm font-mono rounded-xl"
                                    :class="!customRoleForm.role_id_manual ? 'opacity-70 bg-slate-500/5' : ''"
                                    placeholder="supervisor-de-atendimento"
                                />
                                <p v-if="customRoleForm.errors.role_id" class="text-rose-500 text-xs mt-1">{{ customRoleForm.errors.role_id }}</p>
                            </div>
                        </div>

                        <!-- Base Role Selection (Inheritance) -->
                        <div>
                            <label class="form-label text-xs font-bold block mb-1">
                                Começar com as permissões de qual cargo base?
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                <button
                                    v-for="(baseRole, baseKey) in roles"
                                    :key="baseKey"
                                    type="button"
                                    @click="customRoleForm.base_role_id = baseKey"
                                    :class="[
                                        'p-3 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between',
                                        customRoleForm.base_role_id === baseKey
                                            ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/40 ring-2 ring-indigo-500/30'
                                            : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white/40 dark:bg-slate-900/40'
                                    ]"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <div :class="['w-7 h-7 rounded-xl flex items-center justify-center text-xs border', baseRole.badge_color]">
                                            <i :class="baseRole.icon"></i>
                                        </div>
                                        <i v-if="customRoleForm.base_role_id === baseKey" class="fa-solid fa-circle-check text-indigo-600 text-xs"></i>
                                    </div>
                                    <div>
                                        <span class="font-extrabold text-xs block truncate" style="color: var(--text-heading);">{{ baseRole.name }}</span>
                                        <span class="text-[10px] opacity-60">{{ rolePermissions[baseKey]?.length || 0 }} perms</span>
                                    </div>
                                </button>
                            </div>
                            <p class="text-[11px] opacity-60 mt-1.5">
                                <i class="fa-solid fa-lightbulb text-amber-500 mr-1"></i>
                                O novo cargo iniciará com todas as permissões do perfil selecionado e você poderá ajustá-las a qualquer momento.
                            </p>
                        </div>

                        <!-- Visual Color Picker -->
                        <div>
                            <label class="form-label text-xs font-bold block mb-1.5">Cor e Estilo da Badge</label>
                            <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
                                <button
                                    v-for="(preset, pIdx) in colorPresets"
                                    :key="pIdx"
                                    type="button"
                                    @click="customRoleForm.badge_color = preset.badge_color"
                                    :class="[
                                        'p-2 rounded-xl border flex flex-col items-center gap-1 transition-all cursor-pointer',
                                        customRoleForm.badge_color === preset.badge_color
                                            ? 'border-indigo-600 ring-2 ring-indigo-500/40 scale-105 shadow-sm'
                                            : 'border-slate-200 dark:border-slate-800 hover:border-slate-400'
                                    ]"
                                >
                                    <span :class="['w-5 h-5 rounded-full shadow-xs', preset.bg]"></span>
                                    <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">{{ preset.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Visual Icon Picker -->
                        <div>
                            <label class="form-label text-xs font-bold block mb-1.5">Ícone do Cargo</label>
                            <div class="grid grid-cols-4 sm:grid-cols-8 gap-2 max-h-36 overflow-y-auto p-1">
                                <button
                                    v-for="(iconItem, iIdx) in iconPresets"
                                    :key="iIdx"
                                    type="button"
                                    @click="customRoleForm.icon = iconItem.icon"
                                    :class="[
                                        'p-2.5 rounded-xl border flex flex-col items-center gap-1 transition-all cursor-pointer text-center',
                                        customRoleForm.icon === iconItem.icon
                                            ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 ring-2 ring-indigo-500/30'
                                            : 'border-slate-200 dark:border-slate-800 hover:border-slate-400 text-slate-600 dark:text-slate-400'
                                    ]"
                                    :title="iconItem.label"
                                >
                                    <i :class="iconItem.icon" class="text-sm"></i>
                                    <span class="text-[9px] font-bold truncate max-w-full">{{ iconItem.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="form-label text-xs font-bold block mb-1">Descrição do Cargo (Opcional)</label>
                            <textarea
                                v-model="customRoleForm.description"
                                rows="2"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="Descreva as responsabilidades ou propósito desse cargo no estabelecimento..."
                            ></textarea>
                        </div>

                        <!-- Live Preview Box -->
                        <div class="p-4 rounded-2xl bg-slate-100 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 space-y-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pré-visualização do Cargo</span>
                            <div class="flex items-center gap-3">
                                <div :class="['w-10 h-10 rounded-2xl flex items-center justify-center text-base border shadow-xs', customRoleForm.badge_color]">
                                    <i :class="customRoleForm.icon"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-black text-sm truncate" style="color: var(--text-heading);">
                                            {{ customRoleForm.name || 'Nome do Cargo' }}
                                        </h4>
                                        <span :class="['px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border', customRoleForm.badge_color]">
                                            {{ customRoleForm.name || 'Badge' }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] opacity-60 truncate">
                                        {{ customRoleForm.description || 'Herda permissões de ' + (roles[customRoleForm.base_role_id]?.name || 'Profissional') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Bar -->
                        <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                            <button
                                type="button"
                                @click="showCustomRoleModal = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all cursor-pointer"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="customRoleForm.processing"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 cursor-pointer"
                            >
                                <i v-if="!customRoleForm.processing" class="fa-solid fa-check"></i>
                                <i v-else class="fa-solid fa-spinner fa-spin"></i>
                                <span>{{ customRoleForm.processing ? 'Criando...' : 'Criar Cargo' }}</span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </Teleport>

        <!-- ======================================================== -->
        <!-- MODAL: CONFIRMAR EXCLUSÃO DE CARGO -->
        <!-- ======================================================== -->
        <Teleport to="body">
            <div v-if="showDeleteConfirmModal && roleToDelete" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="showDeleteConfirmModal = false">
                <div class="liquid-glass-card w-full max-w-md p-6 space-y-5 shadow-2xl rounded-3xl animate-in fade-in zoom-in-95 duration-200">
                    <div class="flex items-center gap-3 text-rose-500">
                        <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Excluir Cargo Personalizado</h3>
                            <p class="text-xs text-slate-500">Esta ação removerá o cargo da empresa</p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Tem certeza que deseja excluir o cargo <strong>{{ roleToDelete.name }}</strong>?
                        Caso algum membro da equipe esteja atribuído a ele, será revertido automaticamente para <strong>Profissional</strong>.
                    </p>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="showDeleteConfirmModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="deleteRole"
                            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold flex items-center gap-1.5 shadow-md shadow-rose-600/30 transition-all cursor-pointer"
                        >
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Confirmar Exclusão</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>

<style scoped>
.glass-card-3d {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.04), inset 0 1px 1px 0 rgba(255, 255, 255, 0.8);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
html.dark .glass-card-3d {
    background: rgba(18, 24, 36, 0.6);
    border: 1px solid rgba(6, 182, 212, 0.15);
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2), inset 0 1px 0 0 rgba(255, 255, 255, 0.05);
}
.glass-card-3d:hover {
    box-shadow: 0 12px 28px 0 rgba(99, 102, 241, 0.08);
}
html.dark .glass-card-3d:hover {
    box-shadow: 0 12px 28px 0 rgba(0, 0, 0, 0.3), 0 0 15px rgba(6, 182, 212, 0.1);
    border-color: rgba(6, 182, 212, 0.4);
}
</style>
