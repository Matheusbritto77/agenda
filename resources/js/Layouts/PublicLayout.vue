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

    const primary = b.primary_color || '#6366f1';
    const secondary = b.secondary_color || primary;
    const bg = b.background_color || '#f8fafc';
    const cardBg = s.card_bg_color || '#ffffff';
    const text = s.text_color || '#0f172a';
    const btnText = s.button_text_color || '#ffffff';
    const topMenu = b.top_menu_color || '#ffffff';

    styles['--primary'] = primary;
    styles['--primary-hover'] = secondary;
    styles['--primary-gradient'] = `linear-gradient(135deg, ${primary} 0%, ${secondary} 100%)`;
    styles['--primary-light'] = `${primary}1a`;
    styles['--background'] = bg;
    styles['--background-subtle'] = bg;
    styles['--surface'] = cardBg;
    styles['--surface-hover'] = s.card_bg_color && s.card_bg_color !== '#ffffff' ? `${text}0d` : '#f8fafc';
    styles['--surface-subtle'] = s.card_bg_color && s.card_bg_color !== '#ffffff' ? `${text}08` : '#f1f5f9';
    styles['--surface-header'] = topMenu;
    styles['--text'] = text;
    styles['--text-heading'] = text;
    styles['--text-muted'] = `${text}99`;
    styles['--btn-text'] = btnText;
    styles['--border'] = s.card_bg_color && s.card_bg_color !== '#ffffff' ? `${text}22` : '#e2e8f0';

    if (s.border_radius === 'rounded-sm') {
        styles['--radius'] = '0.375rem';
        styles['--radius-sm'] = '0.25rem';
    } else if (s.border_radius === 'rounded-lg') {
        styles['--radius'] = '0.5rem';
        styles['--radius-sm'] = '0.375rem';
    } else if (s.border_radius === 'rounded-xl') {
        styles['--radius'] = '0.75rem';
        styles['--radius-sm'] = '0.5rem';
    } else if (s.border_radius === 'rounded-2xl') {
        styles['--radius'] = '1rem';
        styles['--radius-sm'] = '0.75rem';
    } else if (s.border_radius === 'rounded-3xl') {
        styles['--radius'] = '1.5rem';
        styles['--radius-sm'] = '1rem';
    } else if (s.border_radius === 'rounded-full') {
        styles['--radius'] = '9999px';
        styles['--radius-sm'] = '9999px';
    } else {
        styles['--radius'] = '1rem';
        styles['--radius-sm'] = '0.75rem';
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

watch(() => props.branding?.favicon_url, (newFavicon) => {
    if (newFavicon) {
        const link = document.getElementById('dynamic-favicon') || document.querySelector("link[rel*='icon']");
        if (link) {
            link.href = newFavicon;
        }
        const appleLink = document.getElementById('dynamic-apple-touch-icon') || document.querySelector("link[rel*='apple-touch-icon']");
        if (appleLink) {
            appleLink.href = newFavicon;
        }
    }
}, { immediate: true });

onMounted(() => {
    document.documentElement.classList.remove('dark');
    document.documentElement.setAttribute('data-theme', 'light');
});
</script>

<template>
    <div
        class="min-h-screen flex flex-col justify-between font-sans antialiased selection:bg-brand-500 selection:text-white"
        :style="{
            'background-color': 'var(--background, #f8fafc)',
            'color': 'var(--text, #0f172a)',
            'transition': 'background-color 0.3s ease, color 0.3s ease',
            ...customStyles
        }"
    >
        <!-- Background Ambient Glow Meshes -->
        <div class="fixed inset-0 z-[-1] pointer-events-none overflow-hidden">
            <div class="absolute -top-[12%] left-1/2 -translate-x-1/2 w-[720px] h-[460px] rounded-full" :style="{ background: `radial-gradient(circle, var(--primary-light, rgba(99, 102, 241, 0.12)) 0%, transparent 70%)`, filter: 'blur(60px)' }"></div>
            <div class="absolute -bottom-[8%] -right-[6%] w-[520px] h-[420px] rounded-full" :style="{ background: `radial-gradient(circle, var(--primary-light, rgba(6, 182, 212, 0.10)) 0%, transparent 70%)`, filter: 'blur(60px)' }"></div>
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
                    
                    <!-- 1. Custom User Logo -->
                    <template v-if="hasCustomLogo">
                        <div class="h-10 sm:h-12 max-w-[200px] sm:max-w-[260px] flex items-center justify-center overflow-hidden">
                            <img
                                :src="branding.logo_url"
                                :alt="businessDisplayName || 'Logo do Estabelecimento'"
                                class="h-full w-auto max-h-10 sm:max-h-12 object-contain"
                            />
                        </div>
                    </template>
 
                    <!-- 2. Text Brand with System Icon fallback -->
                    <template v-else>
                        <div
                            class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform duration-300"
                            :style="{
                                background: 'var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%))',
                                color: 'var(--btn-text, #ffffff)'
                            }"
                        >
                            <i class="fa-solid fa-calendar-check text-base sm:text-lg"></i>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xl sm:text-2xl font-black tracking-tight" :style="{ color: 'var(--text-heading, #0f172a)' }">
                                {{ businessDisplayName || 'Agendae' }}
                            </span>
                            <span v-if="!businessDisplayName" class="hidden sm:inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
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
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border flex items-center justify-center transition-all shadow-sm"
                        :style="{
                            backgroundColor: 'var(--surface, #ffffff)',
                            borderColor: 'var(--border, #e2e8f0)',
                            color: 'var(--text, #0f172a)'
                        }"
                        title="Instagram"
                    >
                        <i class="fa-brands fa-instagram text-base"></i>
                    </a>

                    <a
                        v-if="whatsappUrl"
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-sm"
                        :style="{
                            backgroundColor: 'var(--primary-light, #ecfdf5)',
                            borderColor: 'var(--primary, #10b981)',
                            color: 'var(--primary, #059669)'
                        }"
                        title="Falar no WhatsApp"
                    >
                        <i class="fa-brands fa-whatsapp text-sm"></i>
                        <span>WhatsApp</span>
                    </a>

                    <!-- Client Area Link -->
                    <a
                        href="/cliente"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all border shadow-xs"
                        :style="{
                            borderColor: 'var(--border, #e2e8f0)',
                            backgroundColor: 'var(--surface, #ffffff)',
                            color: 'var(--text-heading, #0f172a)'
                        }"
                        title="Acessar Minha Área do Cliente"
                    >
                        <i class="fa-solid fa-user text-xs" :style="{ color: 'var(--primary)' }"></i>
                        <span class="hidden sm:inline">Minha Área</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Dynamic Content -->
        <main class="flex-1 flex flex-col justify-start py-6 sm:py-10 w-full min-w-0">
            <slot />
        </main>

        <!-- Footer -->
        <footer
            class="mt-auto shrink-0 border-t py-6 text-center text-xs backdrop-blur-md transition-colors"
            :style="{
                backgroundColor: 'var(--surface-header, rgba(255, 255, 255, 0.8))',
                borderColor: 'var(--border, rgba(226, 232, 240, 0.8))',
                color: 'var(--text-muted, #64748b)'
            }"
        >
            <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p :style="{ color: 'var(--text-muted, #64748b)' }">
                    <template v-if="branding?.settings?.footer_text">
                        {{ branding.settings.footer_text }}
                    </template>
                    <template v-else>
                        &copy; {{ currentYear }} <strong :style="{ color: 'var(--text-heading, #0f172a)' }">{{ businessDisplayName || 'Agendae' }}</strong>. Todos os direitos reservados.
                    </template>
                </p>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <a href="/cliente" class="hover:underline flex items-center gap-1" :style="{ color: 'var(--primary)' }">
                        <i class="fa-solid fa-user text-[10px]"></i>
                        <span>Área do Cliente</span>
                    </a>
                    <Link :href="route('booking.index')" class="hover:underline transition-colors" :style="{ color: 'var(--primary)' }">Agendamento</Link>
                    <Link :href="route('admin.dashboard')" class="hover:underline flex items-center gap-1" :style="{ color: 'var(--primary)' }">
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
    background-color: var(--surface, #ffffff) !important;
    border: 1px solid var(--border, #e2e8f0) !important;
    border-radius: var(--radius, 1rem) !important;
    color: var(--text, #0f172a);
    padding: 1.5rem;
    transition: all 0.3s ease;
}
.form-control {
    background-color: var(--surface, #ffffff) !important;
    border: 1px solid var(--border, #e2e8f0) !important;
    border-radius: var(--radius-sm, 0.625rem) !important;
    color: var(--text, #0f172a) !important;
    width: 100%;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}
.form-control.form-control-search {
    padding-left: 2.75rem !important;
}
.form-control:focus {
    border-color: var(--primary, #6366f1) !important;
    outline: none;
    box-shadow: 0 0 0 3px var(--primary-light, rgba(99, 102, 241, 0.12));
}
.btn {
    border-radius: var(--radius-sm, 0.625rem) !important;
    padding: 0.75rem 1.5rem;
    font-weight: 700;
    transition: all 0.3s ease;
}
.btn-primary {
    background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)) !important;
    color: var(--btn-text, #ffffff) !important;
    border: none !important;
}
.btn-primary:hover {
    opacity: 0.92;
}
.btn-outline {
    background: transparent !important;
    border: 1px solid var(--border, #e2e8f0) !important;
    color: var(--text, #0f172a) !important;
}
.btn-outline:hover {
    background: var(--primary-light, rgba(99, 102, 241, 0.12)) !important;
}
</style>
