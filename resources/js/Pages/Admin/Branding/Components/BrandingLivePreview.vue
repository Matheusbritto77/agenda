<script setup>
import { ref, computed, watch } from 'vue';
import BrandingMockupProfileView from './BrandingMockupProfileView.vue';
import BrandingMockupBookingSteps from './BrandingMockupBookingSteps.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    logoPreview: {
        type: String,
        default: null,
    },
    bannerPreview: {
        type: String,
        default: null,
    },
    category: {
        type: String,
        default: 'profile', // 'profile' | 'booking'
    },
    bookingStep: {
        type: Number,
        default: 1, // 1 to 5
    },
});

const emit = defineEmits(['update:category', 'update:bookingStep', 'step-selected']);

// Internal preview state synced with props
const activeCategory = ref(props.category);
const currentBookingStep = ref(props.bookingStep);

watch(() => props.category, (newVal) => {
    if (newVal && newVal !== activeCategory.value) {
        activeCategory.value = newVal;
    }
});

watch(() => props.bookingStep, (newVal) => {
    if (newVal && newVal !== currentBookingStep.value) {
        currentBookingStep.value = newVal;
    }
});

// Preview device mode: 'mobile' | 'desktop'
const previewDevice = ref('mobile');

const bookingStepNames = [
    { step: 1, name: 'Profissional', icon: 'fa-solid fa-user-tie' },
    { step: 2, name: 'Serviço', icon: 'fa-solid fa-scissors' },
    { step: 3, name: 'Data & Hora', icon: 'fa-regular fa-calendar-days' },
    { step: 4, name: 'Confirmação', icon: 'fa-solid fa-clipboard-check' },
    { step: 5, name: 'Sucesso', icon: 'fa-solid fa-circle-check' },
];

const totalPagesCount = computed(() => {
    return activeCategory.value === 'profile' ? 1 : bookingStepNames.length;
});

const currentPageTitle = computed(() => {
    if (activeCategory.value === 'profile') {
        return 'Página Inicial & Perfil da Empresa';
    }
    const found = bookingStepNames.find(s => s.step === currentBookingStep.value);
    return found ? `Etapa ${found.step}: ${found.name}` : 'Fluxo de Agendamento';
});

const setCategory = (cat) => {
    activeCategory.value = cat;
    emit('update:category', cat);
    if (cat === 'booking') {
        emit('update:bookingStep', currentBookingStep.value);
        emit('step-selected', { category: 'booking', step: currentBookingStep.value });
    } else {
        emit('step-selected', { category: 'profile', step: 0 });
    }
};

const setBookingStep = (step) => {
    activeCategory.value = 'booking';
    currentBookingStep.value = step;
    emit('update:category', 'booking');
    emit('update:bookingStep', step);
    emit('step-selected', { category: 'booking', step });
};

const nextPage = () => {
    if (activeCategory.value === 'profile') {
        setBookingStep(1);
    } else {
        if (currentBookingStep.value < bookingStepNames.length) {
            setBookingStep(currentBookingStep.value + 1);
        }
    }
};

const prevPage = () => {
    if (activeCategory.value === 'booking') {
        if (currentBookingStep.value > 1) {
            setBookingStep(currentBookingStep.value - 1);
        } else {
            setCategory('profile');
        }
    }
};

// Helper for dynamic border radius style/classes
const radiusClass = computed(() => {
    return props.form.border_radius || 'rounded-2xl';
});

const buttonRadiusClass = computed(() => {
    if (props.form.border_radius === 'rounded-full') return 'rounded-full';
    if (props.form.border_radius === 'rounded-3xl') return 'rounded-2xl';
    if (props.form.border_radius === 'rounded-none') return 'rounded-none';
    if (props.form.border_radius === 'rounded-lg') return 'rounded-lg';
    return 'rounded-xl';
});

// Formatted business name
const businessName = computed(() => props.form.business_name || 'Minha Empresa');
</script>

