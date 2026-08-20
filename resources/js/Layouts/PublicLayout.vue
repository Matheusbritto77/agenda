<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Agendae - Agendamento Online Simplificado',
    },
    branding: {
        type: Object,
        default: null,
    },
});

const customStyles = computed(() => {
    if (!props.branding) {
        return {};
    }

    const styles = {};

    if (props.branding.background_color) {
        styles['--background'] = props.branding.background_color;
        styles['--background-subtle'] = props.branding.background_color;
    }

    if (props.branding.top_menu_color) {
        styles['--surface'] = props.branding.top_menu_color;
    }

    if (props.branding.primary_color) {
        styles['--primary'] = props.branding.primary_color;
        styles['--primary-hover'] = props.branding.primary_color;
        styles['--primary-gradient'] = `linear-gradient(135deg, ${props.branding.primary_color} 0%, ${props.branding.primary_color}dd 100%)`;
        styles['--primary-light'] = `${props.branding.primary_color}1a`;
    }

    return styles;
});

const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    applyTheme(isDark.value);
};

const applyTheme = (dark) => {
    const theme = dark ? 'dark' : 'light';
    if (dark) {
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
    applyTheme(isDark.value);
});

watch(() => props.title, (newTitle) => {
    document.title = newTitle;
}, { immediate: true });

const currentYear = new Date().getFullYear();
</script>

<template>
    <div
        class="min-h-screen flex flex-col justify-between font-sans antialiased selection:bg-brand-500 selection:text-white"
        :style="{
            'background-color': 'var(--background)',
            'color': 'var(--text)',
            'transition': 'background-color 0.3s ease, color 0.3s ease',
            ...customStyles
        }"
    >
        <div class="fixed inset-0 z-[-1] pointer-events-none overflow-hidden">
            <div class="absolute -top-[10%] left-1/2 -translate-x-1/2 w-[700px] h-[450px]" style="background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0) 70%); filter: blur(60px);"></div>
            <div class="absolute -bottom-[5%] -right-[5%] w-[500px] h-[400px]" style="background: radial-gradient(circle, rgba(6, 182, 212, 0.12) 0%, rgba(6, 182, 212, 0) 70%); filter: blur(60px);"></div>
        </div>

        <header class="sticky top-0 z-40 shrink-0 transition-all duration-300" style="background-color: var(--surface); backdrop-filter: blur(16px); border-bottom: 1px solid var(--border); transition: background-color 0.3s ease, border-color 0.3s ease;">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
                <Link :href="route('booking.index')" class="flex items-center gap-2.5 sm:gap-3.5 group">
                    <div v-if="branding?.logo_url" class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl border border-slate-200/50 bg-white overflow-hidden flex items-center justify-center shadow-md">
                        <img :src="branding.logo_url" class="w-full h-full object-contain p-0.5" alt="Logo" />
                    </div>
                    <div v-else class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl bg-gradient-to-tr from-brand-600 via-indigo-500 to-accent-500 flex items-center justify-center text-white shadow-lg shadow-brand-500/25 group-hover:scale-105 transition-transform duration-300">
                        <i class="fa-solid fa-calendar-check text-base sm:text-lg"></i>
                    </div>
                    <div>
                        <span class="text-xl sm:text-2xl font-black tracking-tight bg-gradient-to-r from-indigo-500 via-brand-600 to-cyan-500 bg-clip-text text-transparent">Agendae</span>
                        <span class="hidden sm:inline-block ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-brand-500/10 text-brand-600 dark:text-brand-400 border border-brand-500/20">
                            Online
                        </span>
                    </div>
                </Link>

                <div class="flex items-center gap-2 sm:gap-3">
                    <button
                        type="button"
                        @click="toggleTheme"
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border flex items-center justify-center cursor-pointer transition-all hover:scale-105"
                        style="border-color: var(--border); background-color: var(--surface);"
                        title="Alternar Modo Claro / Escuro"
                        aria-label="Alternar Tema"
                    >
                        <i v-if="isDark" class="fa-solid fa-sun text-sm text-amber-400"></i>
                        <i v-else class="fa-solid fa-moon text-sm text-indigo-500"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 flex flex-col justify-start py-6 sm:py-10 w-full min-w-0">
            <slot />
        </main>

        <footer class="mt-auto shrink-0 border-t py-6 text-center text-xs text-slate-500 dark:text-slate-400 bg-white/60 dark:bg-slate-900/60 backdrop-blur-md transition-colors" style="border-color: var(--border);">
            <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p>&copy; {{ currentYear }} <strong>Agendae</strong>. Sistema Inteligente de Agendamento Online.</p>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <Link :href="route('booking.index')" class="hover:text-indigo-500 transition-colors">Início</Link>
                    <Link :href="route('admin.dashboard')" class="text-indigo-600 dark:text-indigo-400 hover:underline">Painel Administrativo &rarr;</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
