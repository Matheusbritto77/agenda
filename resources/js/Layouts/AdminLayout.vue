<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const sidebarOpen = ref(false);
const isDarkMode = ref(true);
const showManualBookingModal = ref(false);

const canManageRoles = computed(() => page.props.auth?.canManageRoles);

const hasPermission = (permission) => {
    // Admin has full access by default
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

const todayDate = computed(() => {
    const now = new Date();
    return now.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
});

const todayTime = computed(() => {
    const now = new Date();
    return now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
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

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};

const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
    applyTheme(isDarkMode.value ? 'dark' : 'light');
};

const applyTheme = (theme) => {
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-theme', 'light');
    }
    localStorage.setItem('agendae_theme', theme);
};

const openManualBookingModal = () => {
    showManualBookingModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const closeManualBookingModal = () => {
    showManualBookingModal.value = false;
    document.body.classList.remove('overflow-hidden');
};

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        closeManualBookingModal();
    }
};

const logoutForm = useForm({});

const submitLogout = () => {
    logoutForm.post(route('logout'));
};

const bookingForm = useForm({
    service_id: '',
    client_name: '',
    client_email: '',
    client_phone: '',
    status: 'confirmed',
    appointment_date: new Date().toISOString().split('T')[0],
    appointment_time: '09:00',
    notes: '',
});

const submitBooking = () => {
    bookingForm.post(route('admin.appointments.store'), {
        onSuccess: () => {
            closeManualBookingModal();
            bookingForm.reset();
            bookingForm.status = 'confirmed';
            bookingForm.appointment_date = new Date().toISOString().split('T')[0];
            bookingForm.appointment_time = '09:00';
        },
    });
};

onMounted(() => {
    const savedTheme = localStorage.getItem('agendae_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    isDarkMode.value = savedTheme === 'dark';
    applyTheme(savedTheme);

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            sidebarOpen.value = false;
        }
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            sidebarOpen.value = false;
            showManualBookingModal.value = false;
            document.body.classList.remove('overflow-hidden');
        }
    });
});
</script>

