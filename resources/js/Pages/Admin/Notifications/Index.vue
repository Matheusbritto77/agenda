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
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    Notificações & Lembretes
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Configure os canais de envio, lembretes antecipados e regras de confirmação de agendamentos.
                </p>
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
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Disparo instantâneo via gateway gRPC de alta performance.</p>
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

                <!-- 2. LEMBRETE ANTECIPADO -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 md:p-8 shadow-sm backdrop-blur-xl">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Lembrete de Antecedência</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Envie um lembrete com confirmação de presença antes do atendimento.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.reminder_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-amber-500"></div>
                        </label>
                    </div>

                    <div v-if="form.reminder_enabled" class="space-y-4 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Disparar lembrete com</span>
                            <div class="flex items-center gap-2">
                                <input
                                    type="number"
                                    min="1"
                                    max="168"
                                    v-model.number="form.reminder_time_value"
                                    class="w-20 px-3 py-2 text-center rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                />
                                <select
                                    v-model="form.reminder_time_unit"
                                    class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                >
                                    <option value="minutes">Minutos antes</option>
                                    <option value="hours">Horas antes</option>
                                    <option value="days">Dias antes</option>
                                </select>
                            </div>
                            <span class="text-sm text-slate-500 dark:text-slate-400">do horário marcado</span>
                        </div>

                        <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-300 text-xs flex items-center gap-2.5">
                            <i class="fa-solid fa-circle-info text-sm"></i>
                            <span>O cliente receberá uma mensagem automática <strong>{{ reminderPreviewText }} antes</strong> do horário com os dados do agendamento e pedido de confirmação de presença.</span>
                        </div>
                    </div>
                </div>

                <!-- 3. FLUXO DE APROVAÇÃO & PIX -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 md:p-8 shadow-sm backdrop-blur-xl">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Aprovação Prévia & Pagamento PIX</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Defina se os agendamentos exigem aprovação do profissional antes de confirmar.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.require_manual_confirmation" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-purple-600"></div>
                        </label>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-400 leading-relaxed space-y-2">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-check-circle text-purple-600 dark:text-purple-400 mt-0.5"></i>
                            <span><strong>Se Ativado:</strong> O agendamento ficará com status <em>"Aguardando Aprovação"</em>. O profissional receberá a notificação para aprovar ou recusar no painel.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-qrcode text-emerald-600 dark:text-emerald-400 mt-0.5"></i>
                            <span><strong>Com Integração PIX ativa:</strong> Ao aprovar o pedido, o sistema dispara automaticamente no WhatsApp do cliente o aviso <em>"Agendamento quase concluído!"</em> com o <strong>QR Code PIX e chave Copia e Cola</strong> para pagamento imediato.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-bolt text-amber-500 mt-0.5"></i>
                            <span><strong>Se Desativado:</strong> Sem pagamento obrigatório, o horário é confirmado automaticamente no momento em que o cliente conclui o formulário.</span>
                        </div>
                    </div>
                </div>

                <!-- 4. GATILHOS DE NOTIFICAÇÕES -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 md:p-8 shadow-sm backdrop-blur-xl">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <i class="fa-solid fa-sliders"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Gatilhos de Eventos</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Ative ou desative notificações para eventos específicos.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center justify-between p-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-user-plus text-indigo-500"></i>
                                <div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white block">Avisar Cliente ao Agendar</span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Envia recibo inicial da solicitação</span>
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
