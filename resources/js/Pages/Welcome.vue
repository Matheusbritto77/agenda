<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import WelcomeHero from './Welcome/WelcomeHero.vue';
import WelcomeFeatures from './Welcome/WelcomeFeatures.vue';
import WelcomeDemo from './Welcome/WelcomeDemo.vue';
import WelcomePricing from './Welcome/WelcomePricing.vue';
import WelcomeFaq from './Welcome/WelcomeFaq.vue';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});

const mobileMenuOpen = ref(false);
const isDarkMode = ref(true);

function toggleTheme() {
    isDarkMode.value = !isDarkMode.value;
    const theme = isDarkMode.value ? 'dark' : 'light';
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('agendae_theme', theme);
}

onMounted(() => {
    const savedTheme = localStorage.getItem('agendae_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    isDarkMode.value = (savedTheme === 'dark');
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    document.documentElement.setAttribute('data-theme', savedTheme);
});
</script>

<template>
    <Head title="Agendae SaaS 2026 - Gestão de Agendamentos e Vendas Online" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col font-sans selection:bg-indigo-500 selection:text-white relative overflow-x-hidden transition-colors duration-300">
        <!-- Background Mesh Glows Liquid Glass + Neon Pulse -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
            <div class="absolute -top-40 -left-40 w-[350px] sm:w-[700px] h-[350px] sm:h-[700px] bg-indigo-600/15 dark:bg-indigo-600/20 rounded-full blur-[100px] sm:blur-[130px] animate-pulse"></div>
            <div class="absolute top-1/3 -right-40 w-[300px] sm:w-[600px] h-[300px] sm:h-[600px] bg-cyan-500/10 dark:bg-cyan-500/15 rounded-full blur-[100px] sm:blur-[130px] animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute -bottom-40 left-1/3 w-[300px] sm:w-[600px] h-[300px] sm:h-[600px] bg-blue-600/10 dark:bg-blue-600/15 rounded-full blur-[100px] sm:blur-[130px] animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <!-- SaaS Header Navbar -->
        <header class="sticky top-0 z-40 backdrop-blur-xl bg-white/80 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800/80 transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
                <!-- Brand Logo -->
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-blue-600 to-cyan-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 shrink-0">
                        <i class="fa-solid fa-calendar-days text-base sm:text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="text-lg sm:text-2xl font-black tracking-tight bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 dark:from-white dark:via-slate-100 dark:to-slate-300 bg-clip-text text-transparent block truncate">Agendae</span>
                    </div>
                </div>

                <!-- Central Navigation Links -->
                <nav class="hidden md:flex items-center gap-8 text-xs font-bold text-slate-600 dark:text-slate-300">
                    <a href="#recursos" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Recursos</a>
                    <a href="#demo" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Demonstração</a>
                    <a href="#planos" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Planos & Preços</a>
                    <a href="#faq" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">FAQ</a>
                </nav>

                <!-- Right Header Actions -->
                <div class="hidden sm:flex items-center gap-3 sm:gap-4">
                    <button
                        @click="toggleTheme"
                        type="button"
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/80 text-indigo-600 dark:text-amber-400 flex items-center justify-center cursor-pointer transition-all hover:scale-105 shadow-sm"
                        title="Alternar Tema"
                    >
                        <i v-if="isDarkMode" class="fa-solid fa-sun text-amber-400 text-sm"></i>
                        <i v-else class="fa-solid fa-moon text-indigo-600 text-sm"></i>
                    </button>

                    <a
                        v-if="$page.props.auth?.user"
                        :href="route('admin.dashboard')"
                        class="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl font-bold text-xs sm:text-sm whitespace-nowrap bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-lg shadow-indigo-600/30"
                    >
                        <i class="fa-solid fa-chart-pie text-xs"></i>
                        <span>Acessar Painel</span>
                    </a>

                    <template v-else>
                        <a
                            href="/login"
                            class="px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white bg-slate-100 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-800 transition-all shadow-sm"
                        >
                            Acessar Painel
                        </a>

                        <a
                            v-if="canRegister"
                            href="/register"
                            class="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl font-extrabold text-xs sm:text-sm whitespace-nowrap bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 text-white shadow-lg shadow-indigo-600/25 hover:shadow-indigo-600/40"
                        >
                            <span>Criar Minha Conta Grátis</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </template>
                </div>

                <!-- Mobile Hamburger -->
                <div class="flex items-center sm:hidden gap-2">
                    <button
                        @click="toggleTheme"
                        type="button"
                        class="w-8 h-8 rounded-xl border border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 text-indigo-600 dark:text-amber-400 flex items-center justify-center cursor-pointer"
                    >
                        <i v-if="isDarkMode" class="fa-solid fa-sun text-amber-400 text-xs"></i>
                        <i v-else class="fa-solid fa-moon text-indigo-600 text-xs"></i>
                    </button>

                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        type="button"
                        class="p-1.5 rounded-xl border border-slate-300 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-900"
                    >
                        <i :class="['fa-solid text-base', mobileMenuOpen ? 'fa-xmark' : 'fa-bars']"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Dropdown -->
            <div v-if="mobileMenuOpen" class="sm:hidden border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-950/95 backdrop-blur-2xl p-4 space-y-3">
                <nav class="flex flex-col gap-2 font-semibold text-xs text-slate-700 dark:text-slate-300 pb-2">
                    <a href="#recursos" @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900">Recursos</a>
                    <a href="#demo" @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900">Demonstração</a>
                    <a href="#planos" @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900">Planos & Preços</a>
                    <a href="#faq" @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900">FAQ</a>
                </nav>
                <div class="border-t border-slate-150 dark:border-slate-800/80 pt-3 flex flex-col gap-2">
                    <a
                        v-if="$page.props.auth?.user"
                        :href="route('admin.dashboard')"
                        class="w-full text-center px-4 py-2.5 rounded-xl font-bold text-xs bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-md shadow-indigo-600/20"
                        @click="mobileMenuOpen = false"
                    >
                        <i class="fa-solid fa-chart-pie mr-1"></i>
                        Acessar Painel
                    </a>
                    <template v-else>
                        <a
                            href="/login"
                            class="w-full text-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
                            @click="mobileMenuOpen = false"
                        >
                            Acessar Painel
                        </a>
                        <a
                            v-if="canRegister"
                            href="/register"
                            class="w-full text-center px-4 py-2.5 rounded-xl font-extrabold text-xs bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 text-white shadow-md shadow-indigo-600/10"
                            @click="mobileMenuOpen = false"
                        >
                            Criar Minha Conta Grátis
                        </a>
                    </template>
                </div>
            </div>
        </header>

        <!-- Main Sections -->
        <main class="flex-1">
            <WelcomeHero :can-register="canRegister" />
            <WelcomeFeatures />
            <WelcomeDemo />
            <WelcomePricing />
            <WelcomeFaq :can-register="canRegister" />
        </main>
    </div>
</template>