<template>
    <Head title="Painel Administrativo - Agendae" />

    <div class="min-h-screen flex flex-col font-sans text-slate-900 dark:text-slate-100 antialiased bg-slate-50 dark:bg-slate-950 overflow-x-hidden relative selection:bg-indigo-500 selection:text-white transition-colors duration-300">
        <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden">
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[42rem] h-[26rem] bg-indigo-600/16 dark:bg-indigo-600/24 rounded-full blur-[72px] opacity-90"></div>
            <div class="absolute -bottom-32 -right-32 w-[28rem] h-[22rem] bg-cyan-500/12 dark:bg-cyan-500/18 rounded-full blur-[72px] opacity-90"></div>
        </div>

        <div v-if="sidebarOpen" @click="closeSidebar" class="fixed inset-0 z-40 bg-slate-900/55 dark:bg-slate-950/65 backdrop-blur-xl md:hidden transition-opacity"></div>

        <div class="relative z-10 min-h-screen flex flex-col md:flex-row w-full">
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
                    <button type="button" @click="closeSidebar" class="md:hidden opacity-60 hover:opacity-100 p-2 rounded-lg" aria-label="Fechar menu lateral">
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
                        @click="closeSidebar"
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

            <div class="flex-1 flex flex-col min-w-0 min-h-screen relative">
                <header class="h-20 border-b backdrop-blur-md sticky top-0 z-30 px-4 sm:px-8 flex items-center justify-between gap-4 transition-colors duration-300" style="background-color: var(--header-bg); border-color: var(--border);">
                    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                        <button type="button" @click="toggleSidebar" class="md:hidden p-2 rounded-xl opacity-70 hover:opacity-100 transition-opacity shrink-0" aria-label="Abrir menu lateral">
                            <i class="fa-solid fa-bars text-xl"></i>
                        </button>
                        <div class="min-w-0">
                            <slot name="header">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Painel Administrativo</h1>
                                </div>
                                <p class="text-xs opacity-60 hidden sm:block truncate">Gestão simplificada de agendamentos e horários</p>
                            </slot>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                        <button
                            type="button"
                            @click="toggleTheme"
                            class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border flex items-center justify-center cursor-pointer transition-all hover:scale-105"
                            style="border-color: var(--border); background-color: var(--surface);"
                            title="Alternar Modo Claro / Escuro"
                            aria-label="Alternar Tema"
                        >
                            <i v-if="isDarkMode" class="fa-solid fa-sun text-amber-400 text-sm"></i>
                            <i v-else class="fa-solid fa-moon text-indigo-500 text-sm"></i>
                        </button>

                        <button
                            v-if="hasPermission('appointments.create')"
                            type="button"
                            @click="openManualBookingModal"
                            class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-brand-600 to-indigo-600 text-white hover:from-brand-500 hover:to-indigo-500 transition-all shadow-md shadow-brand-600/25 cursor-pointer"
                        >
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span class="hidden sm:inline">+ Agendamento Manual</span>
                            <span class="sm:hidden">+ Manual</span>
                        </button>

                        <div class="h-8 w-px opacity-20 bg-slate-500 hidden md:block"></div>

                        <div class="text-right hidden md:block">
                            <span class="text-xs opacity-60 block">{{ todayDate }}</span>
                            <span class="text-xs font-semibold"><i class="fa-regular fa-clock mr-1 text-brand-500"></i>{{ todayTime }}</span>
                        </div>
                    </div>
                </header>

                <main class="relative z-10 flex-1 px-4 sm:px-8 py-4 sm:py-6 max-w-full min-w-0">
                    <div class="w-full min-w-0 max-w-full space-y-6">
                        <slot />
                    </div>
                </main>

                <footer class="relative z-10 mt-auto shrink-0 py-4 px-4 sm:px-8 border-t text-xs leading-relaxed opacity-70 transition-colors" style="border-color: var(--border); background-color: var(--background-subtle);">
                    <div class="w-full max-w-full flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                        <p>&copy; {{ new Date().getFullYear() }} <strong>Agendae</strong>. Painel de Controle e Gestão Operacional.</p>
                        <div class="flex items-center gap-4 text-xs font-semibold">
                            <a :href="publicBookingUrl" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1.5">
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                <span>Página Pública de Agendamento</span>
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <div
            v-if="showManualBookingModal"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div class="liquid-glass-card w-full max-w-2xl p-6 sm:p-7 space-y-5 relative" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-brand-600/30">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold" style="color: var(--text-heading);">Novo Agendamento Manual</h3>
                            <p class="text-xs opacity-60">Cadastre uma reserva interna diretamente no sistema</p>
                        </div>
                    </div>
                    <button type="button" @click="closeManualBookingModal" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitBooking" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group md:col-span-2 mb-0">
                            <label class="form-label text-xs" for="modal_service_id">Selecione o Serviço *</label>
                            <select
                                id="modal_service_id"
                                v-model="bookingForm.service_id"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                required
                            >
                                <option value="">Selecione um serviço...</option>
                                <option
                                    v-for="svc in page.props.services || []"
                                    :key="svc.id"
                                    :value="svc.id"
                                >
                                    {{ svc.name }} — R$ {{ Number(svc.price || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }} ({{ svc.duration_minutes }} min)
                                </option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_client_name">Nome Completo do Cliente *</label>
                            <input
                                type="text"
                                id="modal_client_name"
                                v-model="bookingForm.client_name"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="Ex: João da Silva"
                                required
                            >
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_client_email">E-mail do Cliente *</label>
                            <input
                                type="email"
                                id="modal_client_email"
                                v-model="bookingForm.client_email"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="cliente@email.com"
                                required
                            >
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_client_phone">Telefone / WhatsApp *</label>
                            <input
                                type="text"
                                id="modal_client_phone"
                                v-model="bookingForm.client_phone"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="(11) 99999-8888"
                                required
                            >
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_status">Status Inicial *</label>
                            <select
                                id="modal_status"
                                v-model="bookingForm.status"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                required
                            >
                                <option value="confirmed">Confirmado</option>
                                <option value="pending">Pendente</option>
                                <option value="completed">Concluído</option>
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_appointment_date">Data do Agendamento *</label>
                            <input
                                type="date"
                                id="modal_appointment_date"
                                v-model="bookingForm.appointment_date"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                required
                            >
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs" for="modal_appointment_time">Horário de Início *</label>
                            <input
                                type="time"
                                id="modal_appointment_time"
                                v-model="bookingForm.appointment_time"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                required
                            >
                        </div>

                        <div class="form-group md:col-span-2 mb-0">
                            <label class="form-label text-xs" for="modal_notes">Observações Adicionais</label>
                            <textarea
                                id="modal_notes"
                                v-model="bookingForm.notes"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                rows="2"
                                placeholder="Ex: Cliente preferiu atendimento especial"
                            ></textarea>
                        </div>
                    </div>

                    <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="closeManualBookingModal"
                            class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary py-2.5 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30"
                            :disabled="bookingForm.processing"
                        >
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>{{ bookingForm.processing ? 'Salvando...' : 'Confirmar Agendamento' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style>

body {
    background-color: var(--background);
    color: var(--text);
    position: relative;
    transition: background-color 0.35s cubic-bezier(0.16, 1, 0.3, 1), color 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.card {
    background-color: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.5rem;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.35s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.14), 0 8px 16px -4px rgba(0, 0, 0, 0.06);
}

html.dark .card:hover {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.55), 0 10px 20px -5px rgba(99, 102, 241, 0.25);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.65rem 1.25rem;
    font-weight: 700;
    font-size: 0.875rem;
    border-radius: var(--radius-sm);
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.35s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1), color 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    cursor: pointer;
    text-decoration: none;
    border: 1px solid transparent;
    will-change: transform;
}

.btn-primary {
    background: var(--primary-gradient);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.38);
}
.btn-primary:hover {
    opacity: 0.98;
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.55);
}
.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-outline {
    background: var(--surface);
    border-color: var(--border);
    color: var(--text);
}
.btn-outline:hover {
    background: var(--surface-hover);
    color: var(--primary);
    border-color: var(--border-hover);
    transform: translateY(-1px);
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    font-size: 0.825rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
    transition: color 0.3s ease;
}

