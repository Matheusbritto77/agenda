<script setup>
import { ref } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    currentBookingStep: {
        type: Number,
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

const emit = defineEmits(['set-booking-step']);

const selectedMockProfessional = ref(1);
const selectedMockService = ref(1);
const selectedMockDate = ref('2026-08-25');
const selectedMockTime = ref('14:30');
const mockSelectedCategory = ref('todos');
</script>

<template>
    <div class="space-y-3 animate-fade-in">
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
                    @click="$emit('set-booking-step', item.step)"
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
                    @click="selectedMockProfessional = 0; $emit('set-booking-step', 2)"
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
                    @click="selectedMockProfessional = 1; $emit('set-booking-step', 2)"
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
                    @click="selectedMockProfessional = 2; $emit('set-booking-step', 2)"
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
                    @click="$emit('set-booking-step', 2)"
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
                    @click="$emit('set-booking-step', 1)"
                    class="text-[11px] font-bold opacity-75 hover:opacity-100 cursor-pointer"
                    :style="{ color: form.text_color }"
                >
                    <i class="fa-solid fa-chevron-left mr-1 text-[9px]"></i> Voltar
                </button>
                <button
                    type="button"
                    @click="$emit('set-booking-step', 3)"
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
                    @click="$emit('set-booking-step', 2)"
                    class="text-[11px] font-bold opacity-75 hover:opacity-100 cursor-pointer"
                    :style="{ color: form.text_color }"
                >
                    <i class="fa-solid fa-chevron-left mr-1 text-[9px]"></i> Voltar
                </button>
                <button
                    type="button"
                    @click="$emit('set-booking-step', 4)"
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
                    @click="$emit('set-booking-step', 3)"
                    class="text-[11px] font-bold opacity-75 hover:opacity-100 cursor-pointer"
                    :style="{ color: form.text_color }"
                >
                    <i class="fa-solid fa-chevron-left mr-1 text-[9px]"></i> Voltar
                </button>
                <button
                    type="button"
                    @click="$emit('set-booking-step', 5)"
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
                        @click="$emit('set-booking-step', 1)"
                        class="w-full py-1.5 px-3 rounded-xl font-bold text-[10px] border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 cursor-pointer"
                    >
                        Fazer Novo Agendamento
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
