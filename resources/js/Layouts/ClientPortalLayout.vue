<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';

const props = defineProps({
    title: { type: String, default: 'Área do Cliente' },
    activeCompany: { type: Object, default: null },
    companies: { type: Array, default: () => [] },
});

const page = usePage();
const isDarkMode = ref(false);
const companyDropdownOpen = ref(false);

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

const effectiveActiveCompany = computed(() => props.activeCompany || page.props.activeCompany || null);
const effectiveCompanies = computed(() => (props.companies && props.companies.length ? props.companies : page.props.companies) || []);

const customStyles = computed(() => {
    const comp = effectiveActiveCompany.value;
    if (!comp) return {};
    const primary = comp.primary_color || '#6366f1';
    const secondary = comp.secondary_color || '#06b6d4';
    return {
        '--brand-primary': primary,
        '--brand-secondary': secondary,
        '--brand-gradient': `linear-gradient(135deg, ${primary} 0%, ${secondary} 100%)`,
    };
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

const selectCompanyContext = (companyId) => {
    companyDropdownOpen.value = false;
    router.post(route('client.companies.select', companyId || 'all'), {}, {
        preserveScroll: true,
    });
};

watch(() => effectiveActiveCompany.value?.favicon_url, (newFavicon) => {
    if (newFavicon) {
        const link = document.getElementById('dynamic-favicon') || document.createElement('link');
        link.id = 'dynamic-favicon';
        link.rel = 'icon';
        link.href = newFavicon;
        if (!link.isConnected) document.head.appendChild(link);
    }
}, { immediate: true });

onMounted(() => {
    const savedTheme = localStorage.getItem('agendae_theme') || 'light';
    isDarkMode.value = savedTheme === 'dark';
    applyTheme(savedTheme);

    const closeDropdownHandler = (e) => {
        if (!e.target.closest('#company-switcher-dropdown')) {
            companyDropdownOpen.value = false;
        }
    };
    window.addEventListener('click', closeDropdownHandler);
});
</script>

<template>
    <Head :title="title + (effectiveActiveCompany ? (' - ' + effectiveActiveCompany.name) : ' - Agendae')" />

    <div
        class="min-h-screen flex flex-col font-sans text-slate-900 dark:text-slate-100 antialiased bg-slate-50 dark:bg-slate-950 relative overflow-x-clip transition-colors duration-300"
        :style="customStyles"
    >
        <!-- Ambient background dynamic glow mesh -->
        <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden">
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[48rem] h-[28rem] bg-indigo-600/15 dark:bg-indigo-600/22 rounded-full blur-[80px] opacity-90"></div>
            <div class="absolute top-1/3 -right-32 w-[32rem] h-[26rem] bg-cyan-500/12 dark:bg-cyan-500/18 rounded-full blur-[80px] opacity-80"></div>
            <div class="absolute -bottom-32 -left-32 w-[36rem] h-[26rem] bg-purple-600/10 dark:bg-purple-600/15 rounded-full blur-[80px] opacity-80"></div>
        </div>

        <!-- Sticky Header Navigation -->
        <header class="sticky top-0 z-40 border-b border-slate-200/80 dark:border-slate-800/80 bg-white/85 dark:bg-slate-950/85 backdrop-blur-xl transition-colors">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6">
                <!-- Brand / Company Custom Logo -->
                <div class="flex items-center gap-3">
                    <Link :href="route('client.dashboard')" class="flex items-center gap-3 group transition-transform hover:scale-102">
                        <div
                            v-if="effectiveActiveCompany?.logo_url"
                            class="w-10 h-10 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-white shadow-md shadow-indigo-500/10 group-hover:scale-105 transition-transform shrink-0"
                        >
                            <img :src="effectiveActiveCompany.logo_url" :alt="effectiveActiveCompany.name" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform shrink-0">
                            <i class="fa-solid fa-calendar-check text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white truncate">
                                    {{ effectiveActiveCompany ? effectiveActiveCompany.name : 'Agendae' }}
                                </span>
                                <span class="hidden sm:inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 border border-indigo-500/20">
                                    Área do Cliente
                                </span>
                            </div>
                            <span class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 truncate">
                                {{ effectiveActiveCompany ? 'Espaço Personalizado do Estabelecimento' : 'Suas reservas e experiências' }}
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Right Actions: Company Switcher + User Profile + Theme -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Multi-Company Switcher Selector Dropdown -->
                    <div v-if="effectiveCompanies.length > 1" id="company-switcher-dropdown" class="relative">
                        <button
                            type="button"
                            @click.stop="companyDropdownOpen = !companyDropdownOpen"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-indigo-500/30 bg-indigo-50/70 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100/80 dark:hover:bg-indigo-900/50 text-xs font-bold transition-all cursor-pointer shadow-xs"
                            title="Alternar Estabelecimento"
                        >
                            <div class="w-5 h-5 rounded-lg overflow-hidden bg-indigo-600 text-white flex items-center justify-center text-[10px] shrink-0">
                                <img v-if="effectiveActiveCompany?.logo_url" :src="effectiveActiveCompany.logo_url" :alt="effectiveActiveCompany.name" class="w-full h-full object-cover" />
                                <i v-else class="fa-solid fa-store"></i>
                            </div>
                            <span class="max-w-[100px] sm:max-w-[140px] truncate">
                                {{ effectiveActiveCompany ? effectiveActiveCompany.name : 'Todas as Empresas' }}
                            </span>
                            <i class="fa-solid fa-chevron-down text-[10px] opacity-70 transition-transform" :class="{ 'rotate-180': companyDropdownOpen }"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            v-if="companyDropdownOpen"
                            class="absolute right-0 mt-2 w-72 sm:w-80 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl p-2 z-50 space-y-1 backdrop-blur-xl"
                            @click.stop
                        >
                            <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Seus Estabelecimentos</span>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Alterne para ver seus agendamentos em cada empresa:</p>
                            </div>

                            <div class="max-h-60 overflow-y-auto space-y-1 py-1">
                                <button
                                    v-for="company in effectiveCompanies"
                                    :key="company.id"
                                    type="button"
                                    @click="selectCompanyContext(company.id)"
                                    :class="[
                                        'w-full flex items-center justify-between gap-3 p-2.5 rounded-xl text-left transition-all cursor-pointer',
                                        effectiveActiveCompany?.id === company.id
                                            ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-black border border-indigo-500/20'
                                            : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold'
                                    ]"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center shrink-0">
                                            <img v-if="company.logo_url" :src="company.logo_url" :alt="company.name" class="w-full h-full object-cover" />
                                            <i v-else class="fa-solid fa-store text-xs text-indigo-500"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs truncate">{{ company.name }}</p>
                                            <span class="text-[10px] text-slate-400 block">{{ company.services_count }} atendimento(s)</span>
                                        </div>
                                    </div>
                                    <i v-if="effectiveActiveCompany?.id === company.id" class="fa-solid fa-check text-indigo-600 dark:text-cyan-400 text-xs"></i>
                                </button>
                            </div>

                            <div class="pt-1 border-t border-slate-100 dark:border-slate-800">
                                <button
                                    type="button"
                                    @click="selectCompanyContext('all')"
                                    :class="[
                                        'w-full flex items-center justify-between p-2 rounded-xl text-left transition-all cursor-pointer text-xs font-bold',
                                        !effectiveActiveCompany
                                            ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-black'
                                            : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400'
                                    ]"
                                >
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-globe text-xs"></i>
                                        <span>Todas as Empresas (Visão Global)</span>
                                    </div>
                                    <i v-if="!effectiveActiveCompany" class="fa-solid fa-check text-indigo-600 dark:text-cyan-400 text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- User profile badge -->
                    <div class="hidden md:flex items-center gap-2.5 px-3 py-1.5 rounded-xl border border-slate-200/60 dark:border-slate-800/60 bg-slate-50/70 dark:bg-slate-900/70">
                        <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-600 to-cyan-600 text-white flex items-center justify-center font-black text-[11px] shadow-xs">
                            {{ clientInitials }}
                        </div>
                        <div class="text-left min-w-0 max-w-[120px]">
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
