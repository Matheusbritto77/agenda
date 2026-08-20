<script setup>
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminSidebar from './Admin/AdminSidebar.vue';
import AdminHeader from './Admin/AdminHeader.vue';
import AdminManualBookingModal from './Admin/AdminManualBookingModal.vue';

const page = usePage();
const sidebarOpen = ref(false);
const isDarkMode = ref(true);
const showManualBookingModal = ref(false);

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
        <!-- Ambient background glows -->
        <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden">
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[42rem] h-[26rem] bg-indigo-600/16 dark:bg-indigo-600/24 rounded-full blur-[72px] opacity-90"></div>
            <div class="absolute -bottom-32 -right-32 w-[28rem] h-[22rem] bg-cyan-500/12 dark:bg-cyan-500/18 rounded-full blur-[72px] opacity-90"></div>
        </div>

        <div class="relative z-10 min-h-screen flex flex-col md:flex-row w-full">
            <div v-if="sidebarOpen" @click="closeSidebar" class="fixed inset-0 z-40 bg-slate-900/55 dark:bg-slate-950/65 backdrop-blur-xl md:hidden transition-opacity"></div>

            <AdminSidebar
                :sidebar-open="sidebarOpen"
                @close="closeSidebar"
            />

            <div class="flex-1 flex flex-col min-w-0 min-h-screen relative">
                <AdminHeader
                    :is-dark-mode="isDarkMode"
                    @toggle-sidebar="toggleSidebar"
                    @toggle-theme="toggleTheme"
                    @open-booking-modal="openManualBookingModal"
                >
                    <template #header>
                        <slot name="header" />
                    </template>
                </AdminHeader>

                <main class="relative z-10 flex-1 px-4 sm:px-8 py-4 sm:py-6 max-w-full min-w-0">
                    <div class="w-full min-w-0 max-w-full space-y-6">
                        <slot />
                    </div>
                </main>

                <footer class="relative z-10 mt-auto shrink-0 py-4 px-4 sm:px-8 border-t text-xs leading-relaxed opacity-70 transition-colors" style="border-color: var(--border); background-color: var(--background-subtle);">
                    <div class="w-full max-w-full flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                        <p>&copy; {{ new Date().getFullYear() }} <strong>Agendae</strong>. Painel de Controle e Gestão Operacional.</p>
                        <div class="flex items-center gap-4 text-xs font-semibold">
                            <a :href="page.props.publicBookingUrl || '/'" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1.5">
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                <span>Página Pública de Agendamento</span>
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <AdminManualBookingModal
            :show="showManualBookingModal"
            @close="closeManualBookingModal"
        />
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
</style>