<template>
    <div class="space-y-4 sticky top-20">
        <!-- Header with title, live badge & device toggle -->
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-xs">
                    <i class="fa-solid fa-mobile-screen"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Pré-visualização da Página
                    </h4>
                    <p class="text-[10px] text-slate-400">Sincronizado em tempo real com as opções de personalização</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Device view toggle -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs">
                    <button
                        type="button"
                        @click="previewDevice = 'mobile'"
                        :class="[
                            'px-2 py-1 rounded-md text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer',
                            previewDevice === 'mobile'
                                ? 'bg-white dark:bg-slate-900 text-indigo-600 shadow-xs'
                                : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        ]"
                        title="Visualização Mobile"
                    >
                        <i class="fa-solid fa-mobile-screen-button"></i>
                        <span class="hidden sm:inline">Mobile</span>
                    </button>
                    <button
                        type="button"
                        @click="previewDevice = 'desktop'"
                        :class="[
                            'px-2 py-1 rounded-md text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer',
                            previewDevice === 'desktop'
                                ? 'bg-white dark:bg-slate-900 text-indigo-600 shadow-xs'
                                : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        ]"
                        title="Visualização Expandida"
                    >
                        <i class="fa-solid fa-laptop"></i>
                        <span class="hidden sm:inline">Desktop</span>
                    </button>
                </div>

                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 font-bold border border-emerald-500/20 shrink-0">
                    Tempo Real
                </span>
            </div>
        </div>

        <!-- Paginator / Category Navigation Bar -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-3 shadow-xs space-y-3">
            <!-- Mode Switcher Tabs: Perfil vs Fluxo -->
            <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-xl">
                <button
                    type="button"
                    @click="setCategory('profile')"
                    :class="[
                        'py-2 px-3 rounded-lg text-xs font-extrabold transition-all flex items-center justify-center gap-2 cursor-pointer',
                        activeCategory === 'profile'
                            ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
                    ]"
                >
                    <i class="fa-solid fa-building-user text-xs"></i>
                    <span>Página Inicial</span>
                </button>
                <button
                    type="button"
                    @click="setCategory('booking')"
                    :class="[
                        'py-2 px-3 rounded-lg text-xs font-extrabold transition-all flex items-center justify-center gap-2 cursor-pointer',
                        activeCategory === 'booking'
                            ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
                    ]"
                >
                    <i class="fa-solid fa-calendar-days text-xs"></i>
                    <span>Fluxo de Agendamento</span>
                </button>
            </div>

            <!-- Steps Selector (when in booking flow mode) -->
            <div v-if="activeCategory === 'booking'" class="flex items-center gap-1 overflow-x-auto pb-1">
                <button
                    v-for="s in bookingStepNames"
                    :key="s.step"
                    type="button"
                    @click="setBookingStep(s.step)"
                    :class="[
                        'px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-all shrink-0 flex items-center gap-1.5 cursor-pointer',
                        currentBookingStep === s.step
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900'
                    ]"
                >
                    <i :class="s.icon" class="text-[10px]"></i>
                    <span>{{ s.name }}</span>
                </button>
            </div>

            <!-- Page Paginator Buttons (Prev / Next) -->
            <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100 dark:border-slate-800/80">
                <button
                    type="button"
                    @click="prevPage"
                    :disabled="activeCategory === 'profile'"
                    class="px-2.5 py-1 rounded-lg font-bold flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    <span>Anterior</span>
                </button>

                <div class="flex items-center gap-1 text-center">
                    <span class="text-[11px] font-black text-slate-800 dark:text-slate-200">
                        {{ currentPageTitle }}
                    </span>
                    <span class="text-[10px] text-slate-400 font-semibold">
                        ({{ activeCategory === 'profile' ? 'Página Única' : `Passo ${currentBookingStep}/5` }})
                    </span>
                </div>

                <button
                    type="button"
                    @click="nextPage"
                    :disabled="activeCategory === 'booking' && currentBookingStep === 5"
                    class="px-2.5 py-1 rounded-lg font-bold flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    <span>Próxima</span>
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </button>
            </div>
        </div>

        <!-- Simulated Screen Display Frame -->
        <div
            :class="[
                'border rounded-3xl overflow-hidden shadow-2xl transition-all duration-300 min-h-[580px] flex flex-col relative mx-auto',
                previewDevice === 'mobile' ? 'max-w-[400px]' : 'w-full'
            ]"
            :style="{
                backgroundColor: form.background_color,
                color: form.text_color
            }"
        >
            <!-- Simulated Top Menu Header -->
            <header
                class="h-14 border-b flex items-center justify-between px-4 transition-all duration-300 shadow-sm shrink-0 sticky top-0 z-20 backdrop-blur-md"
                :style="{
                    backgroundColor: form.top_menu_color,
                    borderColor: 'rgba(0,0,0,0.06)'
                }"
            >
                <!-- Logo & Brand in Header -->
                <div class="flex items-center gap-2 min-w-0">
                    <template v-if="logoPreview">
                        <div class="h-8 max-w-[120px] flex items-center">
                            <img :src="logoPreview" class="h-full w-auto object-contain" alt="Logo preview" />
                        </div>
                        <span v-if="form.business_name" class="font-extrabold text-xs tracking-tight truncate max-w-[120px]" :style="{ color: form.text_color }">
                            {{ form.business_name }}
                        </span>
                    </template>
                    <template v-else>
                        <div
                            class="w-7 h-7 flex items-center justify-center text-white text-xs font-black shadow-sm"
                            :class="buttonRadiusClass"
                            :style="{ backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }"
                        >
                            <i class="fa-solid fa-calendar-check text-[11px]"></i>
                        </div>
                        <span class="font-black text-xs tracking-tight truncate" :style="{ color: form.text_color }">
                            {{ form.business_name || 'Agendae' }}
                        </span>
                    </template>
                </div>

                <!-- Header Actions: Instagram, WhatsApp -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <div
                        v-if="form.instagram_handle"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-pink-500 shadow-2xs border border-slate-200/60"
                        :style="{ backgroundColor: form.card_bg_color }"
                        title="Instagram"
                    >
                        <i class="fa-brands fa-instagram text-xs"></i>
                    </div>
                    <div
                        v-if="form.whatsapp_number"
                        class="w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600 shadow-2xs"
                        title="WhatsApp"
                    >
                        <i class="fa-brands fa-whatsapp text-xs"></i>
                    </div>
                </div>
            </header>

            <!-- Simulated Body Content Container -->
            <div class="flex-1 p-3.5 sm:p-4 space-y-3.5 overflow-y-auto max-h-[580px]">
                <!-- 1. PÁGINA: PERFIL DA EMPRESA (Landing / Capa) -->
                <BrandingMockupProfileView
                    v-if="activeCategory === 'profile'"
                    :form="form"
                    :banner-preview="bannerPreview"
                    :business-name="businessName"
                    :radius-class="radiusClass"
                    :button-radius-class="buttonRadiusClass"
                    @set-booking-step="setBookingStep"
                />

                <!-- 2. PÁGINA: FLUXO DE AGENDAMENTO (Steps 1 to 5) -->
                <BrandingMockupBookingSteps
                    v-else
                    :form="form"
                    :current-booking-step="currentBookingStep"
                    :radius-class="radiusClass"
                    :button-radius-class="buttonRadiusClass"
                    @set-booking-step="setBookingStep"
                />
            </div>

            <!-- Simulated Footer in Mockup -->
            <footer
                class="h-9 border-t flex items-center justify-center text-[8px] opacity-70 px-2 shrink-0 backdrop-blur-sm"
                :style="{
                    borderColor: 'rgba(0,0,0,0.06)',
                    backgroundColor: form.top_menu_color || form.card_bg_color
                }"
            >
                <span class="truncate">
                    {{ form.footer_text || ('© ' + new Date().getFullYear() + ' ' + businessName + '. Todos os direitos reservados.') }}
                </span>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
