<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: true
    },
    canRegister: {
        type: Boolean,
        default: true
    }
});

const mobileMenuOpen = ref(false);
const isDarkMode = ref(true);
const isAnnual = ref(true);
const activeFaq = ref(null);

// Interactive Demo Simulator State
const demoSelectedService = ref('corte-executivo');
const demoSelectedDate = ref('2026-08-15');
const demoSelectedTime = ref('14:30');
const demoBookingSuccess = ref(false);

const demoServices = [
    { id: 'corte-executivo', name: 'Corte Executivo & Barba', duration: '45 min', price: 'R$ 90,00' },
    { id: 'consultoria-vip', name: 'Consultoria Empresarial VIP', duration: '60 min', price: 'R$ 350,00' },
    { id: 'sessao-estetica', name: 'Sessão Estética Avançada', duration: '30 min', price: 'R$ 180,00' }
];

const demoTimeSlots = ['09:00', '10:30', '11:15', '14:30', '16:00', '17:30'];

function simulateBooking() {
    demoBookingSuccess.value = true;
    setTimeout(() => {
        demoBookingSuccess.value = false;
    }, 4000);
}

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

function toggleFaq(index) {
    activeFaq.value = activeFaq.value === index ? null : index;
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

const faqs = [
    {
        question: 'Preciso cadastrar meu cartão de crédito para testar o Agendae?',
        answer: 'Não. O plano Iniciante é 100% gratuito e não exige cartão de crédito. Você pode criar sua conta em menos de 1 minuto e começar a agendar imediatamente.'
    },
    {
        question: 'Como funciona a integração de subdomínio e domínio próprio?',
        answer: 'Toda empresa ganha instantaneamente um subdomínio exclusivo como suaempresa.agendae.app. No plano Pro ou Enterprise, você pode conectar seu domínio próprio (ex: agendamentos.suaempresa.com.br) com certificado SSL automatizado.'
    },
    {
        question: 'Meus clientes precisam baixar algum aplicativo para agendar?',
        answer: 'Não. O Agendae é uma aplicação web progressiva de altíssima velocidade. O cliente acessa seu link via navegador em qualquer celular, tablet ou computador sem precisar instalar nada.'
    },
    {
        question: 'Posso alterar a qualquer momento entre o plano Mensal e Anual?',
        answer: 'Sim. Você pode fazer upgrade, downgrade ou trocar a frequência de cobrança a qualquer momento diretamente pelo seu painel administrativo.'
    }
];
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-days text-white">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" x2="16" y1="2" y2="6"/>
                            <line x1="8" x2="8" y1="2" y2="6"/>
                            <line x1="3" x2="21" y1="10" y2="10"/>
                            <path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <span class="text-lg sm:text-2xl font-black tracking-tight bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 dark:from-white dark:via-slate-100 dark:to-slate-300 bg-clip-text text-transparent block truncate">Agendae</span>
                        <span class="hidden sm:inline-block ml-1.5 text-[10px] font-extrabold uppercase tracking-widest text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-full border border-indigo-500/20">SaaS 2026</span>
                    </div>
                </div>

                <!-- Central Navigation Links -->
                <nav class="hidden md:flex items-center gap-8 text-xs font-bold text-slate-600 dark:text-slate-300">
                    <a href="#recursos" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Recursos</a>
                    <a href="#demo" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Demonstração</a>
                    <a href="#planos" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Planos & Preços</a>
                    <a href="#faq" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">FAQ</a>
                </nav>

                <!-- Right Header Actions: Theme Switcher + CTAs -->
                <div class="hidden sm:flex items-center gap-3 sm:gap-4">
                    
                    <!-- Theme Toggle Button -->
                    <button 
                        @click="toggleTheme" 
                        type="button" 
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-900/80 text-indigo-600 dark:text-amber-400 flex items-center justify-center cursor-pointer transition-all hover:scale-105 shadow-sm"
                        title="Alternar Modo Claro / Modo Escuro"
                        aria-label="Alternar Tema"
                    >
                        <svg v-if="isDarkMode" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sun text-amber-400">
                            <circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-moon text-indigo-600">
                            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                        </svg>
                    </button>

                    <!-- CTA Acessar Painel (If Auth or Direct Link) -->
                    <a
                        v-if="$page.props.auth?.user"
                        :href="route('admin.dashboard')"
                        class="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl font-bold text-xs sm:text-sm whitespace-nowrap bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-lg shadow-indigo-600/30 btn-motion-3d"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard">
                            <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
                        </svg>
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
                            class="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl font-extrabold text-xs sm:text-sm whitespace-nowrap bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 text-white shadow-lg shadow-indigo-600/25 hover:shadow-indigo-600/40 btn-motion-3d"
                        >
                            <span>Criar Minha Conta Grátis</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right">
                                <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                            </svg>
                        </a>
                    </template>
                </div>

                <!-- Mobile Menu Hamburger Button -->
                <div class="flex items-center sm:hidden gap-2">
                    <button 
                        @click="toggleTheme" 
                        type="button" 
                        class="w-8 h-8 rounded-xl border border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 text-indigo-600 dark:text-amber-400 flex items-center justify-center cursor-pointer"
                        title="Alternar Tema"
                    >
                        <svg v-if="isDarkMode" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sun text-amber-400">
                            <circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-moon text-indigo-600">
                            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                        </svg>
                    </button>

                    <button 
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        type="button" 
                        class="p-1.5 rounded-xl border border-slate-300 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-900 focus:outline-none"
                        aria-label="Abrir Menu"
                    >
                        <svg v-if="mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">
                            <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu">
                            <line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Menu -->
            <div v-if="mobileMenuOpen" class="sm:hidden border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-950/95 backdrop-blur-2xl p-4 space-y-3">
                <nav class="flex flex-col gap-2 font-semibold text-xs text-slate-700 dark:text-slate-300 pb-2">
                    <a href="#recursos" @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900">Recursos</a>
                    <a href="#demo" @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900">Demonstração</a>
                    <a href="#planos" @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900">Planos & Preços</a>
                    <a href="#faq" @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900">FAQ</a>
                </nav>

                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-2">
                    <a
                        v-if="$page.props.auth?.user"
                        :href="route('admin.dashboard')"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-indigo-600 text-white shadow-lg"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard">
                            <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
                        </svg>
                        <span>Acessar Painel</span>
                    </a>

                    <template v-else>
                        <a
                            href="/login"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl font-bold text-sm border border-slate-300 dark:border-slate-800 text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-900/60"
                        >
                            Acessar Painel
                        </a>

                        <a
                            v-if="canRegister"
                            href="/register"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-extrabold text-sm bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 text-white shadow-lg"
                        >
                            <span>Criar Minha Conta Grátis</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right">
                                <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                            </svg>
                        </a>
                    </template>
                </div>
            </div>
        </header>

        <!-- Main SaaS Container -->
        <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-16 space-y-16 sm:space-y-28 w-full">
            
            <!-- Hero Section -->
            <section class="text-center space-y-6 sm:space-y-8 max-w-5xl mx-auto">
                
                <!-- Top Sparkle Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles text-indigo-600 dark:text-indigo-400 shrink-0 animate-spin" style="animation-duration: 8s;">
                        <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>
                    </svg>
                    <span>Plataforma SaaS Ultra-Premium de Agendamentos 2026</span>
                </div>

                <!-- Main Title & Subtitle -->
                <div class="space-y-4 sm:space-y-6">
                    <h1 class="text-3xl sm:text-6xl md:text-7xl font-black tracking-tight leading-none text-slate-900 dark:text-white">
                        Agendamentos Automatizados com <span class="bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 dark:from-indigo-400 dark:via-blue-400 dark:to-cyan-400 bg-clip-text text-transparent">Alta Performance</span>
                    </h1>
                    <p class="text-sm sm:text-xl text-slate-600 dark:text-slate-400 max-w-3xl mx-auto leading-relaxed font-normal">
                        Simplifique o fluxo de agendamentos da sua empresa. Receba confirmações instantâneas, sincronize horários sem conflitos e eleve sua taxa de conversão a 60fps.
                    </p>
                </div>

                <!-- CTA Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 pt-2 w-full max-w-md sm:max-w-none mx-auto">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-4 rounded-2xl font-extrabold text-sm sm:text-base bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 text-white shadow-xl shadow-indigo-600/30 btn-motion-3d"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rocket">
                            <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.71.79-1.81.79-1.81l-1.98-1.98s-1.1.08-1.81.79z"/><path d="M12 15l-3-3 8.5-8.5c.83-.83 2.17-.83 3 0s.83 2.17 0 3L12 15z"/>
                        </svg>
                        <span>Criar Minha Conta Grátis</span>
                    </Link>

                    <a
                        href="#demo"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-4 rounded-2xl font-extrabold text-sm sm:text-base bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 btn-motion-3d shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-play">
                            <polygon points="6 3 20 12 6 21 6 3"/>
                        </svg>
                        <span>Ver Demonstração ao Vivo</span>
                    </a>
                </div>

                <!-- Trust Metrics Bar -->
                <div class="pt-6 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto border-t border-slate-200/80 dark:border-slate-800/80 text-left">
                    <div class="space-y-0.5">
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">99.9%</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Uptime Garantido</div>
                    </div>
                    <div class="space-y-0.5">
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">0.02s</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Tempo de Resposta</div>
                    </div>
                    <div class="space-y-0.5">
                        <div class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400">+100.000</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Agendamentos / Mês</div>
                    </div>
                    <div class="space-y-0.5">
                        <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400">100%</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Isolamento Multi-tenant</div>
                    </div>
                </div>
            </section>

            <!-- Dashboard 3D Live Interactive Preview Window -->
            <section id="demo" class="space-y-6 text-center w-full">
                <div class="space-y-2">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Interface Operacional 3D</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white">Painel Administrativo em Tempo Real</h2>
                </div>

                <!-- 3D Card Window Container -->
                <div class="perspective-1000">
                    <div class="rounded-3xl p-4 sm:p-6 liquid-glass-2026 card-tilt-3d glass-specular-glow shadow-2xl max-w-5xl mx-auto text-left relative overflow-hidden group">
                        
                        <!-- Ambient Neon Rays Background -->
                        <div class="absolute -top-32 -right-32 w-80 h-80 rounded-full border-2 border-indigo-500/20 animate-ping pointer-events-none -z-10" style="animation-duration: 4s;"></div>
                        <div class="absolute -bottom-32 -left-32 w-80 h-80 rounded-full border-2 border-cyan-500/20 animate-pulse pointer-events-none -z-10"></div>

                        <!-- Top Mac Window Chrome Header -->
                        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200 dark:border-slate-800/80">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-rose-500/80"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                                <span class="ml-2 text-xs font-mono opacity-70 text-slate-600 dark:text-slate-300 hidden sm:inline">agendae.app/admin/dashboard</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">Sincronização 60FPS</span>
                            </div>
                        </div>

                        <!-- Main Dashboard Grid Content -->
                        <div class="space-y-4">
                            
                            <!-- Key Metrics Row -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="p-4 rounded-2xl bg-white/60 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                                    <div>
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Atendimentos Hoje</span>
                                        <h4 class="text-xl font-black text-slate-900 dark:text-white mt-0.5">18 Confirmados</h4>
                                    </div>
                                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle-2">
                                            <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
                                        </svg>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-white/60 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                                    <div>
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Faturamento Projetado</span>
                                        <h4 class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">R$ 2.450,00</h4>
                                    </div>
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up">
                                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                                        </svg>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-white/60 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
                                    <div>
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Domínio Ativo</span>
                                        <h4 class="text-xl font-black text-cyan-600 dark:text-cyan-400 mt-0.5">SSL Protegido</h4>
                                    </div>
                                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check">
                                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.8 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Interactive Booking Simulator Widget Inside Preview -->
                            <div class="p-5 rounded-2xl bg-white/80 dark:bg-slate-950/90 border border-slate-200 dark:border-slate-800/80 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sliders text-indigo-600 dark:text-indigo-400">
                                            <line x1="4" x2="4" y1="21" y2="14"/><line x1="4" x2="4" y1="10" y2="3"/><line x1="12" x2="12" y1="21" y2="12"/><line x1="12" x2="12" y1="8" y2="3"/><line x1="20" x2="20" y1="21" y2="16"/><line x1="20" x2="20" y1="12" y2="3"/><line x1="2" x2="6" y1="14" y2="14"/><line x1="10" x2="14" y1="8" y2="8"/><line x1="18" x2="22" y1="16" y2="16"/>
                                        </svg>
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">Simulador de Agendamento em Tempo Real</span>
                                    </div>
                                    <span class="text-xs text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-500/10 px-2.5 py-1 rounded-full border border-indigo-500/20">Modo Interativo</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <!-- Service Selector -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400">1. Selecione o Serviço</label>
                                        <select v-model="demoSelectedService" class="w-full bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl p-2.5 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                            <option v-for="s in demoServices" :key="s.id" :value="s.id">
                                                {{ s.name }} ({{ s.price }})
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Time Slots Selector -->
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400">2. Horário Disponível</label>
                                        <div class="flex flex-wrap gap-1.5">
                                            <button 
                                                v-for="time in demoTimeSlots" 
                                                :key="time"
                                                @click="demoSelectedTime = time"
                                                type="button"
                                                :class="[
                                                    'px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all',
                                                    demoSelectedTime === time 
                                                        ? 'bg-indigo-600 text-white shadow-md' 
                                                        : 'bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-indigo-500/20'
                                                ]"
                                            >
                                                {{ time }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Simulation Action Button -->
                                    <div class="space-y-1.5 flex flex-col justify-end">
                                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400">3. Testar Confirmação</label>
                                        <button 
                                            @click="simulateBooking"
                                            type="button"
                                            class="w-full py-2.5 rounded-xl font-extrabold text-xs bg-emerald-600 hover:bg-emerald-500 text-white shadow-md transition-all flex items-center justify-center gap-1.5"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                            <span>Simular Agendamento</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Dynamic Feedback Banner -->
                                <div v-if="demoBookingSuccess" class="p-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-center gap-2 animate-bounce">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-party-popper">
                                        <path d="M5.8 11.3 2 22l10.7-3.79"/>
                                        <path d="M4 3h.01"/><path d="M22 8h.01"/><path d="M15 2h.01"/><path d="M22 20h.01"/>
                                    </svg>
                                    <span>Agendamento confirmado para {{ demoSelectedTime }} com sucesso! Notificação enviada em tempo real.</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- Features Grid Section -->
            <section id="recursos" class="space-y-10 text-center w-full">
                <div class="space-y-2">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Recursos de Alta Performance</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white">Engenharia de Luxo para Seu Negócio</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left perspective-1000">
                    
                    <!-- Feature Card 1 -->
                    <div class="p-6 sm:p-8 rounded-3xl liquid-glass-2026 card-tilt-3d glass-specular-glow space-y-4 hover:border-indigo-500/50 transition-all shadow-sm">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl shrink-0 card-3d-layer-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-globe">
                                <circle cx="12" cy="12" r="10"/><line x1="2" x2="22" y1="12" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white card-3d-layer-1">Subdomínio Grátis ou Próprio</h3>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed card-3d-layer-1">
                            Defina seu endereço exclusivo (`suaempresa.agendae.app`) ou conecte o seu próprio domínio profissional com certificado SSL automatizado.
                        </p>
                    </div>

                    <!-- Feature Card 2 -->
                    <div class="p-6 sm:p-8 rounded-3xl liquid-glass-2026 card-tilt-3d glass-specular-glow space-y-4 hover:border-cyan-500/50 transition-all shadow-sm">
                        <div class="w-12 h-12 rounded-2xl bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-xl shrink-0 card-3d-layer-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-check">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white card-3d-layer-1">Slots Livres em Tempo Real</h3>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed card-3d-layer-1">
                            Algoritmo inteligente de disponibilidade que previne overbooking e exibe apenas horários válidos conforme a duração de cada serviço.
                        </p>
                    </div>

                    <!-- Feature Card 3 -->
                    <div class="p-6 sm:p-8 rounded-3xl liquid-glass-2026 card-tilt-3d glass-specular-glow space-y-4 hover:border-emerald-500/50 transition-all shadow-sm">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shrink-0 card-3d-layer-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white card-3d-layer-1">Arquitetura Multi-Tenant Segura</h3>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed card-3d-layer-1">
                            Total isolamento de dados por empresa. Segurança de nível corporativo para proteger suas informações e contatos de clientes.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Interactive Pricing Table Section (Mensal / Anual) -->
            <section id="planos" class="space-y-10 text-center pt-4 w-full">
                
                <div class="space-y-4 max-w-2xl mx-auto">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Planos & Preços</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white">Escolha o Plano Ideal para Sua Empresa</h2>
                    
                    <!-- Billing Toggle Switcher (Mensal / Anual) -->
                    <div class="inline-flex items-center gap-3 p-1.5 rounded-2xl bg-slate-200/80 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 shadow-inner mt-2">
                        <button 
                            @click="isAnnual = false"
                            type="button"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer',
                                !isAnnual 
                                    ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-md' 
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                            ]"
                        >
                            Cobrança Mensal
                        </button>
                        
                        <button 
                            @click="isAnnual = true"
                            type="button"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer',
                                isAnnual 
                                    ? 'bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-md' 
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                            ]"
                        >
                            <span>Cobrança Anual</span>
                            <span class="bg-emerald-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase">Economize 20%</span>
                        </button>
                    </div>
                </div>

                <!-- Pricing Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 max-w-6xl mx-auto gap-6 sm:gap-8 text-left perspective-1000">
                    
                    <!-- Plan 1: Iniciante Free -->
                    <div class="p-6 sm:p-8 rounded-3xl liquid-glass-2026 card-tilt-3d glass-specular-glow space-y-6 flex flex-col justify-between hover:border-slate-400 dark:hover:border-slate-700 transition-all shadow-sm">
                        <div class="space-y-4 card-3d-layer-1">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20 inline-block">Iniciante Free</span>
                            
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-4xl sm:text-5xl font-black text-slate-900 dark:text-white">R$ 0</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">/mês para sempre</span>
                            </div>
                            
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                Ideal para autônomos e pequenos estabelecimentos organizarem seus primeiros horários.
                            </p>
                            
                            <ul class="space-y-3 text-xs text-slate-700 dark:text-slate-300 pt-2">
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Subdomínio gratuito `.agendae.app`</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Catálogo completo de serviços</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Grade de disponibilidade interativa</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Confirmação automatizada via web</span>
                                </li>
                            </ul>
                        </div>

                        <Link 
                            v-if="canRegister"
                            :href="route('register')" 
                            class="w-full py-3.5 rounded-xl font-bold text-center text-xs sm:text-sm bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-900 dark:text-white btn-motion-3d transition-all block shadow-sm card-3d-layer-2"
                        >
                            Criar Conta Grátis
                        </Link>
                    </div>

                    <!-- Plan 2: Pro SaaS (Popular) -->
                    <div class="p-6 sm:p-8 rounded-3xl liquid-glass-2026 card-tilt-3d glass-specular-glow border-2 border-indigo-500 dark:border-indigo-400 pricing-card-popular space-y-6 flex flex-col justify-between relative shadow-2xl">
                        
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-indigo-600 to-cyan-600 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1 rounded-full shadow-lg whitespace-nowrap card-3d-layer-2">
                            Mais Escolhido
                        </div>
                        
                        <div class="space-y-4 pt-1 card-3d-layer-1">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-cyan-600 dark:text-cyan-300 bg-cyan-500/20 px-3 py-1 rounded-full border border-cyan-500/30 inline-block">Plano Pro SaaS</span>
                            
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-4xl sm:text-5xl font-black text-slate-900 dark:text-white transition-all">
                                    {{ isAnnual ? 'R$ 39' : 'R$ 49' }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                                    /mês {{ isAnnual ? '(faturamento anual)' : '' }}
                                </span>
                            </div>
                            
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                Para empresas que desejam presença de marca personalizada e automação de ponta.
                            </p>

                            <ul class="space-y-3 text-xs text-slate-700 dark:text-slate-200 pt-2">
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">Conexão de Domínio Próprio</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Agendamentos Ilimitados</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>SSL Automático & CDN de alta velocidade</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Suporte Prioritário VIP</span>
                                </li>
                            </ul>
                        </div>

                        <Link 
                            v-if="canRegister"
                            :href="route('register')" 
                            class="w-full py-3.5 rounded-xl font-extrabold text-center text-xs sm:text-sm bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 text-white shadow-xl shadow-indigo-600/30 btn-motion-3d block card-3d-layer-2"
                        >
                            Começar Teste Pro
                        </Link>
                    </div>

                    <!-- Plan 3: Corporate Enterprise -->
                    <div class="p-6 sm:p-8 rounded-3xl liquid-glass-2026 card-tilt-3d glass-specular-glow space-y-6 flex flex-col justify-between hover:border-slate-400 dark:hover:border-slate-700 transition-all shadow-sm">
                        <div class="space-y-4 card-3d-layer-1">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20 inline-block">Corporate Enterprise</span>
                            
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-4xl sm:text-5xl font-black text-slate-900 dark:text-white transition-all">
                                    {{ isAnnual ? 'R$ 119' : 'R$ 149' }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
                                    /mês {{ isAnnual ? '(faturamento anual)' : '' }}
                                </span>
                            </div>
                            
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                Para redes de franquias, grandes clínicas e operações corporativas de alto volume.
                            </p>
                            
                            <ul class="space-y-3 text-xs text-slate-700 dark:text-slate-300 pt-2">
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Tudo do Plano Pro</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Múltiplos Estabelecimentos & Unidades</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Gerente de Conta Dedicado</span>
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-500 shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>SLA de 99.9% com suporte 24/7</span>
                                </li>
                            </ul>
                        </div>

                        <Link 
                            v-if="canRegister"
                            :href="route('register')" 
                            class="w-full py-3.5 rounded-xl font-bold text-center text-xs sm:text-sm bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-900 dark:text-white btn-motion-3d transition-all block shadow-sm card-3d-layer-2"
                        >
                            Falar com Consultor
                        </Link>
                    </div>

                </div>
            </section>

            <!-- FAQ Section Accordion -->
            <section id="faq" class="space-y-8 max-w-4xl mx-auto text-center w-full">
                <div class="space-y-2">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Dúvidas Frequentes</span>
                    <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white">Perguntas & Respostas</h2>
                </div>

                <div class="space-y-3 text-left">
                    <div 
                        v-for="(faq, idx) in faqs" 
                        :key="idx" 
                        class="rounded-2xl liquid-glass-2026 border border-slate-200 dark:border-slate-800 overflow-hidden transition-all shadow-sm"
                    >
                        <button 
                            @click="toggleFaq(idx)"
                            type="button" 
                            class="w-full p-5 text-left flex items-center justify-between font-bold text-sm sm:text-base text-slate-900 dark:text-white focus:outline-none cursor-pointer"
                        >
                            <span>{{ faq.question }}</span>
                            <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                width="18" 
                                height="18" 
                                viewBox="0 0 24 24" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round" 
                                :class="['lucide lucide-chevron-down transition-transform duration-300 shrink-0 ml-2', activeFaq === idx ? 'rotate-180 text-indigo-600' : 'text-slate-400']"
                            >
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        
                        <div v-if="activeFaq === idx" class="px-5 pb-5 pt-0 text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed border-t border-slate-100 dark:border-slate-800/60 mt-1 pt-3">
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- Final CTA Banner Section -->
            <section class="rounded-3xl p-8 sm:p-14 liquid-glass-2026 border border-indigo-500/30 text-center relative overflow-hidden space-y-6 shadow-2xl">
                <div class="absolute -top-24 -left-24 w-64 h-64 rounded-full bg-indigo-500/20 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -right-24 w-64 h-64 rounded-full bg-cyan-500/20 blur-3xl pointer-events-none"></div>

                <h2 class="text-2xl sm:text-5xl font-black text-slate-900 dark:text-white max-w-3xl mx-auto leading-tight">
                    Pronto para Elevar o Padrão dos Seus Agendamentos?
                </h2>
                <p class="text-xs sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                    Crie sua conta em menos de 1 minuto sem precisar de cartão de crédito.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                    <a
                        v-if="canRegister"
                        href="/register"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl font-extrabold text-sm sm:text-base bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 text-white shadow-xl shadow-indigo-600/30 btn-motion-3d"
                    >
                        <span>Criar Minha Conta Grátis</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right">
                            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                        </svg>
                    </a>

                    <a
                        href="/login"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl font-extrabold text-sm sm:text-base bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 btn-motion-3d shadow-sm"
                    >
                        <span>Acessar Painel</span>
                    </a>
                </div>
            </section>

        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 dark:border-slate-800/80 bg-white/70 dark:bg-slate-950/90 backdrop-blur-xl py-10 text-center text-xs text-slate-500 dark:text-slate-400 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">
                <div class="flex items-center justify-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-indigo-600 text-white font-bold flex items-center justify-center text-xs shadow-md shadow-indigo-600/30">A</div>
                    <span class="font-extrabold text-slate-800 dark:text-slate-200 text-sm">Agendae SaaS Multi-Empresa 2026</span>
                </div>
                <p>&copy; {{ new Date().getFullYear() }} Agendae Inc. Todos os direitos reservados. Plataforma corporativa de agendamentos online.</p>
            </div>
        </footer>

    </div>
</template>
