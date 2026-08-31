<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    logs: {
        type: Array,
        default: () => [],
    },
    queue: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('settings'); // 'settings' | 'logs' | 'queue'
const logFilterChannel = ref('all');
const logSearch = ref('');
const selectedLog = ref(null);

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

const filteredLogs = computed(() => {
    return props.logs.filter((log) => {
        if (logFilterChannel.value !== 'all' && log.channel !== logFilterChannel.value) {
            return false;
        }
        if (logSearch.value) {
            const q = logSearch.value.toLowerCase();
            const titleMatch = log.title?.toLowerCase().includes(q);
            const descMatch = log.description?.toLowerCase().includes(q);
            const clientMatch = log.appointment?.client_name?.toLowerCase().includes(q);
            const phoneMatch = log.appointment?.client_phone?.includes(q);
            return titleMatch || descMatch || clientMatch || phoneMatch;
        }
        return true;
    });
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const getLevelBadge = (level) => {
    switch (level) {
        case 'success':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
        case 'warning':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        case 'error':
        case 'danger':
            return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
        default:
            return 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20';
    }
};

const getChannelIcon = (channel) => {
    switch (channel) {
        case 'whatsapp':
            return 'fa-brands fa-whatsapp text-emerald-500';
        case 'email':
            return 'fa-solid fa-envelope text-indigo-500';
        case 'payment':
            return 'fa-solid fa-qrcode text-purple-500';
        default:
            return 'fa-solid fa-gear text-slate-500';
    }
};
</script>

<template>
    <Head title="Notificações & Logs - Agendae" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                        Notificações & Fluxo de Agendamentos
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Configure regras, acompanhe disparos em tempo real e inspecione logs detalhados do fluxo.
                    </p>
                </div>

                <!-- TAB SWITCHER -->
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl self-start">
                    <button
                        type="button"
                        @click="activeTab = 'settings'"
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all flex items-center gap-2"
                        :class="activeTab === 'settings' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    >
                        <i class="fa-solid fa-sliders"></i>
                        Configurações
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'logs'"
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all flex items-center gap-2 relative"
                        :class="activeTab === 'logs' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    >
                        <i class="fa-solid fa-list-check"></i>
                        Logs do Fluxo
                        <span v-if="logs.length" class="px-1.5 py-0.2 bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 rounded-full text-[10px]">
                            {{ logs.length }}
                        </span>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'queue'"
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all flex items-center gap-2"
                        :class="activeTab === 'queue' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    >
                        <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                        Fila WhatsApp
                        <span v-if="queue.length" class="px-1.5 py-0.2 bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 rounded-full text-[10px]">
                            {{ queue.length }}
                        </span>
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-8 pb-12">
            <!-- TAB 1: CONFIGURAÇÕES -->
            <div v-if="activeTab === 'settings'">
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
                                <span><strong>Se Ativado:</strong> O agendamento ficará com status <em>"Aguardando Aprovação"</em>. O profissional receberá a notificação com opções SIM / NÃO e poderá gerenciar diretamente no painel.</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-qrcode text-emerald-600 dark:text-emerald-400 mt-0.5"></i>
                                <span><strong>Com Integração PIX ativa:</strong> Ao aprovar o pedido, o sistema dispara automaticamente no WhatsApp do cliente o aviso com o <strong>QR Code PIX e chave Copia e Cola</strong> para pagamento imediato.</span>
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

            <!-- TAB 2: LOGS DO FLUXO -->
            <div v-else-if="activeTab === 'logs'" class="space-y-6">
                <!-- Filters & Search -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0">
                        <button
                            type="button"
                            @click="logFilterChannel = 'all'"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                            :class="logFilterChannel === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
                        >
                            Todos ({{ logs.length }})
                        </button>
                        <button
                            type="button"
                            @click="logFilterChannel = 'whatsapp'"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition flex items-center gap-1.5"
                            :class="logFilterChannel === 'whatsapp' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
                        >
                            <i class="fa-brands fa-whatsapp"></i>
                            WhatsApp
                        </button>
                        <button
                            type="button"
                            @click="logFilterChannel = 'email'"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition flex items-center gap-1.5"
                            :class="logFilterChannel === 'email' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
                        >
                            <i class="fa-solid fa-envelope"></i>
                            E-mail
                        </button>
                        <button
                            type="button"
                            @click="logFilterChannel = 'payment'"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition flex items-center gap-1.5"
                            :class="logFilterChannel === 'payment' ? 'bg-purple-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
                        >
                            <i class="fa-solid fa-qrcode"></i>
                            PIX & Pagamentos
                        </button>
                    </div>

                    <div class="relative w-full md:w-72">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input
                            type="text"
                            v-model="logSearch"
                            placeholder="Buscar logs ou cliente..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <!-- Logs Table / Feed -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                    <div v-if="filteredLogs.length === 0" class="p-12 text-center text-slate-400">
                        <i class="fa-solid fa-file-waveform text-4xl mb-3 text-slate-300 dark:text-slate-700"></i>
                        <p class="font-medium text-sm text-slate-600 dark:text-slate-400">Nenhum registro de log encontrado.</p>
                        <p class="text-xs text-slate-400 mt-1">Conforme novos agendamentos e notificações forem processados, os eventos aparecerão aqui automaticamente.</p>
                    </div>

                    <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                        <div
                            v-for="log in filteredLogs"
                            :key="log.id"
                            @click="selectedLog = log"
                            class="p-4 md:p-5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition cursor-pointer flex items-start justify-between gap-4"
                        >
                            <div class="flex items-start gap-3.5">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm shrink-0 mt-0.5 border"
                                    :class="getLevelBadge(log.level)">
                                    <i :class="getChannelIcon(log.channel)"></i>
                                </div>
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ log.title }}</span>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md uppercase tracking-wider border"
                                            :class="getLevelBadge(log.level)">
                                            {{ log.channel }}
                                        </span>
                                        <span v-if="log.appointment" class="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            Agendamento #{{ log.appointment.id }} ({{ log.appointment.client_name }})
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2">{{ log.description }}</p>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-[11px] font-medium text-slate-400 block">{{ formatDate(log.created_at) }}</span>
                                <span class="text-xs text-indigo-500 hover:text-indigo-600 font-semibold mt-1 inline-flex items-center gap-1">
                                    Detalhes <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: FILA DO WHATSAPP -->
            <div v-else-if="activeTab === 'queue'" class="space-y-6">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                                Mensagens na Fila de Disparo
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Mensagens aguardando processamento pelo daemon do WhatsApp.</p>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 font-semibold">
                            {{ queue.length }} registro(s)
                        </span>
                    </div>

                    <div v-if="queue.length === 0" class="p-12 text-center text-slate-400">
                        <i class="fa-solid fa-inbox text-4xl mb-3 text-slate-300 dark:text-slate-700"></i>
                        <p class="font-medium text-sm text-slate-600 dark:text-slate-400">A fila está vazia no momento.</p>
                        <p class="text-xs text-slate-400 mt-1">Todas as notificações foram processadas com sucesso.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th class="p-3.5">ID</th>
                                    <th class="p-3.5">Status</th>
                                    <th class="p-3.5">Destinatário</th>
                                    <th class="p-3.5">Tipo</th>
                                    <th class="p-3.5">Programado Para</th>
                                    <th class="p-3.5">Mensagem</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                                <tr v-for="item in queue" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                    <td class="p-3.5 font-bold">#{{ item.id }}</td>
                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                            :class="item.status === 'sent' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : (item.status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400')">
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 font-medium">
                                        {{ item.recipient_name }}
                                        <span class="block text-[11px] text-slate-400 font-mono">{{ item.recipient_phone }}</span>
                                    </td>
                                    <td class="p-3.5 font-medium">{{ item.message_type }}</td>
                                    <td class="p-3.5 font-mono text-[11px]">{{ formatDate(item.scheduled_for) }}</td>
                                    <td class="p-3.5 max-w-xs truncate text-slate-500 dark:text-slate-400" :title="item.message_body">
                                        {{ item.message_body }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL DETALHE DO LOG -->
        <div v-if="selectedLog" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="selectedLog = null">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in duration-150">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg border"
                            :class="getLevelBadge(selectedLog.level)">
                            <i :class="getChannelIcon(selectedLog.channel)"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-slate-900 dark:text-white">{{ selectedLog.title }}</h3>
                            <span class="text-xs text-slate-400">{{ formatDate(selectedLog.created_at) }}</span>
                        </div>
                    </div>
                    <button type="button" @click="selectedLog = null" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white flex items-center justify-center">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-3 pt-2">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 block mb-1">Descrição do Evento</span>
                        <p class="text-xs bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 leading-relaxed">
                            {{ selectedLog.description }}
                        </p>
                    </div>

                    <div v-if="selectedLog.metadata">
                        <span class="text-xs font-semibold text-slate-400 block mb-1">Metadados & Payload Técnico</span>
                        <pre class="text-[11px] bg-slate-950 text-emerald-400 p-3 rounded-xl overflow-x-auto font-mono max-h-48">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
                    </div>

                    <div v-if="selectedLog.appointment" class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Agendamento Vinculado:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">#{{ selectedLog.appointment.id }} - {{ selectedLog.appointment.client_name }}</span>
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button
                        type="button"
                        @click="selectedLog = null"
                        class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
