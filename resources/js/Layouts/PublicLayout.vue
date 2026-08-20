<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, watch, onMounted } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Agendamento Online Simplificado',
    },
    branding: {
        type: Object,
        default: null,
    },
    company: {
        type: Object,
        default: null,
    },
});

const customStyles = computed(() => {
    const styles = {};
    const b = props.branding || {};
    const s = b.settings || {};

    if (b.background_color) {
        styles['--background'] = b.background_color;
        styles['--background-subtle'] = b.background_color;
    }

    if (b.top_menu_color) {
        styles['--surface-header'] = b.top_menu_color;
    }

    if (s.card_bg_color) {
        styles['--surface'] = s.card_bg_color;
    }

    if (s.text_color) {
        styles['--text'] = s.text_color;
        styles['--text-heading'] = s.text_color;
    }

    if (b.primary_color) {
        styles['--primary'] = b.primary_color;
        styles['--primary-hover'] = b.primary_color;
        styles['--primary-gradient'] = `linear-gradient(135deg, ${b.primary_color} 0%, ${b.primary_color}dd 100%)`;
        styles['--primary-light'] = `${b.primary_color}18`;
    }

    if (s.border_radius) {
        if (s.border_radius === 'rounded-sm') styles['--radius'] = '0.375rem';
        else if (s.border_radius === 'rounded-lg') styles['--radius'] = '0.5rem';
        else if (s.border_radius === 'rounded-xl') styles['--radius'] = '0.75rem';
        else if (s.border_radius === 'rounded-2xl') styles['--radius'] = '1rem';
        else if (s.border_radius === 'rounded-3xl') styles['--radius'] = '1.5rem';
        else if (s.border_radius === 'rounded-full') styles['--radius'] = '9999px';
    }

    return styles;
});

const businessDisplayName = computed(() => {
    return props.branding?.settings?.business_name || props.company?.name || null;
});

const whatsappCleanNumber = computed(() => {
    const raw = props.branding?.settings?.whatsapp_number;
    if (!raw) return null;
    return raw.replace(/\D/g, '');
});

const whatsappUrl = computed(() => {
    if (!whatsappCleanNumber.value) return null;
    const greeting = encodeURIComponent(`Olá! Gostaria de tirar uma dúvida sobre agendamento.`);
    return `https://wa.me/${whatsappCleanNumber.value}?text=${greeting}`;
});

const hasCustomLogo = computed(() => {
    return !!props.branding?.logo_url;
});

const currentYear = new Date().getFullYear();

watch(() => props.title, (newTitle) => {
    document.title = newTitle;
}, { immediate: true });

onMounted(() => {
    document.documentElement.classList.remove('dark');
    document.documentElement.setAttribute('data-theme', 'light');
});
</script>

