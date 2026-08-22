<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';

defineProps({
    title: { type: String, default: 'Área do Cliente' },
});

const page = usePage();
const isDarkMode = ref(true);

const clientUser = computed(() => page.props.clientAuth?.user || page.props.client || {});
const clientInitials = computed(() => {
    const name = clientUser.value.name || 'Cliente';
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(n => n[0])
        .join('')
        .toUpperCase() || 'C';
});

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

const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
    applyTheme(isDarkMode.value ? 'dark' : 'light');
};

onMounted(() => {
    const savedTheme = localStorage.getItem('agendae_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    isDarkMode.value = savedTheme === 'dark';
    applyTheme(savedTheme);
});
</script>

<template>
    <Head :title="title + ' - Agendae'" />

    <div class="min-h-screen flex flex-col font-sans text-slate-900 dark:text-slate-100 antialiased bg-slate-50 dark:bg-slate-950 relative overflow-x-clip transition-colors duration-300">
        <!-- Ambient background dynamic glow mesh -->
        <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden">
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[48rem] h-[28rem] bg-indigo-600/15 dark:bg-indigo-600/22 rounded-full blur-[80px] opacity-90"></div>
            <div class="absolute top-1/3 -right-32 w-[32rem] h-[26rem] bg-cyan-500/12 dark:bg-cyan-500/18 rounded-full blur-[80px] opacity-80"></div>
            <div class="absolute -bottom-32 -left-32 w-[36rem] h-[26rem] bg-purple-600/10 dark:bg-purple-600/15 rounded-full blur-[80px] opacity-80"></div>
        </div>

        <!-- Sticky Header Navigation -->
        <header class="sticky top-0 z-40 border-b border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-950/80 backdrop-blur-xl transition-colors">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6">
                <!-- Brand / Logo -->
                <Link :href="route('client.dashboard')" class="flex items-center gap-3 group transition-transform hover:scale-102">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-calendar-check text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-black tracking-tight text-slate-900 dark:text-white">Agendae</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 border border-indigo-500/20">Área do Cliente</span>
                        </div>
                        <span class="block text-[11px] font-medium text-slate-500 dark:text-slate-400">Suas reservas e experiências</span>
                    </div>
                </Link>

                <!-- Right Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- User badge -->
                    <div class="hidden sm:flex items-center gap-3 px-3 py-1.5 rounded-xl border border-slate-200/60 dark:border-slate-800/60 bg-slate-50/70 dark:bg-slate-900/70">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 to-cyan-600 text-white flex items-center justify-center font-black text-xs shadow-xs">
                            {{ clientInitials }}
                        </div>
                        <div class="text-left min-w-0 max-w-[140px]">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ clientUser.name }}</p>
                            <p class="text-[10px] text-slate-400 truncate">{{ clientUser.email }}</p>
                        </div>
                    </div>

                    <!-- Theme toggle button -->
                    <button
                        type="button"
                        @click="toggleTheme"
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-center text-slate-700 dark:text-slate-200 hover:border-indigo-500/50 transition-all cursor-pointer shadow-xs"
                        :title="isDarkMode ? 'Alternar para Modo Claro' : 'Alternar para Modo Escuro'"
                        aria-label="Alternar Tema"
                    >
                        <i v-if="isDarkMode" class="fa-solid fa-sun text-amber-400 text-sm"></i>
                        <i v-else class="fa-solid fa-moon text-indigo-500 text-sm"></i>
                    </button>

                    <!-- Logout Button -->
                    <Link
                        :href="route('client.logout')"
                        method="post"
                        as="button"
                        class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 border border-transparent hover:border-rose-200 dark:hover:border-rose-900/40 transition-all cursor-pointer"
                        title="Sair da Minha Conta"
                    >
                        <i class="fa-solid fa-right-from-bracket text-xs"></i>
                        <span class="hidden sm:inline">Sair</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content Body -->
        <main class="relative z-10 flex-1 mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8">
            <!-- Flash Success Message -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform -translate-y-2 opacity-0"
            >
                <div
                    v-if="page.props.flash?.success"
                    class="mb-6 p-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 text-xs sm:text-sm font-bold flex items-center justify-between gap-3 shadow-sm"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm shrink-0">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <span>{{ page.props.flash.success }}</span>
                    </div>
                </div>
            </transition>

            <!-- Flash Warning Message -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform -translate-y-2 opacity-0"
            >
                <div
                    v-if="page.props.flash?.warning"
                    class="mb-6 p-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300 text-xs sm:text-sm font-bold flex items-center justify-between gap-3 shadow-sm"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm shrink-0">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <span>{{ page.props.flash.warning }}</span>
                    </div>
                </div>
            </transition>

            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-slate-200/70 dark:border-slate-800/70 py-6 text-center text-xs text-slate-500 dark:text-slate-400 bg-white/40 dark:bg-slate-950/40 backdrop-blur-md transition-colors">
            <div class="mx-auto max-w-7xl px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p>&copy; {{ new Date().getFullYear() }} <strong class="text-slate-800 dark:text-slate-200">Agendae</strong>. Área exclusiva do cliente.</p>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <Link :href="route('landing')" class="text-indigo-600 dark:text-cyan-400 hover:underline">Início</Link>
                    <Link :href="route('login')" class="text-indigo-600 dark:text-cyan-400 hover:underline">Sou uma Empresa</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
