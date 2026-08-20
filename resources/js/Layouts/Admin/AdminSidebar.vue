<script setup>
import { computed } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';

const props = defineProps({
    sidebarOpen: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const canManageRoles = computed(() => page.props.auth?.canManageRoles);

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    const userPerms = page.props.auth?.permissions || [];
    return userPerms.includes(permission);
};

const navItems = computed(() => {
    const items = [
        { name: 'Dashboard Geral', icon: 'fa-solid fa-chart-pie', route: 'admin.dashboard', pattern: 'admin.dashboard' }
    ];

    if (hasPermission('reports.revenue')) {
        items.push({ name: 'Financeiro & Comissões', icon: 'fa-solid fa-wallet', route: 'admin.financial.index', pattern: 'admin.financial.*' });
    }

    if (hasPermission('appointments.view')) {
        items.push({ name: 'Agendamentos', icon: 'fa-solid fa-calendar-days', route: 'admin.appointments.index', pattern: 'admin.appointments.*' });
    }

    if (hasPermission('services.view')) {
        items.push({ name: 'Serviços & Valores', icon: 'fa-solid fa-scissors', route: 'admin.services.index', pattern: 'admin.services.*' });
    }

    if (hasPermission('team.view')) {
        items.push({ name: 'Time & Profissionais', icon: 'fa-solid fa-users', route: 'admin.team.index', pattern: 'admin.team.*' });
    }

    if (hasPermission('schedules.view')) {
        items.push({ name: 'Horários & Bloqueios', icon: 'fa-regular fa-clock', route: 'admin.business-hours.index', pattern: 'admin.business-hours.*' });
    }

    if (hasPermission('settings.domain')) {
        items.push({ name: 'Guia de Domínio', icon: 'fa-solid fa-globe', route: 'admin.domain.index', pattern: 'admin.domain.*' });
    }

    if (hasPermission('integrations.view')) {
        items.push({ name: 'Integrações', icon: 'fa-solid fa-puzzle-piece', route: 'admin.integrations.index', pattern: 'admin.integrations.*' });
    }

    if (hasPermission('branding.view')) {
        items.push({ name: 'Personalização', icon: 'fa-solid fa-palette', route: 'admin.branding.index', pattern: 'admin.branding.*' });
    }

    if (canManageRoles.value && hasPermission('settings.roles')) {
        items.push({ name: 'Cargos & Permissões', icon: 'fa-solid fa-user-shield', route: 'admin.roles.index', pattern: 'admin.roles.*' });
    }

    return items;
});

const userInitials = computed(() => {
    const name = user.value?.name || 'A';
    return name.substring(0, 2).toUpperCase();
});

const publicBookingUrl = computed(() => {
    return page.props.publicBookingUrl || '/';
});

const isActiveRoute = (pattern) => {
    const current = page.route?.name || '';
    if (pattern.includes('*')) {
        const regex = new RegExp('^' + pattern.replace(/\./g, '\\.').replace(/\*/g, '.*') + '$');
        return regex.test(current);
    }
    return current === pattern;
};

const logoutForm = useForm({});

const submitLogout = () => {
    logoutForm.post(route('logout'));
};
</script>

<template>
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-50 w-72 border-r flex flex-col transition-transform duration-300 ease-in-out md:static md:z-auto shrink-0 shadow-2xl md:shadow-none',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
        ]"
        style="background-color: var(--surface); border-color: var(--border);"
    >
        <div class="h-20 px-6 flex items-center justify-between border-b" style="border-color: var(--border);">
            <Link :href="route('admin.dashboard')" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 via-indigo-500 to-accent-500 flex items-center justify-center text-white shadow-lg shadow-brand-500/25 group-hover:scale-105 transition-transform">
                    <i class="fa-solid fa-calendar-check text-lg"></i>
                </div>
                <div>
                    <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-indigo-500 to-brand-600 bg-clip-text text-transparent">Agendae</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-wider text-brand-500 dark:text-brand-400">Painel Admin</span>
                </div>
            </Link>
            <button type="button" @click="$emit('close')" class="md:hidden opacity-60 hover:opacity-100 p-2 rounded-lg" aria-label="Fechar menu lateral">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar overscroll-contain">
            <div class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Gestão</div>

            <Link
                v-for="item in navItems"
                :key="item.name"
                :href="route(item.route)"
                :class="[
                    'flex items-center gap-3.5 px-3.5 py-3 rounded-xl font-semibold text-sm transition-all duration-200',
                    isActiveRoute(item.pattern)
                        ? 'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-brand-600/30'
                        : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80'
                ]"
                @click="$emit('close')"
            >
                <i :class="['text-base', isActiveRoute(item.pattern) ? 'text-white' : 'text-indigo-500', item.icon]"></i>
                <span>{{ item.name }}</span>
            </Link>

            <div class="pt-6 px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Atalhos</div>

            <a
                :href="publicBookingUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center justify-between px-3.5 py-3 rounded-xl font-semibold text-sm text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all duration-200 group"
            >
                <div class="flex items-center gap-3.5">
                    <i class="fa-solid fa-globe text-accent-500 transition-colors"></i>
                    <span>Página Pública</span>
                </div>
                <i class="fa-solid fa-arrow-up-right-from-square text-xs opacity-60 group-hover:opacity-100"></i>
            </a>
        </nav>

        <div class="p-4 border-t" style="border-color: var(--border); background-color: var(--background-subtle);">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-brand-600 to-indigo-700 text-white flex items-center justify-center font-bold shrink-0">
                        {{ userInitials }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold truncate">{{ user?.name || 'Administrador' }}</p>
                        <p class="text-xs opacity-60 truncate">{{ user?.email || 'admin@agendae.com' }}</p>
                    </div>
                </div>
                <form @submit.prevent="submitLogout">
                    <button type="submit" title="Sair do sistema" class="p-2.5 rounded-xl opacity-60 hover:opacity-100 hover:text-rose-500 transition-colors">
                        <i class="fa-solid fa-right-from-bracket text-lg"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>
</template>
