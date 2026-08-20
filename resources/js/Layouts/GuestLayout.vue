<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const isDark = ref(true);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    const theme = isDark.value ? 'dark' : 'light';
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-theme', 'light');
    }
    localStorage.setItem('agendae_theme', theme);
};

onMounted(() => {
    const savedTheme = localStorage.getItem('agendae_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    isDark.value = savedTheme === 'dark';
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-theme', 'light');
    }
});
</script>

<template>
    <div
        class="min-h-screen flex flex-col justify-between font-sans text-slate-900 dark:text-slate-100 antialiased bg-slate-50 dark:bg-slate-950 relative overflow-x-hidden selection:bg-brand-500 selection:text-white transition-colors duration-300"
    >
        <!-- Background Ambient Dynamic Glow Meshes -->
        <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden">
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[36rem] sm:w-[52rem] h-[28rem] bg-gradient-to-tr from-indigo-500/25 via-brand-600/20 to-cyan-400/20 rounded-full blur-3xl opacity-80 dark:opacity-60 transition-opacity"></div>
            <div class="absolute -bottom-28 -right-28 w-96 h-96 bg-cyan-500/20 dark:bg-cyan-500/15 rounded-full blur-3xl opacity-70"></div>
            <div class="absolute -bottom-28 -left-28 w-96 h-96 bg-brand-600/20 dark:bg-brand-600/15 rounded-full blur-3xl opacity-70"></div>
        </div>

        <!-- Top Header Navigation -->
        <header class="w-full max-w-6xl mx-auto px-4 sm:px-6 pt-6 sm:pt-8 flex items-center justify-between z-20 shrink-0">
            <Link href="/" class="flex items-center gap-2.5 group transition-transform hover:scale-105">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 via-indigo-500 to-accent-500 flex items-center justify-center text-white shadow-lg shadow-brand-500/25">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-check"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
                </div>
                <div>
                    <span class="text-xl sm:text-2xl font-black tracking-tight bg-gradient-to-r from-indigo-500 via-brand-600 to-cyan-500 bg-clip-text text-transparent">Agendae</span>
                </div>
            </Link>

            <div class="flex items-center gap-2 sm:gap-3">
                <Link href="/" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white bg-white/70 dark:bg-slate-900/70 border border-slate-200/60 dark:border-slate-800/60 backdrop-blur-md transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>Início</span>
                </Link>

                <button
                    type="button"
                    @click="toggleTheme"
                    class="w-10 h-10 rounded-xl border border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md flex items-center justify-center cursor-pointer transition-all hover:scale-105 shadow-sm text-slate-700 dark:text-slate-200"
                    title="Alternar Tema"
                    aria-label="Alternar Tema"
                >
                    <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-400"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-500"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                </button>
            </div>
        </header>

        <!-- Main Card Centerpiece Full Page -->
        <main class="flex-1 flex items-center justify-center px-4 py-8 sm:py-12 z-10 w-full">
            <div class="w-full max-w-md relative">
                <div
                    class="relative backdrop-blur-2xl bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/80 shadow-2xl shadow-indigo-500/10 rounded-3xl p-6 sm:p-8 transition-all duration-300"
                >
                    <!-- Close Button X -->
                    <Link
                        href="/"
                        class="absolute top-4 right-4 sm:top-5 sm:right-5 w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 bg-slate-100/80 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all cursor-pointer shadow-sm"
                        title="Voltar à página inicial"
                        aria-label="Voltar para home"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                        </svg>
                    </Link>

                    <slot />
                </div>
            </div>
        </main>

        <!-- Bottom Footer -->
        <footer class="w-full max-w-6xl mx-auto px-4 py-4 sm:py-6 text-center text-xs text-slate-500 dark:text-slate-400 z-10 shrink-0">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 border-t border-slate-200/60 dark:border-slate-800/60 pt-4">
                <p>&copy; {{ new Date().getFullYear() }} <strong>Agendae</strong>. Plataforma de Agendamentos Online em Tempo Real.</p>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <Link href="/" class="hover:text-indigo-500 transition-colors">Página Inicial</Link>
                    <a href="/login" class="hover:text-indigo-500 transition-colors">Painel Admin</a>
                </div>
            </div>
        </footer>
    </div>
</template>
