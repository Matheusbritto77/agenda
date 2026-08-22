<script setup>
import { ref } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    activeSubStep: {
        type: Number,
        default: 1,
    },
});

const emit = defineEmits(['update:activeSubStep', 'select-step']);

const stepTabs = [
    { step: 1, name: 'Profissional', icon: 'fa-solid fa-user-tie' },
    { step: 2, name: 'Serviço', icon: 'fa-solid fa-scissors' },
    { step: 3, name: 'Data & Hora', icon: 'fa-regular fa-calendar-days' },
    { step: 4, name: 'Confirmação', icon: 'fa-solid fa-clipboard-check' },
    { step: 5, name: 'Sucesso', icon: 'fa-solid fa-circle-check' },
];

const handleSelectStep = (step) => {
    emit('update:activeSubStep', step);
    emit('select-step', step);
};
</script>

<template>
    <div class="space-y-5">
        <!-- Sub-step Switcher Bar -->
        <div class="p-1.5 bg-slate-100 dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center gap-1 overflow-x-auto scrollbar-none">
            <button
                v-for="st in stepTabs"
                :key="st.step"
                type="button"
                @click="handleSelectStep(st.step)"
                :class="[
                    'px-3 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 cursor-pointer',
                    activeSubStep === st.step
                        ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-xs'
                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
                ]"
            >
                <i :class="st.icon" class="text-[11px]"></i>
                <span>{{ st.step }}. {{ st.name }}</span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- SUB-STEP 1: SELEÇÃO DE PROFISSIONAL        -->
        <!-- ========================================== -->
        <div v-if="activeSubStep === 1" class="space-y-4 animate-fade-in">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/50 p-4 space-y-1">
                <div class="flex items-center gap-2 text-slate-900 dark:text-white">
                    <i class="fa-solid fa-user-tie text-indigo-500"></i>
                    <h2 class="text-sm font-black">Etapa 1: Seleção de Profissional</h2>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Personalize os títulos e regras exibidos quando o cliente vai selecionar com quem deseja ser atendido.
                </p>
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_professional_title">Título da Etapa</label>
                <input
                    type="text"
                    id="booking_step_professional_title"
                    v-model="form.booking_step_professional_title"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Escolha o Profissional ou Selecione o Especialista"
                />
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_professional_subtitle">Subtítulo / Instrução</label>
                <input
                    type="text"
                    id="booking_step_professional_subtitle"
                    v-model="form.booking_step_professional_subtitle"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Escolha seu profissional favorito ou qualquer disponível para o primeiro horário."
                />
            </div>

            <label class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-between gap-3 cursor-pointer">
                <div>
                    <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Opção "Qualquer Profissional"</span>
                    <span class="block text-[11px] text-slate-400">Permite ao cliente agendar no primeiro horário vago com qualquer membro disponível.</span>
                </div>
                <input
                    type="checkbox"
                    v-model="form.booking_step_professional_allow_any"
                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
            </label>
        </div>

        <!-- ========================================== -->
        <!-- SUB-STEP 2: SELEÇÃO DE SERVIÇOS            -->
        <!-- ========================================== -->
        <div v-else-if="activeSubStep === 2" class="space-y-4 animate-fade-in">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/50 p-4 space-y-1">
                <div class="flex items-center gap-2 text-slate-900 dark:text-white">
                    <i class="fa-solid fa-scissors text-indigo-500"></i>
                    <h2 class="text-sm font-black">Etapa 2: Seleção de Serviços</h2>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Personalize os textos e opções exibidas no catálogo de serviços durante o agendamento.
                </p>
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_service_title">Título da Etapa</label>
                <input
                    type="text"
                    id="booking_step_service_title"
                    v-model="form.booking_step_service_title"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Escolha o Serviço ou Nossos Procedimentos"
                />
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_service_subtitle">Subtítulo / Instrução</label>
                <input
                    type="text"
                    id="booking_step_service_subtitle"
                    v-model="form.booking_step_service_subtitle"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Selecione o procedimento que deseja realizar."
                />
            </div>

            <label class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-between gap-3 cursor-pointer">
                <div>
                    <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Barra de Busca de Serviços</span>
                    <span class="block text-[11px] text-slate-400">Exibir campo para o cliente pesquisar rapidamente o serviço pelo nome.</span>
                </div>
                <input
                    type="checkbox"
                    v-model="form.booking_step_service_search_enabled"
                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
            </label>
        </div>

        <!-- ========================================== -->
        <!-- SUB-STEP 3: DATA E HORÁRIO                 -->
        <!-- ========================================== -->
        <div v-else-if="activeSubStep === 3" class="space-y-4 animate-fade-in">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/50 p-4 space-y-1">
                <div class="flex items-center gap-2 text-slate-900 dark:text-white">
                    <i class="fa-regular fa-calendar-days text-indigo-500"></i>
                    <h2 class="text-sm font-black">Etapa 3: Data e Horário</h2>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Personalize as mensagens e orientações do calendário de horários.
                </p>
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_datetime_title">Título da Etapa</label>
                <input
                    type="text"
                    id="booking_step_datetime_title"
                    v-model="form.booking_step_datetime_title"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Escolha Data e Horário"
                />
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_datetime_subtitle">Subtítulo / Instrução</label>
                <input
                    type="text"
                    id="booking_step_datetime_subtitle"
                    v-model="form.booking_step_datetime_subtitle"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Selecione o melhor dia e horário para o seu atendimento."
                />
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SUB-STEP 4: DADOS E CONFIRMAÇÃO           -->
        <!-- ========================================== -->
        <div v-else-if="activeSubStep === 4" class="space-y-4 animate-fade-in">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/50 p-4 space-y-1">
                <div class="flex items-center gap-2 text-slate-900 dark:text-white">
                    <i class="fa-solid fa-clipboard-check text-indigo-500"></i>
                    <h2 class="text-sm font-black">Etapa 4: Dados do Cliente & Confirmação</h2>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Personalize os campos de identificação e o botão final de confirmação.
                </p>
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_confirm_title">Título da Etapa</label>
                <input
                    type="text"
                    id="booking_step_confirm_title"
                    v-model="form.booking_step_confirm_title"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Dados & Confirmação"
                />
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_confirm_button_label">Texto do Botão de Confirmação</label>
                <input
                    type="text"
                    id="booking_step_confirm_button_label"
                    v-model="form.booking_step_confirm_button_label"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Confirmar Agendamento ou Finalizar Agendamento"
                />
            </div>

            <label class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-between gap-3 cursor-pointer">
                <div>
                    <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Campo de Observações Adicionais</span>
                    <span class="block text-[11px] text-slate-400">Permitir que o cliente deixe notas especiais antes de confirmar.</span>
                </div>
                <input
                    type="checkbox"
                    v-model="form.booking_step_confirm_show_notes"
                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
            </label>
        </div>

        <!-- ========================================== -->
        <!-- SUB-STEP 5: SUCESSO & CONCLUSÃO            -->
        <!-- ========================================== -->
        <div v-else-if="activeSubStep === 5" class="space-y-4 animate-fade-in">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/50 p-4 space-y-1">
                <div class="flex items-center gap-2 text-slate-900 dark:text-white">
                    <i class="fa-solid fa-circle-check text-emerald-500"></i>
                    <h2 class="text-sm font-black">Etapa 5: Tela de Sucesso & Comprovante</h2>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Personalize a mensagem e botões exibidos logo após o agendamento ser concluído com sucesso.
                </p>
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_success_title">Título de Sucesso</label>
                <input
                    type="text"
                    id="booking_step_success_title"
                    v-model="form.booking_step_success_title"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Agendamento Confirmado com Sucesso!"
                />
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_success_message">Mensagem de Instruções / Lembrete</label>
                <textarea
                    id="booking_step_success_message"
                    v-model="form.booking_step_success_message"
                    rows="3"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Um lembrete com os detalhes foi enviado para o seu WhatsApp. Caso precise reagendar, entre em contato."
                ></textarea>
            </div>

            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="booking_step_success_whatsapp_label">Texto do Botão de WhatsApp Pós-Agendamento</label>
                <input
                    type="text"
                    id="booking_step_success_whatsapp_label"
                    v-model="form.booking_step_success_whatsapp_label"
                    class="form-control text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: Conversar no WhatsApp ou Falar com Atendente"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(3px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
