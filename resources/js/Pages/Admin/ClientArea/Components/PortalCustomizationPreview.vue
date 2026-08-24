<script setup>
import { ref } from 'vue';

const props = defineProps({
    customForm: {
        type: Object,
        required: true,
    },
    portalCustomization: {
        type: Object,
        default: () => ({}),
    },
    logoPreview: {
        type: String,
        default: null,
    },
    bannerPreview: {
        type: String,
        default: null,
    },
});

const previewTab = ref('appointments');
const previewDevice = ref('mobile');
const companyName = props.portalCustomization?.company_name || 'Minha Empresa';
</script>

<template>
    <div class="sticky top-20 space-y-3">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <span class="text-xs font-black uppercase tracking-wider opacity-60 flex items-center gap-1.5">
                <i class="fa-solid fa-eye text-cyan-500"></i>
                Pré-visualização em Tempo Real
            </span>
            <div class="flex items-center gap-2">
                <!-- Device toggle -->
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
                    >
                        <i class="fa-solid fa-mobile-screen-button"></i>
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
                    >
                        <i class="fa-solid fa-laptop"></i>
                    </button>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                    <i class="fa-solid fa-circle text-[5px] animate-pulse mr-1"></i>Ao Vivo
                </span>
            </div>
        </div>

        <!-- Mockup Frame -->
        <div
            :class="[
                'rounded-3xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 shadow-2xl overflow-hidden transition-all duration-300 mx-auto',
                previewDevice === 'mobile' ? 'max-w-[400px]' : 'w-full'
            ]"
        >
            <!-- Phone Top Notch -->
            <div class="h-6 bg-slate-900 flex items-center justify-center">
                <div class="w-20 h-3 bg-slate-800 rounded-full"></div>
            </div>

            <!-- Mock Layout Header (ClientPortalLayout mimic) -->
            <header class="h-12 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between px-3">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 flex items-center justify-center shrink-0 shadow-2xs" :style="{ backgroundColor: customForm.portal_primary_color }">
                        <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                        <i v-else class="fa-solid fa-store text-white text-[10px]"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-black truncate text-slate-900 dark:text-white leading-none">{{ companyName }}</p>
                        <span class="text-[8px] font-bold text-slate-400 leading-none">Área do Cliente</span>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="px-1.5 py-0.5 rounded-full text-[8px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-cyan-400">Cliente VIP</span>
                    <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-indigo-500 to-cyan-500 text-white flex items-center justify-center text-[9px] font-black">M</div>
                </div>
            </header>

            <!-- Scrollable Body -->
            <div class="overflow-y-auto max-h-[560px] bg-slate-50 dark:bg-slate-950">
                <div class="p-3 space-y-3">

                    <!-- Hero Banner Preview (branded company space) -->
                    <div
                        class="rounded-2xl p-4 text-white space-y-3 shadow-lg relative overflow-hidden"
                        :style="{
                            background: bannerPreview
                                ? '#0b0f19'
                                : `linear-gradient(135deg, ${customForm.portal_primary_color} 0%, ${customForm.portal_secondary_color} 100%)`
                        }"
                    >
                        <!-- Banner image overlay if present -->
                        <div v-if="bannerPreview" class="absolute inset-0 z-0">
                            <img :src="bannerPreview" class="w-full h-full object-cover" />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/60 to-slate-950/40"></div>
                        </div>

                        <div class="relative z-10 flex items-start gap-3">
                            <div class="w-11 h-11 rounded-xl bg-white text-slate-900 flex items-center justify-center font-black text-sm shrink-0 overflow-hidden shadow-md">
                                <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                                <i v-else class="fa-solid fa-store text-xs" :style="{ color: customForm.portal_primary_color }"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="px-2 py-0.5 rounded-full text-[7px] font-black uppercase bg-black/25 text-white/95 inline-block mb-1">Espaço Exclusivo</span>
                                <h4 class="font-black text-sm leading-tight truncate">
                                    {{ customForm.portal_welcome_title || companyName }}
                                </h4>
                                <p class="text-[10px] text-white/85 truncate mt-0.5">
                                    {{ customForm.portal_welcome_subtitle || 'Acompanhe seus horários e conquistas.' }}
                                </p>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="relative z-10 flex flex-wrap items-center gap-1.5">
                            <span class="px-2 py-1 rounded-lg text-[9px] font-black bg-white text-slate-900 shadow-2xs flex items-center gap-1">
                                <i class="fa-solid fa-calendar-plus text-[8px]" :style="{ color: customForm.portal_primary_color }"></i>
                                Agendar
                            </span>
                            <span v-if="customForm.portal_support_whatsapp" class="px-2 py-1 rounded-lg text-[9px] font-black bg-emerald-600 text-white shadow-2xs flex items-center gap-1">
                                <i class="fa-brands fa-whatsapp text-[9px]"></i>
                                WhatsApp
                            </span>
                            <span v-if="customForm.portal_show_reviews" class="px-2 py-1 rounded-lg text-[9px] font-black bg-black/25 text-amber-300 flex items-center gap-1">
                                <i class="fa-solid fa-star text-[8px]"></i>
                                Avaliar
                            </span>
                        </div>

                        <!-- Custom instructions -->
                        <p v-if="customForm.portal_custom_instructions" class="relative z-10 text-[9px] text-white/80 border-t border-white/15 pt-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-info text-[8px]"></i>
                            <span class="truncate">{{ customForm.portal_custom_instructions }}</span>
                        </p>
                    </div>

                    <!-- Announcement Bar -->
                    <div
                        v-if="customForm.portal_announcement_enabled && customForm.portal_announcement"
                        class="p-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300 text-[10px] font-bold flex items-center gap-2"
                    >
                        <i class="fa-solid fa-bullhorn text-xs shrink-0"></i>
                        <span class="truncate">{{ customForm.portal_announcement }}</span>
                    </div>

                    <!-- Profile Stats Row -->
                    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 shadow-xs">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-cyan-500 text-white flex items-center justify-center font-black text-sm shadow-md shrink-0">M</div>
                            <div>
                                <h3 class="text-xs font-black text-slate-900 dark:text-white">Matheus</h3>
                                <span class="text-[9px] text-slate-400">Gerencie seus agendamentos exclusivos</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-1.5">
                            <div class="text-center p-1.5 rounded-xl bg-slate-50 dark:bg-slate-950">
                                <span class="block text-sm font-black" :style="{ color: customForm.portal_primary_color }">8</span>
                                <span class="text-[7px] font-bold text-slate-400 uppercase">Agenda</span>
                            </div>
                            <div class="text-center p-1.5 rounded-xl bg-slate-50 dark:bg-slate-950">
                                <span class="block text-sm font-black text-emerald-600">6</span>
                                <span class="text-[7px] font-bold text-slate-400 uppercase">Concluído</span>
                            </div>
                            <div class="text-center p-1.5 rounded-xl bg-slate-50 dark:bg-slate-950">
                                <span class="block text-sm font-black text-purple-600">2</span>
                                <span class="text-[7px] font-bold text-slate-400 uppercase">Empresas</span>
                            </div>
                            <div class="text-center p-1.5 rounded-xl bg-slate-50 dark:bg-slate-950">
                                <span class="block text-sm font-black text-amber-500">4</span>
                                <span class="text-[7px] font-bold text-slate-400 uppercase">Avaliações</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Navigation Mini -->
                    <div class="flex gap-1 p-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs overflow-x-auto">
                        <button
                            v-for="tab in [
                                { id: 'appointments', icon: 'fa-solid fa-calendar-check', label: 'Agendamentos' },
                                { id: 'badges', icon: 'fa-solid fa-award', label: 'Medalhas' },
                                { id: 'coupons', icon: 'fa-solid fa-ticket', label: 'Cupons' },
                            ]"
                            :key="tab.id"
                            type="button"
                            @click="previewTab = tab.id"
                            class="px-2 py-1.5 rounded-lg text-[9px] font-extrabold transition-all flex items-center gap-1 whitespace-nowrap cursor-pointer"
                            :class="previewTab === tab.id
                                ? 'text-white shadow-xs'
                                : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                            :style="previewTab === tab.id ? { backgroundColor: customForm.portal_primary_color } : {}"
                        >
                            <i :class="tab.icon" class="text-[8px]"></i>
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>

                    <!-- Preview Tab: Appointments -->
                    <template v-if="previewTab === 'appointments'">
                        <!-- Appointment Card 1 -->
                        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 space-y-2 shadow-xs">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-extrabold text-slate-900 dark:text-white">Corte de Cabelo & Barba</p>
                                    <span v-if="customForm.portal_show_professionals" class="text-[9px] text-slate-400 block">
                                        <i class="fa-solid fa-user text-[8px] mr-0.5"></i>Profissional: Carlos Eduardo
                                    </span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[8px] font-black bg-emerald-500/15 text-emerald-600 shrink-0 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check text-[7px]"></i>Confirmado
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[10px] pt-1.5 border-t border-slate-100 dark:border-slate-800 text-slate-500">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex items-center gap-1">
                                        <i class="fa-regular fa-calendar text-[9px]" :style="{ color: customForm.portal_primary_color }"></i>
                                        25/08 · 14:00
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fa-regular fa-clock text-[9px]"></i>
                                        45 min
                                    </span>
                                </div>
                                <strong v-if="customForm.portal_show_service_prices" class="text-slate-900 dark:text-white font-black">R$ 80,00</strong>
                            </div>

                            <!-- Review section when enabled -->
                            <div v-if="customForm.portal_show_reviews" class="pt-2 border-t border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <div class="flex text-amber-400 text-[9px] gap-0.5">
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                            <i class="fa-solid fa-star"></i>
                                        </div>
                                        <span class="text-[9px] font-black text-slate-700 dark:text-slate-300 ml-1">Excelente</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400">Avaliado 25/08</span>
                                </div>
                                <p class="text-[9px] text-slate-500 italic mt-0.5">"Atendimento incrível, super recomendo!"</p>
                            </div>
                        </div>

                        <!-- Appointment Card 2 (Completed) -->
                        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 space-y-2 shadow-xs">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-extrabold text-slate-900 dark:text-white">Tratamento Capilar Premium</p>
                                    <span v-if="customForm.portal_show_professionals" class="text-[9px] text-slate-400 block">
                                        <i class="fa-solid fa-user text-[8px] mr-0.5"></i>Profissional: Ana Maria
                                    </span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[8px] font-black bg-blue-500/15 text-blue-600 shrink-0 flex items-center gap-1">
                                    <i class="fa-solid fa-check-double text-[7px]"></i>Concluído
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[10px] pt-1.5 border-t border-slate-100 dark:border-slate-800 text-slate-500">
                                <span class="flex items-center gap-1">
                                    <i class="fa-regular fa-calendar text-[9px]" :style="{ color: customForm.portal_primary_color }"></i>
                                    20/08 · 10:30
                                </span>
                                <strong v-if="customForm.portal_show_service_prices" class="text-slate-900 dark:text-white font-black">R$ 120,00</strong>
                            </div>

                            <!-- Pending review CTA -->
                            <div v-if="customForm.portal_show_reviews" class="pt-2 border-t border-slate-100 dark:border-slate-800">
                                <button
                                    type="button"
                                    class="w-full py-1.5 px-3 rounded-xl text-[9px] font-bold flex items-center justify-center gap-1.5 transition-all"
                                    :style="{
                                        backgroundColor: customForm.portal_primary_color + '15',
                                        color: customForm.portal_primary_color,
                                    }"
                                >
                                    <i class="fa-solid fa-star text-[8px]"></i>
                                    Avaliar este Atendimento
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Preview Tab: Badges -->
                    <template v-else-if="previewTab === 'badges'">
                        <div v-if="customForm.portal_show_loyalty_badges" class="space-y-2.5">
                            <!-- Badges hero header -->
                            <div
                                class="rounded-2xl p-3 text-white shadow-md"
                                :style="{
                                    background: `linear-gradient(135deg, ${customForm.portal_primary_color} 0%, ${customForm.portal_secondary_color} 100%)`
                                }"
                            >
                                <span class="text-[8px] font-black uppercase tracking-widest text-white/80">Programa de Fidelidade</span>
                                <h5 class="text-sm font-black">Suas Medalhas de Conquista</h5>
                                <p class="text-[9px] text-white/75">Acumule atendimentos e desbloqueie recompensas exclusivas.</p>
                            </div>

                            <!-- Badge Cards Grid -->
                            <div class="grid grid-cols-2 gap-2">
                                <!-- Earned Badge -->
                                <div class="rounded-2xl border border-amber-400/40 bg-white dark:bg-slate-900 p-3 shadow-md ring-1 ring-amber-400/20 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xl">⭐</span>
                                        <span class="px-1.5 py-0.5 rounded-full text-[7px] font-black bg-emerald-500/15 text-emerald-600">
                                            <i class="fa-solid fa-check text-[6px] mr-0.5"></i>Desbloqueado
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="text-[10px] font-black text-slate-900 dark:text-white">Cliente Frequente</h6>
                                        <span class="text-[8px] text-slate-400">Meta: 3 atendimentos</span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                                        <div class="h-full rounded-full bg-emerald-500 w-full"></div>
                                    </div>
                                </div>

                                <!-- Locked Badge -->
                                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 p-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xl opacity-50">👑</span>
                                        <span class="px-1.5 py-0.5 rounded-full text-[7px] font-black bg-slate-200 dark:bg-slate-800 text-slate-500">
                                            <i class="fa-solid fa-lock text-[6px] mr-0.5"></i>Bloqueado
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="text-[10px] font-black text-slate-900 dark:text-white">Cliente VIP</h6>
                                        <span class="text-[8px] text-slate-400">Meta: 10 atendimentos</span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                                        <div class="h-full rounded-full w-[60%]" :style="{ backgroundColor: customForm.portal_primary_color }"></div>
                                    </div>
                                    <p class="text-[8px] font-bold" :style="{ color: customForm.portal_primary_color }">Faltam 4 atendimentos!</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white/50 dark:bg-slate-900/50 p-6 text-center space-y-2">
                            <i class="fa-solid fa-lock text-xl text-slate-300"></i>
                            <p class="text-[10px] font-bold text-slate-400">Medalhas de Fidelidade estão desativadas para este portal.</p>
                        </div>
                    </template>

                    <!-- Preview Tab: Coupons -->
                    <template v-else-if="previewTab === 'coupons'">
                        <div
                            class="rounded-2xl p-3 text-white shadow-md"
                            :style="{
                                background: `linear-gradient(135deg, ${customForm.portal_secondary_color} 0%, ${customForm.portal_primary_color} 100%)`
                            }"
                        >
                            <span class="text-[8px] font-black uppercase tracking-widest text-white/80">Vouchers & Vantagens</span>
                            <h5 class="text-sm font-black">Seus Cupons de Desconto</h5>
                        </div>

                        <!-- Coupon Card -->
                        <div class="rounded-2xl border border-indigo-500/30 bg-white dark:bg-slate-900 p-3 space-y-2 shadow-xs">
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 rounded-full text-[8px] font-black bg-emerald-500/15 text-emerald-700 border border-emerald-500/25">
                                    15% OFF
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[7px] font-black uppercase bg-purple-500/15 text-purple-700 border border-purple-500/25">
                                    ⭐ Presente
                                </span>
                            </div>
                            <p class="text-[10px] font-bold text-slate-900 dark:text-white">Desconto especial de fidelidade</p>
                            <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-between">
                                <div>
                                    <span class="text-[8px] text-slate-400 font-bold block">CÓDIGO</span>
                                    <strong class="text-[11px] font-black tracking-widest" :style="{ color: customForm.portal_primary_color }">FIDELIDADE15</strong>
                                </div>
                                <span
                                    class="px-2 py-1 rounded-lg text-[9px] font-black text-white"
                                    :style="{ backgroundColor: customForm.portal_primary_color }"
                                >
                                    <i class="fa-solid fa-copy text-[8px] mr-0.5"></i>Copiar
                                </span>
                            </div>
                            <p class="text-[8px] text-slate-400">
                                <i class="fa-solid fa-calendar-xmark text-[7px] mr-0.5"></i>Expira em: 30/09/2026
                            </p>
                        </div>

                        <!-- Coupon Card 2 -->
                        <div class="rounded-2xl border border-indigo-500/30 bg-white dark:bg-slate-900 p-3 space-y-2 shadow-xs">
                            <span class="px-2 py-0.5 rounded-full text-[8px] font-black bg-emerald-500/15 text-emerald-700 border border-emerald-500/25">
                                R$ 20,00 OFF
                            </span>
                            <p class="text-[10px] font-bold text-slate-900 dark:text-white">Bônus por indicação de amigo</p>
                            <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-between">
                                <div>
                                    <span class="text-[8px] text-slate-400 font-bold block">CÓDIGO</span>
                                    <strong class="text-[11px] font-black tracking-widest" :style="{ color: customForm.portal_primary_color }">INDIQUE20</strong>
                                </div>
                                <span
                                    class="px-2 py-1 rounded-lg text-[9px] font-black text-white"
                                    :style="{ backgroundColor: customForm.portal_primary_color }"
                                >
                                    <i class="fa-solid fa-copy text-[8px] mr-0.5"></i>Copiar
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Phone Bottom Bar -->
            <div class="h-5 bg-slate-900 flex items-center justify-center">
                <div class="w-24 h-1 bg-slate-700 rounded-full"></div>
            </div>
        </div>
    </div>
</template>