<template>
    <div
        class="min-h-screen flex flex-col justify-between font-sans antialiased text-slate-800 selection:bg-brand-500 selection:text-white"
        :style="{
            'background-color': 'var(--background, #f8fafc)',
            'color': 'var(--text, #0f172a)',
            'transition': 'background-color 0.3s ease, color 0.3s ease',
            ...customStyles
        }"
    >
        <!-- Background Ambient Glow Meshes -->
        <div class="fixed inset-0 z-[-1] pointer-events-none overflow-hidden">
            <div class="absolute -top-[12%] left-1/2 -translate-x-1/2 w-[720px] h-[460px] rounded-full" style="background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, rgba(99, 102, 241, 0) 70%); filter: blur(60px);"></div>
            <div class="absolute -bottom-[8%] -right-[6%] w-[520px] h-[420px] rounded-full" style="background: radial-gradient(circle, rgba(6, 182, 212, 0.10) 0%, rgba(6, 182, 212, 0) 70%); filter: blur(60px);"></div>
        </div>

        <!-- Sticky Header Navigation -->
        <header
            class="sticky top-0 z-40 shrink-0 transition-all duration-300 backdrop-blur-md shadow-sm border-b"
            :style="{
                'background-color': 'var(--surface-header, rgba(255, 255, 255, 0.95))',
                'border-color': 'var(--border, rgba(226, 232, 240, 0.8))'
            }"
        >
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
                
                <!-- Brand / Logo Area -->
                <Link :href="route('booking.index')" class="flex items-center gap-3 group transition-transform hover:opacity-95">
                    
                    <!-- 1. Custom User Logo (Without Agendae name) -->
                    <template v-if="hasCustomLogo">
                        <div class="h-10 sm:h-12 max-w-[200px] sm:max-w-[260px] flex items-center justify-center overflow-hidden">
                            <img
                                :src="branding.logo_url"
                                :alt="businessDisplayName || 'Logo do Estabelecimento'"
                                class="h-full w-auto max-h-10 sm:max-h-12 object-contain"
                            />
                        </div>
                        <span v-if="businessDisplayName" class="hidden sm:inline-block font-extrabold text-base sm:text-lg tracking-tight truncate max-w-[220px]" style="color: var(--text-heading, #0f172a);">
                            {{ businessDisplayName }}
                        </span>
                    </template>

                    <!-- 2. Default System Logo (With Agendae name) -->
                    <template v-else>
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-tr from-brand-600 via-indigo-500 to-accent-500 flex items-center justify-center text-white shadow-lg shadow-brand-500/25 group-hover:scale-105 transition-transform duration-300">
                            <i class="fa-solid fa-calendar-check text-base sm:text-lg"></i>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xl sm:text-2xl font-black tracking-tight bg-gradient-to-r from-indigo-600 via-brand-600 to-cyan-500 bg-clip-text text-transparent">
                                Agendae
                            </span>
                            <span class="hidden sm:inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-indigo-50 text-indigo-600 border border-indigo-200">
                                Online
                            </span>
                        </div>
                    </template>
                </Link>

                <!-- Right Header Actions (Social or Contact) -->
                <div class="flex items-center gap-3">
                    <a
                        v-if="branding?.settings?.instagram_handle"
                        :href="'https://instagram.com/' + branding.settings.instagram_handle.replace('@', '')"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-700 hover:text-pink-600 transition-all shadow-sm"
                        title="Instagram"
                    >
                        <i class="fa-brands fa-instagram text-base"></i>
                    </a>

                    <a
                        v-if="whatsappUrl"
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-all shadow-sm"
                        title="Falar no WhatsApp"
                    >
                        <i class="fa-brands fa-whatsapp text-sm text-emerald-600"></i>
                        <span>WhatsApp</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Dynamic Content -->
        <main class="flex-1 flex flex-col justify-start py-6 sm:py-10 w-full min-w-0">
            <slot />
        </main>

        <!-- Floating WhatsApp Widget Button -->
        <a
            v-if="whatsappUrl && branding?.settings?.whatsapp_button_enabled"
            :href="whatsappUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full shadow-xl shadow-emerald-500/30 hover:scale-105 transition-all duration-300 group cursor-pointer"
            title="Dúvidas? Fale conosco no WhatsApp"
        >
            <i class="fa-brands fa-whatsapp text-2xl animate-bounce"></i>
            <span class="text-xs font-extrabold hidden sm:inline">Dúvidas? Fale Conosco</span>
        </a>

        <!-- Footer -->
        <footer
            class="mt-auto shrink-0 border-t py-6 text-center text-xs text-slate-500 bg-white/80 backdrop-blur-md transition-colors"
            style="border-color: var(--border, rgba(226, 232, 240, 0.8));"
        >
            <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p>
                    <template v-if="branding?.settings?.footer_text">
                        {{ branding.settings.footer_text }}
                    </template>
                    <template v-else>
                        &copy; {{ currentYear }} <strong>{{ businessDisplayName || 'Agendae' }}</strong>. Todos os direitos reservados.
                    </template>
                </p>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <Link :href="route('booking.index')" class="hover:text-indigo-600 transition-colors">Agendamento</Link>
                    <Link :href="route('admin.dashboard')" class="text-indigo-600 hover:underline flex items-center gap-1">
                        <span>Painel Administrativo</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </Link>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
.card {
    background-color: var(--surface, #ffffff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: var(--radius, 1rem);
    padding: 1.5rem;
    transition: all 0.3s ease;
}
.form-control {
    background-color: var(--background, #f8fafc);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: var(--radius-sm, 0.625rem);
    color: var(--text, #0f172a);
    width: 100%;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}
.form-control:focus {
    border-color: var(--primary, #6366f1);
    outline: none;
    box-shadow: 0 0 0 3px var(--primary-light, rgba(99, 102, 241, 0.12));
}
.btn {
    border-radius: var(--radius-sm, 0.625rem);
    padding: 0.75rem 1.5rem;
    font-weight: 700;
    transition: all 0.3s ease;
}
.btn-primary {
    background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%));
    color: #ffffff;
}
.btn-primary:hover {
    opacity: 0.95;
}
.btn-outline {
    background: transparent;
    border: 1px solid var(--border, #e2e8f0);
    color: var(--text, #0f172a);
}
.btn-outline:hover {
    background: var(--background-subtle, #f8fafc);
}
</style>