.form-control {
    width: 100%;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 0.65rem 0.95rem;
    color: var(--text);
    font-size: 0.95rem;
    transition: var(--transition);
}
.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-light);
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 0.875rem;
}

thead tr {
    border-bottom: 1px solid var(--border);
    background: var(--background-subtle);
    transition: background-color 0.3s ease, border-color 0.3s ease;
}

th {
    padding: 0.85rem 1rem;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    transition: color 0.3s ease;
}

tbody tr {
    border-bottom: 1px solid var(--border);
    transition: var(--transition);
}
tbody tr:hover {
    background: var(--surface-hover);
}

td {
    padding: 1rem;
    color: var(--text);
    transition: color 0.3s ease;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    transition: all 0.3s ease;
}

.badge-confirmed {
    background: var(--success-light);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.badge-cancelled {
    background: var(--danger-light);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(148, 163, 184, 0.1);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(99, 102, 241, 0.35);
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(99, 102, 241, 0.65);
}

.liquid-glass-backdrop {
    background: rgba(15, 23, 42, 0.70) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
}

.liquid-glass-card {
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(28px) saturate(200%);
    -webkit-backdrop-filter: blur(28px) saturate(200%);
    border: 1px solid rgba(255, 255, 255, 0.7);
    box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) inset, 0 1px 3px 0 rgba(255, 255, 255, 0.7) inset;
    border-radius: 1.5rem;
    animation: liquidModalIn 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    max-height: 90vh;
    overflow-y: auto;
}

html.dark .liquid-glass-card {
    background: rgba(15, 23, 42, 0.88);
    border: 1px solid rgba(148, 163, 184, 0.22);
    box-shadow: 0 35px 80px -20px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(255, 255, 255, 0.08) inset, 0 1px 2px 0 rgba(255, 255, 255, 0.12) inset;
}

@keyframes liquidModalIn {
    0% {
        opacity: 0;
        transform: scale(0.95) translateY(12px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>
