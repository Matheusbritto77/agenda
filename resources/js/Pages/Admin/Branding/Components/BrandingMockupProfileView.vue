<script setup>
defineProps({
    form: {
        type: Object,
        required: true,
    },
    bannerPreview: {
        type: String,
        default: null,
    },
    businessName: {
        type: String,
        required: true,
    },
    radiusClass: {
        type: String,
        required: true,
    },
    buttonRadiusClass: {
        type: String,
        required: true,
    },
});

defineEmits(['set-booking-step']);
</script>

<template>
    <div class="space-y-3 animate-fade-in">
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
                    @click="$emit('set-booking-step', 1)"
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
                    @click="$emit('set-booking-step', 2)"
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
                    @click="$emit('set-booking-step', 3)"
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
                    @click="$emit('set-booking-step', 3)"
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
                <div class="pt-1.5 border-t border-slate-200/30 flex items-center gap-2 text-[9px]">
                    <div
                        class="w-5 h-5 rounded-full overflow-hidden shrink-0 flex items-center justify-center text-[7px] font-black text-white"
                        :style="{ background: form.primary_color }"
                    >
                        <span>GS</span>
                    </div>
                    <div class="min-w-0 flex-1 flex items-center justify-between">
                        <span class="font-bold truncate">Gabriel S.</span>
                        <span class="opacity-60 text-[8px]">Corte Degradê</span>
                    </div>
                </div>
            </div>

            <!-- Client portal link invitation -->
            <div class="pt-1 flex items-center justify-between text-[8px] opacity-70">
                <span>Avaliações verificadas de clientes reais.</span>
                <span class="font-bold underline" :style="{ color: form.primary_color }">Área do Cliente →</span>
            </div>
        </div>
    </div>
</template>
