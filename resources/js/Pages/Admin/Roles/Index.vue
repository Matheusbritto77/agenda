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

const activeTab = ref('users');
const selectedRole = ref('admin');
const selectedRolePermissions = ref([]);

const roleTitles = {
    'admin': 'Administrador',
    'manager': 'Gerente',
    'professional': 'Profissional / Especialista',
    'receptionist': 'Recepcionista / Atendente'
};

const subUserEmailsForStats = computed(() => props.subUsers?.map(u => u.email).filter(Boolean) || []);
const teamMembersNotInSubUsers = computed(() => props.teamMembers.filter(m => !m.email || !subUserEmailsForStats.value.includes(m.email)));
const adminCount = computed(() => 1 + props.teamMembers.filter(m => m.role_id === 'admin').length);
const totalAccounts = computed(() => 1 + (props.subUsers?.length || 0) + teamMembersNotInSubUsers.value.length);
const subUserEmails = computed(() => props.subUsers?.map(u => u.email).filter(Boolean) || []);

const permissionsForm = useForm({
    role: 'admin',
    permissions: [],
});

const roleUpdateForms = ref({});

const getRoleUpdateForm = (memberId) => {
    if (!roleUpdateForms.value[memberId]) {
        roleUpdateForms.value[memberId] = useForm({
            role_id: 'professional',
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
        selectedRolePermissions.value = [...permissionsForm.permissions];
    } else {
        permissionsForm.permissions.push(permKey);
        selectedRolePermissions.value = [...permissionsForm.permissions];
    }
};

const hasPermission = (permKey) => {
    return permissionsForm.permissions.includes(permKey);
};

const submitPermissions = () => {
    permissionsForm.post(route('admin.roles.permissions.update'), {
        preserveScroll: true,
    });
};

const submitRoleChange = (memberId) => {
    const form = getRoleUpdateForm(memberId);
    form.patch(route('admin.roles.team-member.update-role', memberId), {
        preserveScroll: true,
    });
};

const getInitials = (name) => (name || 'A').substring(0, 2).toUpperCase();

const getLinkedMember = (sub) => {
    const firstUserId = props.users?.[0]?.id;
    if (!firstUserId || !sub.email) return null;
    return props.teamMembers.find(m => m.user_id === firstUserId && m.email === sub.email);
};

const onMountedFn = () => {
    selectRole('admin');
    props.teamMembers.forEach(m => {
        const form = getRoleUpdateForm(m.id);
        form.role_id = m.role_id || 'professional';
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {}
    });
};

onMounted(onMountedFn);
</script>

<template>
    <AdminLayout>
        <Head title="Usuários & Permissões - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Usuários & Permissões</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Controle de acesso, perfis administrativos e matriz de permissões</p>
        </template>

        <div class="space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight" style="color: var(--text-heading);">Acessos & Governança</h2>
                    <p class="text-xs sm:text-sm opacity-70">Gerencie quem tem acesso ao painel e configure o que cada perfil pode visualizar e editar.</p>
                </div>

                <div class="inline-flex p-1.5 rounded-2xl bg-slate-200/60 dark:bg-slate-800/60 border border-slate-300/40 dark:border-slate-700/40 backdrop-blur-md self-start sm:self-auto">
                    <button
                        type="button"
                        @click="switchTab('users')"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200',
                            activeTab === 'users'
                                ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-md shadow-slate-900/5 dark:shadow-black/20'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white'
                        ]"
                    >
                        <i class="fa-solid fa-users mr-1.5 text-xs"></i>
                        <span>Usuários do Sistema</span>
                    </button>
                    <button
                        type="button"
                        @click="switchTab('roles')"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200',
                            activeTab === 'roles'
                                ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-md shadow-slate-900/5 dark:shadow-black/20'
                                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white'
                        ]"
                    >
                        <i class="fa-solid fa-shield-halved mr-1.5 text-xs"></i>
                        <span>Roles & Permissões</span>
                    </button>
                </div>
            </div>

            <div v-if="activeTab === 'users'" class="space-y-6">
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
                            <i class="fa-solid fa-shield-check"></i>
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
                            <span class="text-xs font-semibold uppercase tracking-wider opacity-60">Roles Ativas</span>
                            <h3 class="text-2xl font-black mt-0.5" style="color: var(--text-heading);">4</h3>
                        </div>
                    </div>
                </div>

                <div class="glass-card-3d rounded-3xl overflow-hidden p-0">
                    <div class="p-5 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
                        <div>
                            <h3 class="text-base font-extrabold" style="color: var(--text-heading);">Contas & Membros da Equipe</h3>
                            <p class="text-xs opacity-60">Visualize os usuários e altere os cargos/roles de acesso diretamente na tabela</p>
                        </div>
                        <Link :href="route('admin.team.index')" class="btn btn-outline py-1.5 px-3.5 text-xs font-bold rounded-xl self-start sm:self-auto">
                            <i class="fa-solid fa-user-plus text-xs mr-1"></i>
                            <span>Gerenciar Time & Fotos</span>
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
                                        <span class="text-[11px] opacity-60">Acesso via Login Principal</span>
                                    </td>
                                    <td>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Diretor / Administrador</span>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30">
                                            <i class="fa-solid fa-shield-halved text-[10px]"></i>
                                            Administrador (Dono)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-xs font-semibold opacity-60 italic">Acesso Total Irrestrito</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="badge badge-confirmed inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Ativo
                                        </span>
                                    </td>
                                </tr>

                                <template v-if="subUsers && subUsers.length > 0">
                                    <tr v-for="sub in subUsers" :key="'sub-' + sub.id" class="bg-slate-50/50 dark:bg-slate-800/30">
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-slate-500 to-slate-700 flex items-center justify-center text-white font-extrabold text-sm shadow-md shrink-0">
                                                    {{ getInitials(sub.name) }}
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-extrabold text-sm" style="color: var(--text-heading);">{{ sub.name }}</span>
                                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-500/15 text-slate-600 dark:text-slate-400 border border-slate-500/30">
                                                            Colaborador
                                                        </span>
                                                    </div>
                                                    <span class="text-[11px] opacity-60">Sub-conta vinculada ao profissional</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-xs font-medium opacity-80 block">{{ sub.email }}</span>
                                            <span class="text-[11px] opacity-60">Acesso por login independente</span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                {{ sub.role_title || (getLinkedMember(sub)?.job_title || 'Colaborador do Estabelecimento') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border', (getLinkedMember(sub)?.role_badge_color || 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30')]">
                                                <i :class="[(getLinkedMember(sub)?.role_icon || 'fa-solid fa-user-check'), 'text-[10px]']"></i>
                                                {{ getLinkedMember(sub)?.role_name || (sub.role_title || 'Profissional / Especialista') }}
                                            </span>
                                        </td>
                                        <td>
                                            <form
                                                v-if="getLinkedMember(sub)"
                                                @submit.prevent="submitRoleChange(getLinkedMember(sub).id)"
                                                class="inline-block"
                                            >
                                                <div class="relative">
                                                    <select
                                                        v-model="getRoleUpdateForm(getLinkedMember(sub).id).role_id"
                                                        @change="submitRoleChange(getLinkedMember(sub).id)"
                                                        class="text-xs font-bold py-1.5 pl-3 pr-8 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 cursor-pointer shadow-sm hover:border-indigo-500 transition-all"
                                                        title="Clique para alterar o cargo deste colaborador"
                                                    >
                                                        <option value="professional">
                                                            Profissional / Especialista
                                                        </option>
                                                        <option value="manager">
                                                            Gerente
                                                        </option>
                                                        <option value="receptionist">
                                                            Recepcionista
                                                        </option>
                                                        <option value="admin">
                                                            Administrador
                                                        </option>
                                                    </select>
                                                </div>
                                            </form>
                                            <template v-else>
                                                <span class="text-xs opacity-60 italic">Vincule e-mail no profissional</span>
                                            </template>
                                        </td>
                                        <td class="text-right">
                                            <span class="badge badge-confirmed inline-flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Ativo
                                            </span>
                                        </td>
                                    </tr>
                                </template>

                                <template v-for="member in teamMembers" :key="'member-' + member.id">
                                    <template v-if="!(member.email && subUserEmails.includes(member.email))">
                                        <tr>
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <div class="w-11 h-11 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center shrink-0 shadow-sm">
                                                        <img v-if="member.avatar_url" :src="member.avatar_url" :alt="member.name" class="w-full h-full object-cover" loading="lazy">
                                                        <div v-else class="w-full h-full bg-gradient-to-tr from-indigo-500 to-cyan-500 flex items-center justify-center text-white font-extrabold text-sm">
                                                            {{ getInitials(member.name) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span class="font-extrabold text-sm block" style="color: var(--text-heading);">{{ member.name }}</span>
                                                        <template v-if="member.subdomain">
                                                            <span class="text-[11px] font-mono text-indigo-600 dark:text-indigo-400 font-semibold">{{ member.subdomain }}.agendae.app</span>
                                                        </template>
                                                        <span v-else class="text-[11px] opacity-60">Membro da Equipe</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <template v-if="member.email">
                                                    <span class="text-xs font-medium opacity-80 block">{{ member.email }}</span>
                                                </template>
                                                <template v-if="member.phone">
                                                    <span class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                        <i class="fa-brands fa-whatsapp text-xs"></i>
                                                        {{ member.phone }}
                                                    </span>
                                                </template>
                                                <template v-if="!member.email && !member.phone">
                                                    <span class="text-xs opacity-50">-</span>
                                                </template>
                                            </td>
                                            <td>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    {{ member.job_title || member.role_title || 'Profissional do Time' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border', member.role_badge_color]">
                                                    <i :class="[member.role_icon, 'text-[10px]']"></i>
                                                    {{ member.role_name }}
                                                </span>
                                            </td>
                                            <td>
                                                <form @submit.prevent="submitRoleChange(member.id)" class="inline-block">
                                                    <div class="relative">
                                                        <select
                                                            v-model="getRoleUpdateForm(member.id).role_id"
                                                            @change="submitRoleChange(member.id)"
                                                            class="text-xs font-bold py-1.5 pl-3 pr-8 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 cursor-pointer shadow-sm hover:border-indigo-500 transition-all"
                                                            title="Clique para alterar o cargo deste profissional"
                                                        >
                                                            <option value="professional">
                                                                Profissional / Especialista
                                                            </option>
                                                            <option value="manager">
                                                                Gerente
                                                            </option>
                                                            <option value="receptionist">
                                                                Recepcionista
                                                            </option>
                                                            <option value="admin">
                                                                Administrador
                                                            </option>
                                                        </select>
                                                    </div>
                                                </form>
                                            </td>
                                            <td class="text-right">
                                                <template v-if="member.is_active">
                                                    <span class="badge badge-confirmed inline-flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        Ativo
                                                    </span>
                                                </template>
                                                <template v-else>
                                                    <span class="badge badge-cancelled">Inativo</span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
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

            <div v-if="activeTab === 'roles'" class="space-y-6">
                <form @submit.prevent="submitPermissions">

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div
                            v-for="(role, key) in roles"
                            :key="key"
                            @click="selectRole(key)"
                            :class="[
                                'glass-card-3d rounded-2xl p-5 cursor-pointer border-2 transition-all duration-200 relative',
                                selectedRole === key
                                    ? 'border-indigo-600 bg-indigo-50/20 dark:bg-indigo-950/20 shadow-lg shadow-indigo-500/10 scale-[1.01]'
                                    : 'border-slate-200/60 dark:border-slate-800/60 hover:border-slate-300 dark:hover:border-slate-700'
                            ]"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <div :class="['w-10 h-10 rounded-2xl flex items-center justify-center text-lg border', role.badge_color]">
                                    <i :class="role.icon"></i>
                                </div>
                                <span :class="['w-3 h-3 rounded-full', selectedRole === key ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-700']"></span>
                            </div>
                            <h4 class="font-extrabold text-sm" style="color: var(--text-heading);">{{ role.name }}</h4>
                            <p class="text-[11px] opacity-70 mt-1 line-clamp-2 leading-relaxed">{{ role.description }}</p>
                        </div>
                    </div>

                    <input type="hidden" v-model="permissionsForm.role">

                    <div class="glass-card-3d rounded-3xl p-6 sm:p-7 space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-5 border-b" style="border-color: var(--border);">
                            <div>
                                <h3 class="text-base font-extrabold" style="color: var(--text-heading);">
                                    Permissões para o Perfil: <span class="text-indigo-600 dark:text-indigo-400">{{ roleTitles[selectedRole] || selectedRole }}</span>
                                </h3>
                                <p class="text-xs opacity-60">Marque ou desmarque as permissões específicas que este perfil terá no painel</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" @click="toggleAllPermissions(true)" class="btn btn-outline py-1.5 px-3 text-xs rounded-xl">
                                    Marcar Todos
                                </button>
                                <button type="button" @click="toggleAllPermissions(false)" class="btn btn-outline py-1.5 px-3 text-xs rounded-xl">
                                    Desmarcar Todos
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="(module, modKey) in permissionModules" :key="modKey" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/80 space-y-3">
                                <div class="flex items-center gap-2.5 pb-2 border-b border-slate-200 dark:border-slate-700">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm shrink-0">
                                        <i :class="module.icon"></i>
                                    </div>
                                    <h4 class="font-extrabold text-xs" style="color: var(--text-heading);">{{ module.title }}</h4>
                                </div>

                                <div class="space-y-2 pt-1">
                                    <label v-for="(permLabel, permKey) in module.permissions" :key="permKey" class="flex items-start gap-2.5 cursor-pointer text-xs font-medium group">
                                        <input
                                            type="checkbox"
                                            :checked="hasPermission(permKey)"
                                            @change="togglePermission(permKey)"
                                            class="mt-0.5 w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-600"
                                        >
                                        <span class="group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors leading-tight">
                                            {{ permLabel }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="pt-5 border-t flex items-center justify-between gap-4" style="border-color: var(--border);">
                            <span class="text-xs opacity-60">Alterações terão efeito imediato para todos os usuários com este perfil.</span>
                            <button type="submit" class="btn btn-primary py-2.5 px-6 text-xs sm:text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/30" :disabled="permissionsForm.processing">
                                <i class="fa-solid fa-floppy-disk text-xs mr-1.5"></i>
                                <span>{{ permissionsForm.processing ? 'Salvando...' : 'Salvar Permissões da Role' }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

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
    transform: translateY(-2px);
    box-shadow: 0 16px 32px 0 rgba(99, 102, 241, 0.06);
}
html.dark .glass-card-3d:hover {
    box-shadow: 0 16px 32px 0 rgba(0, 0, 0, 0.3), 0 0 15px rgba(6, 182, 212, 0.1);
    border-color: rgba(6, 182, 212, 0.4);
}
</style>
