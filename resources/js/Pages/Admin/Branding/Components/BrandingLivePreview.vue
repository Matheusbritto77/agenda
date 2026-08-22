<script setup>
import { ref, computed, watch } from 'vue';

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

// Mock interactive state for preview
const selectedMockProfessional = ref(1);
const selectedMockService = ref(1);
const selectedMockDate = ref('2026-08-25');
const selectedMockTime = ref('14:30');
const mockSelectedCategory = ref('todos');

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
                    <span>Perfil da Empresa</span>
                </button>

                <button
                    type="button"
                    @click="setBookingStep(currentBookingStep || 1)"
                    :class="[
                        'py-2 px-3 rounded-lg text-xs font-extrabold transition-all flex items-center justify-center gap-2 cursor-pointer',
                        activeCategory === 'booking'
                            ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
                    ]"
                >
                    <i class="fa-solid fa-calendar-check text-xs"></i>
                    <span>Fluxo de Agendamento</span>
                </button>
            </div>

            <!-- Specific Step Switcher Pills / Stepper buttons -->
            <div v-if="activeCategory === 'booking'" class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                <button
                    v-for="item in bookingStepNames"
                    :key="item.step"
                    type="button"
                    @click="setBookingStep(item.step)"
                    :class="[
                        'px-2.5 py-1.5 rounded-lg text-[11px] font-bold shrink-0 transition-all flex items-center gap-1.5 cursor-pointer',
                        currentBookingStep === item.step
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
                    ]"
                >
                    <i :class="item.icon" class="text-[10px]"></i>
                    <span>{{ item.step }}. {{ item.name }}</span>
                </button>
            </div>

            <!-- Prev / Next Controller Bar -->
            <div class="flex items-center justify-between border-t pt-2.5 text-xs" style="border-color: var(--border);">
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

                <!-- ============================================================= -->
                <!-- 1. PÁGINA: PERFIL DA EMPRESA (Landing / Capa)                 -->
                <!-- ============================================================= -->
                <div v-if="activeCategory === 'profile'" class="space-y-3 animate-fade-in">
                    <!-- Banner Hero Cover -->
                    <div
                        class="h-32 w-full overflow-hidden shadow-sm relative border border-slate-200/40"
                        :class="radiusClass"
                    >
                        <img
                            v-if="bannerPreview"
                            :src="bannerPreview"
                            class="w-full h-full object-cover"
                            alt="Banner da empresa"
                        />
                        <div v-else class="w-full h-full bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 flex items-center justify-center">
                            <i class="fa-solid fa-store text-3xl text-white/20"></i>
                        </div>

                        <!-- Gradient Overlay with Business Details -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/35 to-transparent flex flex-col justify-end p-3 text-white">
                            <div class="flex items-center gap-2">
                                <span class="text-[8px] px-2 py-0.5 rounded-full bg-emerald-500/90 text-white font-black tracking-wider uppercase flex items-center gap-1">
                                    <i class="fa-solid fa-circle text-[5px] animate-pulse"></i>
                                    Aberto Agora
                                </span>
                            </div>
                            <h3 class="font-black text-sm drop-shadow mt-1 leading-tight">{{ businessName }}</h3>
                            <p v-if="form.tagline" class="text-[10px] text-white/80 font-medium line-clamp-1 drop-shadow">
                                {{ form.tagline }}
                            </p>
                        </div>
                    </div>

                    <!-- Sobre a Empresa Card -->
                    <div
                        class="p-3.5 border border-slate-200/60 shadow-xs space-y-2"
                        :class="radiusClass"
                        :style="{ backgroundColor: form.card_bg_color }"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] font-black uppercase tracking-wider opacity-60" :style="{ color: form.text_color }">
                                <i class="fa-solid fa-circle-info mr-1" :style="{ color: form.primary_color }"></i>
                                Sobre a Empresa
                            </span>
                            <span class="text-[9px] font-bold opacity-75" :style="{ color: form.text_color }">
                                ⭐ 4.9 (128 avaliações)
                            </span>
                        </div>

                        <p class="text-[11px] leading-relaxed opacity-85" :style="{ color: form.text_color }">
                            {{ form.company_profile_description || form.tagline || 'Somos um espaço especializado em atendimento de excelência, com profissionais experientes e horário marcado para sua comodidade.' }}
                        </p>

                        <!-- Address if present -->
                        <div v-if="form.company_address" class="flex items-start gap-1.5 text-[10px] opacity-75 pt-1">
                            <i class="fa-solid fa-location-dot mt-0.5 text-indigo-500"></i>
                            <span>{{ form.company_address }}</span>
                        </div>

                        <!-- CTA Button to Start Booking -->
                        <div class="pt-1">
                            <button
                                type="button"
                                @click="setBookingStep(1)"
                                class="w-full py-2.5 px-4 font-black text-xs shadow-md flex items-center justify-center gap-2 transition-all cursor-pointer hover:opacity-90"
                                :class="buttonRadiusClass"
                                :style="{
                                    backgroundColor: form.primary_color,
                                    color: form.button_text_color || '#ffffff'
                                }"
                            >
                                <i class="fa-solid fa-calendar-check text-xs"></i>
                                <span>{{ form.company_profile_cta_label || 'Agendar agora' }}</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Company Profile Features (Horários, Serviços, Equipe) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <!-- Horários Box -->
                        <div
                            v-if="form.company_profile_show_hours"
                            class="p-3 border border-slate-200/60 shadow-xs flex items-center gap-2.5"
                            :class="radiusClass"
                            :style="{ backgroundColor: form.card_bg_color }"
                        >
                            <div
                                class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                                :style="{ backgroundColor: form.primary_color + '18', color: form.primary_color }"
                            >
                                <i class="fa-solid fa-clock text-xs"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] font-black uppercase opacity-60 leading-none" :style="{ color: form.text_color }">Horários</p>
                                <p class="text-[10px] font-extrabold mt-0.5 truncate" :style="{ color: form.text_color }">08:00 às 19:00</p>
                            </div>
                        </div>

                        <!-- Serviços Box -->
                        <div
                            v-if="form.company_profile_show_services"
                            class="p-3 border border-slate-200/60 shadow-xs flex items-center gap-2.5"
                            :class="radiusClass"
                            :style="{ backgroundColor: form.card_bg_color }"
                        >
                            <div
                                class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                                :style="{ backgroundColor: form.primary_color + '18', color: form.primary_color }"
                            >
                                <i class="fa-solid fa-scissors text-xs"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] font-black uppercase opacity-60 leading-none" :style="{ color: form.text_color }">Serviços</p>
                                <p class="text-[10px] font-extrabold mt-0.5 truncate" :style="{ color: form.text_color }">12 Disponíveis</p>
                            </div>
                        </div>

                        <!-- Equipe Box -->
                        <div
                            v-if="form.company_profile_show_professionals"
                            class="p-3 border border-slate-200/60 shadow-xs flex items-center gap-2.5"
                            :class="radiusClass"
                            :style="{ backgroundColor: form.card_bg_color }"
                        >
                            <div
                                class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                                :style="{ backgroundColor: form.primary_color + '18', color: form.primary_color }"
                            >
                                <i class="fa-solid fa-users text-xs"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] font-black uppercase opacity-60 leading-none" :style="{ color: form.text_color }">Equipe</p>
                                <p class="text-[10px] font-extrabold mt-0.5 truncate" :style="{ color: form.text_color }">Especialistas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Services Highlights List -->
                    <div
                        v-if="form.company_profile_show_services"
                        class="p-3.5 border border-slate-200/60 shadow-xs space-y-2.5"
                        :class="radiusClass"
                        :style="{ backgroundColor: form.card_bg_color }"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider opacity-70" :style="{ color: form.text_color }">
                                Serviços em Destaque
                            </span>
                            <button
                                type="button"
                                @click="setBookingStep(2)"
                                class="text-[10px] font-extrabold flex items-center gap-1 hover:underline cursor-pointer"
                                :style="{ color: form.primary_color }"
                            >
                                <span>Ver todos</span>
                                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                            </button>
                        </div>

                        <div class="space-y-1.5">
                            <div
                                class="p-2 rounded-xl border border-slate-200/40 flex items-center justify-between gap-2 cursor-pointer transition-all hover:bg-slate-50/50"
                                @click="setBookingStep(3)"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 text-[10px] shrink-0">
                                        <i class="fa-solid fa-scissors"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-bold truncate" :style="{ color: form.text_color }">Corte Degradê & Barba</p>
                                        <span class="text-[9px] opacity-60">45 min</span>
                                    </div>
                                </div>
                                <span class="text-[11px] font-black shrink-0" :style="{ color: form.primary_color }">R$ 65,00</span>
                            </div>

                            <div
                                class="p-2 rounded-xl border border-slate-200/40 flex items-center justify-between gap-2 cursor-pointer transition-all hover:bg-slate-50/50"
                                @click="setBookingStep(3)"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 text-[10px] shrink-0">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-bold truncate" :style="{ color: form.text_color }">Tratamento Completo</p>
                                        <span class="text-[9px] opacity-60">60 min</span>
                                    </div>
                                </div>
                                <span class="text-[11px] font-black shrink-0" :style="{ color: form.primary_color }">R$ 90,00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews & Comments Live Preview Section -->
                    <div
                        v-if="form.company_profile_show_reviews"
                        class="p-3.5 border border-slate-200/60 shadow-xs space-y-3"
                        :class="radiusClass"
                        :style="{ backgroundColor: form.card_bg_color }"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <span class="text-[9px] font-black uppercase tracking-wider opacity-60 block leading-none" :style="{ color: form.text_color }">
                                    Experiências Reais
                                </span>
                                <h6 class="text-[11px] font-black mt-0.5 truncate" :style="{ color: form.text_color }">
                                    {{ form.company_profile_reviews_title || 'O que os clientes dizem' }}
                                </h6>
                            </div>
                            <div
                                class="px-2 py-1 rounded-lg flex items-center gap-1.5 shrink-0"
                                :style="{ backgroundColor: form.primary_color + '18' }"
                            >
                                <span class="text-xs font-black" :style="{ color: form.primary_color }">5.0</span>
                                <div class="flex gap-0.5 text-amber-400 text-[8px]">
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Review Card -->
                        <div
                            class="p-2.5 rounded-xl border border-slate-200/40 space-y-1.5 shadow-2xs"
                            :style="{
                                backgroundColor: form.card_bg_color && form.card_bg_color !== '#ffffff' ? 'rgba(255, 255, 255, 0.05)' : '#f8fafc',
                                color: form.text_color
                            }"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex gap-0.5 text-amber-400 text-[9px]">
                                    <i v-for="s in 5" :key="s" class="fa-solid fa-star"></i>
                                </div>
                                <span class="text-[8px] opacity-60">Hoje</span>
                            </div>
                            <p class="text-[10px] italic leading-tight opacity-90">
                                "Atendimento impecável! Profissionais pontuais e ambiente nota 10."
                            </p>
                            <div class="pt-1 border-t border-slate-200/30 flex items-center justify-between text-[9px]">
                                <span class="font-bold">Gabriel S.</span>
                                <span class="opacity-60">Corte Degradê</span>
                            </div>
                        </div>

                        <!-- Client portal link invitation -->
                        <div class="pt-1 flex items-center justify-between text-[8px] opacity-70">
                            <span>Avaliações verificadas de clientes reais.</span>
                            <span class="font-bold underline" :style="{ color: form.primary_color }">Área do Cliente →</span>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- 2. PÁGINA: FLUXO DE AGENDAMENTO (Steps 1 to 5)                -->
                <!-- ============================================================= -->
                <div v-else class="space-y-3 animate-fade-in">
                    <!-- Booking Stepper Progress Indicator -->
                    <div
                        class="p-2.5 border border-slate-200/60 shadow-xs"
                        :class="radiusClass"
                        :style="{ backgroundColor: form.card_bg_color }"
                    >
                        <div class="flex items-center justify-between gap-1 text-center">
                            <div
                                v-for="item in [
                                    { step: 1, label: 'Profissional' },
                                    { step: 2, label: 'Serviço' },
                                    { step: 3, label: 'Horário' },
                                    { step: 4, label: 'Confirmar' }
                                ]"
                                :key="item.step"
                                class="flex-1 flex flex-col items-center gap-1 cursor-pointer"
                                @click="setBookingStep(item.step)"
                            >
                                <div
                                    class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-extrabold transition-all"
                                    :style="[
                                        currentBookingStep >= item.step
                                            ? { backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }
                                            : { backgroundColor: 'rgba(0,0,0,0.06)', color: form.text_color }
                                    ]"
                                >
                                    <i v-if="currentBookingStep > item.step" class="fa-solid fa-check text-[9px]"></i>
                                    <span v-else>{{ item.step }}</span>
                                </div>
                                <span class="text-[8px] font-bold truncate max-w-[60px]" :style="{ color: form.text_color }">
                                    {{ item.label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 1: PROFISSIONAL -->
                    <div v-if="currentBookingStep === 1" class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h5 class="text-xs font-black" :style="{ color: form.text_color }">
                                    {{ form.booking_step_professional_title || '1. Escolha o Profissional' }}
                                </h5>
                                <p class="text-[10px] opacity-70 leading-tight">
                                    {{ form.booking_step_professional_subtitle || 'Selecione quem irá lhe atender' }}
                                </p>
                            </div>
                            <span class="text-[9px] opacity-65 shrink-0">Passo 1/4</span>
                        </div>

                        <!-- Professional Option Cards -->
                        <div class="space-y-2">
                            <!-- Card: Qualquer Profissional (if allowed) -->
                            <div
                                v-if="form.booking_step_professional_allow_any !== false"
                                @click="selectedMockProfessional = 0; setBookingStep(2)"
                                class="p-3 border transition-all cursor-pointer flex items-center justify-between gap-2 shadow-xs"
                                :class="[
                                    radiusClass,
                                    selectedMockProfessional === 0 ? 'border-2 ring-1' : 'border-slate-200/60'
                                ]"
                                :style="{
                                    backgroundColor: form.card_bg_color,
                                    borderColor: selectedMockProfessional === 0 ? form.primary_color : 'rgba(0,0,0,0.08)'
                                }"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold"
                                        :style="{ backgroundColor: form.primary_color + '20', color: form.primary_color }"
                                    >
                                        <i class="fa-solid fa-user-group"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold" :style="{ color: form.text_color }">Qualquer Profissional</p>
                                        <span class="text-[9px] opacity-65">Primeiro horário disponível</span>
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right text-xs opacity-40"></i>
                            </div>

                            <!-- Card: Profissional 1 -->
                            <div
                                @click="selectedMockProfessional = 1; setBookingStep(2)"
                                class="p-3 border transition-all cursor-pointer flex items-center justify-between gap-2 shadow-xs"
                                :class="[
                                    radiusClass,
                                    selectedMockProfessional === 1 ? 'border-2 ring-1' : 'border-slate-200/60'
                                ]"
                                :style="{
                                    backgroundColor: form.card_bg_color,
                                    borderColor: selectedMockProfessional === 1 ? form.primary_color : 'rgba(0,0,0,0.08)'
                                }"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-cyan-500 text-white flex items-center justify-center text-xs font-extrabold shadow-xs">
                                        CS
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <p class="text-xs font-bold" :style="{ color: form.text_color }">Carlos Santos</p>
                                            <span class="text-[8px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-600 font-bold">Disponível</span>
                                        </div>
                                        <span class="text-[9px] opacity-65">Barbeiro & Visagista • ⭐ 4.9</span>
                                    </div>
                                </div>
                                <div
                                    class="w-5 h-5 rounded-full flex items-center justify-center text-white text-[9px]"
                                    :style="{ backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </div>
                            </div>

                            <!-- Card: Profissional 2 -->
                            <div
                                @click="selectedMockProfessional = 2; setBookingStep(2)"
                                class="p-3 border border-slate-200/60 transition-all cursor-pointer flex items-center justify-between gap-2 shadow-xs"
                                :class="radiusClass"
                                :style="{ backgroundColor: form.card_bg_color }"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-pink-500 to-rose-400 text-white flex items-center justify-center text-xs font-extrabold shadow-xs">
                                        AC
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <p class="text-xs font-bold" :style="{ color: form.text_color }">Ana Costa</p>
                                            <span class="text-[8px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-bold">Especialista</span>
                                        </div>
                                        <span class="text-[9px] opacity-65">Esteticista & Barba • ⭐ 5.0</span>
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right text-xs opacity-40"></i>
                            </div>
                        </div>

                        <!-- Continue CTA -->
                        <div class="pt-2 flex justify-end">
                            <button
                                type="button"
                                @click="setBookingStep(2)"
                                class="py-2 px-5 font-bold text-xs shadow-md flex items-center gap-1.5 cursor-pointer"
                                :class="buttonRadiusClass"
                                :style="{ backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }"
                            >
                                <span>Continuar</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: SERVIÇOS -->
                    <div v-else-if="currentBookingStep === 2" class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h5 class="text-xs font-black" :style="{ color: form.text_color }">
                                    {{ form.booking_step_service_title || '2. Escolha o Serviço' }}
                                </h5>
                                <p class="text-[10px] opacity-70 leading-tight">
                                    {{ form.booking_step_service_subtitle || 'Selecione os procedimentos desejados' }}
                                </p>
                            </div>
                            <span class="text-[9px] opacity-65 shrink-0">Passo 2/4</span>
                        </div>

                        <!-- Search Bar if enabled -->
                        <div
                            v-if="form.booking_step_service_search_enabled !== false"
                            class="h-8 px-2.5 rounded-xl border border-slate-200/80 bg-white/70 flex items-center gap-2 text-[10px] text-slate-400"
                        >
                            <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                            <span>Buscar procedimento...</span>
                        </div>

                        <!-- Category Filter Chips -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                            <button
                                type="button"
                                @click="mockSelectedCategory = 'todos'"
                                :class="[
                                    'px-2.5 py-1 text-[10px] font-bold rounded-lg transition-all cursor-pointer',
                                    mockSelectedCategory === 'todos' ? 'text-white shadow-xs' : 'bg-slate-100 text-slate-600'
                                ]"
                                :style="mockSelectedCategory === 'todos' ? { backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' } : {}"
                            >
                                Todos
                            </button>
                            <button
                                type="button"
                                @click="mockSelectedCategory = 'cortes'"
                                :class="[
                                    'px-2.5 py-1 text-[10px] font-bold rounded-lg transition-all cursor-pointer',
                                    mockSelectedCategory === 'cortes' ? 'text-white shadow-xs' : 'bg-slate-100 text-slate-600'
                                ]"
                                :style="mockSelectedCategory === 'cortes' ? { backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' } : {}"
                            >
                                Cortes
                            </button>
                            <button
                                type="button"
                                @click="mockSelectedCategory = 'barba'"
                                :class="[
                                    'px-2.5 py-1 text-[10px] font-bold rounded-lg transition-all cursor-pointer',
                                    mockSelectedCategory === 'barba' ? 'text-white shadow-xs' : 'bg-slate-100 text-slate-600'
                                ]"
                                :style="mockSelectedCategory === 'barba' ? { backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' } : {}"
                            >
                                Barba & Tratamentos
                            </button>
                        </div>

                        <!-- Services List Cards -->
                        <div class="space-y-2">
                            <!-- Service 1 -->
                            <div
                                @click="selectedMockService = 1"
                                class="p-3 border transition-all cursor-pointer flex items-center justify-between gap-2 shadow-xs"
                                :class="[
                                    radiusClass,
                                    selectedMockService === 1 ? 'border-2' : 'border-slate-200/60'
                                ]"
                                :style="{
                                    backgroundColor: form.card_bg_color,
                                    borderColor: selectedMockService === 1 ? form.primary_color : 'rgba(0,0,0,0.08)'
                                }"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div
                                        class="w-8 h-8 rounded-xl flex items-center justify-center text-xs shrink-0"
                                        :style="{ backgroundColor: form.primary_color + '20', color: form.primary_color }"
                                    >
                                        <i class="fa-solid fa-scissors"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold truncate" :style="{ color: form.text_color }">Corte Masculino Degradê</p>
                                        <span class="text-[9px] opacity-70"><i class="fa-regular fa-clock mr-1"></i>30 min</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-xs font-black block" :style="{ color: form.primary_color }">R$ 45,00</span>
                                    <span v-if="selectedMockService === 1" class="text-[8px] font-bold px-1.5 py-0.5 rounded-md text-white" :style="{ backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }">Selecionado</span>
                                </div>
                            </div>

                            <!-- Service 2 -->
                            <div
                                @click="selectedMockService = 2"
                                class="p-3 border transition-all cursor-pointer flex items-center justify-between gap-2 shadow-xs"
                                :class="[
                                    radiusClass,
                                    selectedMockService === 2 ? 'border-2' : 'border-slate-200/60'
                                ]"
                                :style="{
                                    backgroundColor: form.card_bg_color,
                                    borderColor: selectedMockService === 2 ? form.primary_color : 'rgba(0,0,0,0.08)'
                                }"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div
                                        class="w-8 h-8 rounded-xl flex items-center justify-center text-xs shrink-0"
                                        :style="{ backgroundColor: form.primary_color + '20', color: form.primary_color }"
                                    >
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold truncate" :style="{ color: form.text_color }">Corte + Barboterapia Completa</p>
                                        <span class="text-[9px] opacity-70"><i class="fa-regular fa-clock mr-1"></i>55 min</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-xs font-black block" :style="{ color: form.primary_color }">R$ 80,00</span>
                                    <span v-if="selectedMockService === 2" class="text-[8px] font-bold px-1.5 py-0.5 rounded-md text-white" :style="{ backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }">Selecionado</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stepper Actions -->
                        <div class="pt-2 flex items-center justify-between">
                            <button
                                type="button"
                                @click="setBookingStep(1)"
                                class="text-[11px] font-bold opacity-75 hover:opacity-100 cursor-pointer"
                                :style="{ color: form.text_color }"
                            >
                                <i class="fa-solid fa-chevron-left mr-1 text-[9px]"></i> Voltar
                            </button>
                            <button
                                type="button"
                                @click="setBookingStep(3)"
                                class="py-2 px-5 font-bold text-xs shadow-md flex items-center gap-1.5 cursor-pointer"
                                :class="buttonRadiusClass"
                                :style="{ backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }"
                            >
                                <span>Continuar</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: DATA E HORÁRIO -->
                    <div v-else-if="currentBookingStep === 3" class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h5 class="text-xs font-black" :style="{ color: form.text_color }">
                                    {{ form.booking_step_datetime_title || '3. Escolha Data e Horário' }}
                                </h5>
                                <p class="text-[10px] opacity-70 leading-tight">
                                    {{ form.booking_step_datetime_subtitle || 'Selecione o melhor dia e horário disponível' }}
                                </p>
                            </div>
                            <span class="text-[9px] opacity-65 shrink-0">Passo 3/4</span>
                        </div>

                        <!-- Date Selection Strip -->
                        <div
                            class="p-2.5 border border-slate-200/60 shadow-xs space-y-2"
                            :class="radiusClass"
                            :style="{ backgroundColor: form.card_bg_color }"
                        >
                            <div class="flex items-center justify-between text-xs font-bold" :style="{ color: form.text_color }">
                                <span>Agosto 2026</span>
                                <div class="flex gap-1 text-[10px]">
                                    <span class="w-5 h-5 rounded-md bg-slate-100 flex items-center justify-center cursor-pointer">&lt;</span>
                                    <span class="w-5 h-5 rounded-md bg-slate-100 flex items-center justify-center cursor-pointer">&gt;</span>
                                </div>
                            </div>

                            <!-- Day Pills -->
                            <div class="grid grid-cols-5 gap-1 text-center">
                                <div
                                    v-for="d in [
                                        { day: 'Ter', num: '25', val: '2026-08-25' },
                                        { day: 'Qua', num: '26', val: '2026-08-26' },
                                        { day: 'Qui', num: '27', val: '2026-08-27' },
                                        { day: 'Sex', num: '28', val: '2026-08-28' },
                                        { day: 'Sáb', num: '29', val: '2026-08-29' }
                                    ]"
                                    :key="d.val"
                                    @click="selectedMockDate = d.val"
                                    class="p-1.5 rounded-xl cursor-pointer transition-all flex flex-col items-center"
                                    :style="[
                                        selectedMockDate === d.val
                                            ? { backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }
                                            : { backgroundColor: 'rgba(0,0,0,0.03)', color: form.text_color }
                                    ]"
                                >
                                    <span class="text-[8px] uppercase font-bold opacity-75">{{ d.day }}</span>
                                    <span class="text-xs font-black">{{ d.num }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Available Time Slots -->
                        <div
                            class="p-2.5 border border-slate-200/60 shadow-xs space-y-2"
                            :class="radiusClass"
                            :style="{ backgroundColor: form.card_bg_color }"
                        >
                            <span class="text-[9px] font-black uppercase opacity-65 block" :style="{ color: form.text_color }">
                                Horários Disponíveis
                            </span>

                            <div class="grid grid-cols-3 gap-1.5">
                                <button
                                    v-for="t in ['09:00', '10:30', '14:30', '15:15', '16:00', '17:30']"
                                    :key="t"
                                    type="button"
                                    @click="selectedMockTime = t"
                                    class="py-1.5 px-2 rounded-lg text-[10px] font-extrabold transition-all cursor-pointer text-center"
                                    :style="[
                                        selectedMockTime === t
                                            ? { backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }
                                            : { backgroundColor: 'rgba(0,0,0,0.04)', color: form.text_color }
                                    ]"
                                >
                                    {{ t }}
                                </button>
                            </div>
                        </div>

                        <!-- Stepper Actions -->
                        <div class="pt-2 flex items-center justify-between">
                            <button
                                type="button"
                                @click="setBookingStep(2)"
                                class="text-[11px] font-bold opacity-75 hover:opacity-100 cursor-pointer"
                                :style="{ color: form.text_color }"
                            >
                                <i class="fa-solid fa-chevron-left mr-1 text-[9px]"></i> Voltar
                            </button>
                            <button
                                type="button"
                                @click="setBookingStep(4)"
                                class="py-2 px-5 font-bold text-xs shadow-md flex items-center gap-1.5 cursor-pointer"
                                :class="buttonRadiusClass"
                                :style="{ backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }"
                            >
                                <span>Continuar</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 4: IDENTIFICAÇÃO E CONFIRMAÇÃO -->
                    <div v-else-if="currentBookingStep === 4" class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h5 class="text-xs font-black" :style="{ color: form.text_color }">
                                    {{ form.booking_step_confirm_title || '4. Dados & Confirmação' }}
                                </h5>
                                <p class="text-[10px] opacity-70 leading-tight">Revise os detalhes antes de concluir</p>
                            </div>
                            <span class="text-[9px] opacity-65 shrink-0">Passo 4/4</span>
                        </div>

                        <!-- Appointment Summary Card -->
                        <div
                            class="p-3 border border-slate-200/60 shadow-xs space-y-2"
                            :class="radiusClass"
                            :style="{ backgroundColor: form.card_bg_color }"
                        >
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <div>
                                    <p class="text-xs font-black" :style="{ color: form.text_color }">Corte Masculino Degradê</p>
                                    <span class="text-[9px] opacity-70">Profissional: Carlos Santos</span>
                                </div>
                                <span class="text-xs font-black" :style="{ color: form.primary_color }">R$ 45,00</span>
                            </div>
                            <div class="flex items-center gap-3 text-[10px] opacity-80" :style="{ color: form.text_color }">
                                <span><i class="fa-regular fa-calendar mr-1"></i>25/08/2026</span>
                                <span><i class="fa-regular fa-clock mr-1"></i>14:30</span>
                            </div>
                        </div>

                        <!-- Client Information Inputs Mockup -->
                        <div
                            class="p-3 border border-slate-200/60 shadow-xs space-y-2"
                            :class="radiusClass"
                            :style="{ backgroundColor: form.card_bg_color }"
                        >
                            <div>
                                <label class="text-[9px] font-bold uppercase opacity-70 block mb-0.5" :style="{ color: form.text_color }">Seu Nome</label>
                                <div class="h-8 px-2.5 rounded-lg border border-slate-200/80 bg-slate-50/50 flex items-center text-[11px] text-slate-600">
                                    Matheus Brito
                                </div>
                            </div>

                            <div>
                                <label class="text-[9px] font-bold uppercase opacity-70 block mb-0.5" :style="{ color: form.text_color }">WhatsApp / Telefone</label>
                                <div class="h-8 px-2.5 rounded-lg border border-slate-200/80 bg-slate-50/50 flex items-center text-[11px] text-slate-600">
                                    (11) 98765-4321
                                </div>
                            </div>

                            <div v-if="form.booking_step_confirm_show_notes !== false">
                                <label class="text-[9px] font-bold uppercase opacity-70 block mb-0.5" :style="{ color: form.text_color }">Observações (Opcional)</label>
                                <div class="h-8 px-2.5 rounded-lg border border-slate-200/80 bg-slate-50/50 flex items-center text-[10px] text-slate-400">
                                    Ex: Preferência por barba aparada baixa...
                                </div>
                            </div>
                        </div>

                        <!-- Confirm Button -->
                        <div class="pt-1 flex items-center justify-between">
                            <button
                                type="button"
                                @click="setBookingStep(3)"
                                class="text-[11px] font-bold opacity-75 hover:opacity-100 cursor-pointer"
                                :style="{ color: form.text_color }"
                            >
                                <i class="fa-solid fa-chevron-left mr-1 text-[9px]"></i> Voltar
                            </button>
                            <button
                                type="button"
                                @click="setBookingStep(5)"
                                class="py-2.5 px-5 font-black text-xs shadow-lg flex items-center gap-2 cursor-pointer hover:opacity-95"
                                :class="buttonRadiusClass"
                                :style="{ backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }"
                            >
                                <i class="fa-solid fa-check text-xs"></i>
                                <span>{{ form.booking_step_confirm_button_label || 'Confirmar Agendamento' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 5: SUCESSO / CONCLUSÃO -->
                    <div v-else-if="currentBookingStep === 5" class="space-y-3">
                        <div
                            class="p-4 border border-slate-200/60 shadow-md text-center space-y-2.5"
                            :class="radiusClass"
                            :style="{ backgroundColor: form.card_bg_color }"
                        >
                            <!-- Animated checkmark icon -->
                            <div
                                class="w-12 h-12 rounded-full mx-auto flex items-center justify-center text-lg shadow-md animate-bounce"
                                :style="{ backgroundColor: form.primary_color, color: form.button_text_color || '#ffffff' }"
                            >
                                <i class="fa-solid fa-circle-check text-xl"></i>
                            </div>

                            <h4 class="text-sm font-black" :style="{ color: form.text_color }">
                                {{ form.booking_step_success_title || 'Agendamento Confirmado!' }}
                            </h4>
                            <p class="text-[10px] opacity-75 leading-relaxed" :style="{ color: form.text_color }">
                                {{ form.booking_step_success_message || 'Um lembrete com os detalhes foi enviado para o seu WhatsApp.' }}
                            </p>

                            <!-- Voucher Details Ticket -->
                            <div class="p-2.5 rounded-xl border border-dashed border-slate-300 bg-slate-50/70 text-left space-y-1.5">
                                <div class="flex items-center justify-between text-[10px] font-black">
                                    <span class="text-slate-500 uppercase">Protocolo</span>
                                    <span :style="{ color: form.primary_color }">#AG-2026-8841</span>
                                </div>
                                <div class="text-[10px] text-slate-700 font-semibold space-y-0.5">
                                    <p>📅 25 de Agosto de 2026 às 14:30</p>
                                    <p>✂️ Corte Masculino Degradê (R$ 45,00)</p>
                                    <p>👤 Profissional: Carlos Santos</p>
                                    <p v-if="form.company_address">📍 {{ form.company_address }}</p>
                                </div>
                            </div>

                            <!-- Actions: Add to Calendar & WhatsApp -->
                            <div class="space-y-1.5 pt-1">
                                <div
                                    class="w-full py-2 px-3 rounded-xl font-bold text-[10px] bg-emerald-500 text-white flex items-center justify-center gap-1.5 shadow-xs cursor-pointer"
                                >
                                    <i class="fa-brands fa-whatsapp text-xs"></i>
                                    <span>{{ form.booking_step_success_whatsapp_label || 'Conversar no WhatsApp' }}</span>
                                </div>

                                <button
                                    type="button"
                                    @click="setBookingStep(1)"
                                    class="w-full py-1.5 px-3 rounded-xl font-bold text-[10px] border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer"
                                >
                                    Fazer Novo Agendamento
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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
