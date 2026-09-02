<script setup>
import { computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    email_enabled: Boolean(props.settings.email_enabled),
    whatsapp_enabled: Boolean(props.settings.whatsapp_enabled),
    require_manual_confirmation: Boolean(props.settings.require_manual_confirmation),
    reminder_enabled: Boolean(props.settings.reminder_enabled),
    reminder_time_value: Number(props.settings.reminder_time_value) || 2,
    reminder_time_unit: props.settings.reminder_time_unit || 'hours',
    notify_client_on_booking: Boolean(props.settings.notify_client_on_booking),
    notify_staff_on_booking: Boolean(props.settings.notify_staff_on_booking),
    notify_client_on_confirmation: Boolean(props.settings.notify_client_on_confirmation),
    notify_client_on_cancellation: Boolean(props.settings.notify_client_on_cancellation),
});

const submit = () => {
    form.post(route('admin.notifications.update'), {
        preserveScroll: true,
    });
};

const reminderPreviewText = computed(() => {
    const val = form.reminder_time_value;
    const unitMap = {
        minutes: val === 1 ? 'minuto' : 'minutos',
        hours: val === 1 ? 'hora' : 'horas',
        days: val === 1 ? 'dia' : 'dias',
    };
    return `${val} ${unitMap[form.reminder_time_unit] || 'horas'}`;
});
</script>

<template>
    <Head title="Notificações & Lembretes - Agendae" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                        Notificações & Lembretes
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Configure regras de envio, mensagens de confirmação e lembretes automáticos para clientes e equipe.
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-8 pb-12">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- 1. CANAIS DE ENVIO -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 md:p-8 shadow-sm backdrop-blur-xl">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i class="fa-solid fa-tower-broadcast"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Canais de Envio Ativos</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Escolha por onde seus clientes e sua equipe receberão os alertas.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- WhatsApp Card -->
                        <div class="flex items-start justify-between p-5 rounded-xl border transition-all"
                            :class="form.whatsapp_enabled ? 'border-emerald-500/40 bg-emerald-500/5 dark:bg-emerald-500/10' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50'">
                            <div class="flex items-start gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg"
                                    :class="form.whatsapp_enabled ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/25' : 'bg-slate-200 dark:bg-slate-800 text-slate-400'">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-slate-900 dark:text-white">WhatsApp Automático</h3>
                                        <span v-if="form.whatsapp_enabled" class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300">Ativo</span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Disparo instantâneo e confirmações interativas via WhatsApp.</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.whatsapp_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <!-- Email Card -->
                        <div class="flex items-start justify-between p-5 rounded-xl border transition-all"
                            :class="form.email_enabled ? 'border-indigo-500/40 bg-indigo-500/5 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50'">
                            <div class="flex items-start gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg"
                                    :class="form.email_enabled ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'bg-slate-200 dark:bg-slate-800 text-slate-400'">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-slate-900 dark:text-white">E-mail Transacional</h3>
                                        <span v-if="form.email_enabled" class="px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300">Ativo</span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Confirmações e lembretes entregues na caixa de entrada.</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.email_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 2. FLUXO DE APROVAÇÃO -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 md:p-8 shadow-sm backdrop-blur-xl">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Regra de Confirmação de Agendamentos</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Defina se novos pedidos entram direto como confirmados ou precisam de aprovação prévia.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-start gap-4 p-4 rounded-xl border cursor-pointer transition-all"
                            :class="!form.require_manual_confirmation ? 'border-indigo-500 bg-indigo-50/20 dark:bg-indigo-950/20' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                            <input type="radio" :value="false" v-model="form.require_manual_confirmation" class="mt-1 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-semibold text-slate-900 dark:text-white block">Confirmação Automática Instantânea</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 block mt-0.5">
                                    O agendamento é aceito imediatamente ao ser solicitado pelo cliente. O cliente recebe a confirmação na hora.
                                </span>
                            </div>
                        </label>

                        <label class="flex items-start gap-4 p-4 rounded-xl border cursor-pointer transition-all"
                            :class="form.require_manual_confirmation ? 'border-indigo-500 bg-indigo-50/20 dark:bg-indigo-950/20' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                            <input type="radio" :value="true" v-model="form.require_manual_confirmation" class="mt-1 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-semibold text-slate-900 dark:text-white block">Aprovação Prévia Obrigatória (Recomendado)</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 block mt-0.5">
                                    O agendamento fica como pendente. Você ou o profissional recebem um alerta no WhatsApp com opção de responder <b>SIM</b> para aprovar ou <b>NAO</b> para recusar.
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 3. LEMBRETES AUTOMÁTICOS -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 md:p-8 shadow-sm backdrop-blur-xl">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Lembrete Pré-Agendamento</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Reduza faltas e no-shows avisando o cliente antes do horário.</p>
                            </div>
                        </div>

                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.reminder_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <div v-if="form.reminder_enabled" class="space-y-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                    Tempo de Antecedência
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    max="168"
                                    v-model="form.reminder_time_value"
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                    Unidade de Medida
                                </label>
                                <select
                                    v-model="form.reminder_time_unit"
                                    class="w-full rounded-xl border-slate-200 dark:border-slate-800 dark:bg-slate-950 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="minutes">Minutos antes</option>
                                    <option value="hours">Horas antes</option>
                                    <option value="days">Dias antes</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center gap-3">
                            <i class="fa-solid fa-circle-info text-indigo-500"></i>
                            <span class="text-xs text-slate-600 dark:text-slate-400">
                                O lembrete será disparado automaticamente <b>{{ reminderPreviewText }}</b> antes do início de cada agendamento confirmado.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 4. GATILHOS DE EVENTOS -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 md:p-8 shadow-sm backdrop-blur-xl">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Gatilhos de Notificação Ativos</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Defina quais partes recebem avisos a cada etapa do ciclo de vida.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center justify-between p-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-user-plus text-indigo-500"></i>
                                <div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white block">Avisar Cliente na Criação</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Envia resumo do pedido recebido</span>
                                </div>
                            </div>
                            <input type="checkbox" v-model="form.notify_client_on_booking" class="rounded text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                        </label>

                        <label class="flex items-center justify-between p-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-briefcase text-blue-500"></i>
                                <div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white block">Avisar Profissional / Empresa</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Alerta a equipe sobre novos agendamentos</span>
                                </div>
                            </div>
                            <input type="checkbox" v-model="form.notify_staff_on_booking" class="rounded text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                        </label>

                        <label class="flex items-center justify-between p-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                <div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white block">Avisar Cliente na Aprovação / PIX</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Envia confirmação final ou cobrança PIX</span>
                                </div>
                            </div>
                            <input type="checkbox" v-model="form.notify_client_on_confirmation" class="rounded text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                        </label>

                        <label class="flex items-center justify-between p-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-circle-xmark text-rose-500"></i>
                                <div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white block">Avisar Cliente no Cancelamento</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Informa cancelamentos ou recusas</span>
                                </div>
                            </div>
                            <input type="checkbox" v-model="form.notify_client_on_cancellation" class="rounded text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                        </label>
                    </div>
                </div>

                <!-- SAVE BUTTON -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-lg shadow-indigo-600/25 transition-all flex items-center gap-2 disabled:opacity-50"
                    >
                        <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-floppy-disk"></i>
                        Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
