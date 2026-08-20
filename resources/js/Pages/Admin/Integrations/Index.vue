<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    paymentConfig: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    gateway: props.paymentConfig.gateway || 'mercadopago',
    is_active: props.paymentConfig.is_active || false,
    access_token: props.paymentConfig.access_token || '',
    settings: {
        pix_expiration_minutes: props.paymentConfig.settings?.pix_expiration_minutes || 30,
    },
});

const showToken = ref(false);
const saveSuccess = ref(false);

const submit = () => {
    form.post(route('admin.integrations.payments.update'), {
        preserveScroll: true,
        onSuccess: () => {
            saveSuccess.value = true;
            setTimeout(() => {
                saveSuccess.value = false;
            }, 3000);
        },
    });
};

const isConfigured = computed(() => {
    return form.access_token && form.access_token.trim().length > 10;
});
</script>

<template>
    <AdminLayout>
        <Head title="Integrações & Pagamentos - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Integrações & Pagamentos</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Conecte gateways de pagamento para automatizar as cobranças dos seus serviços</p>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <div v-if="saveSuccess" class="p-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-xs sm:text-sm font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>Configurações salvas com sucesso!</span>
            </div>

            <div class="card overflow-hidden p-6 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b flex-wrap gap-3" style="border-color: var(--border);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shadow-md shadow-indigo-500/20">
                            <i class="fa-solid fa-puzzle-piece"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base sm:text-lg" style="color: var(--text-heading);">Gateway de Pagamento</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Configure as credenciais e regras de pagamento online</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span v-if="form.is_active && isConfigured" class="badge badge-confirmed">
                            Ativo e Conectado
                        </span>
                        <span v-else-if="form.is_active" class="badge badge-cancelled bg-amber-500/15 text-amber-600">
                            Sem Token / Pendente
                        </span>
                        <span v-else class="badge badge-cancelled">
                            Desativado
                        </span>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="pt-1">
                        <label class="flex items-center gap-3 cursor-pointer select-none p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 transition-all hover:bg-slate-100 dark:hover:bg-slate-900">
                            <input type="checkbox" v-model="form.is_active" class="w-4 h-4 rounded text-indigo-600 focus:ring-0">
                            <div class="text-xs sm:text-sm">
                                <span class="font-bold block" style="color: var(--text-heading);">Ativar Pagamento Online no Agendamento</span>
                                <span class="opacity-70 text-[11px] sm:text-xs">Permite que seus clientes paguem via PIX ao finalizar a reserva.</span>
                            </div>
                        </label>
                    </div>

                    <div v-if="form.is_active" class="space-y-4 pt-2">
                        <div class="form-group">
                            <label class="form-label text-xs font-bold" for="gateway_select">Gateway de Pagamento *</label>
                            <select id="gateway_select" v-model="form.gateway" class="form-control text-xs sm:text-sm rounded-xl" required>
                                <option value="mercadopago">Mercado Pago (PIX)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="flex justify-between items-center mb-1">
                                <label class="form-label text-xs font-bold mb-0" for="access_token">Access Token do Mercado Pago *</label>
                                <a href="https://www.mercadopago.com.br/developers/panel/credentials" target="_blank" class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Onde encontrar minhas credenciais? <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                </a>
                            </div>
                            <div class="relative">
                                <input
                                    :type="showToken ? 'text' : 'password'"
                                    id="access_token"
                                    v-model="form.access_token"
                                    class="form-control text-xs sm:text-sm rounded-xl pr-10"
                                    placeholder="APP_USR-..."
                                    required
                                />
                                <button
                                    type="button"
                                    @click="showToken = !showToken"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs"
                                >
                                    <i :class="showToken ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1">Insira seu Access Token do painel de desenvolvedor do Mercado Pago. Mantenha em segurança.</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label text-xs font-bold" for="pix_expiration">Tempo de Expiração do PIX (Minutos) *</label>
                            <input
                                type="number"
                                id="pix_expiration"
                                v-model="form.settings.pix_expiration_minutes"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                min="1"
                                max="1440"
                                required
                            />
                            <p class="text-[10px] text-slate-500 mt-1">Tempo limite para o cliente efetuar o pagamento do QR Code Pix (Recomendado: 30 minutos).</p>
                        </div>
                    </div>

                    <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                        <button type="submit" class="btn btn-primary py-2.5 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30" :disabled="form.processing">
                            <i class="fa-solid fa-floppy-disk text-xs mr-1"></i>
                            <span>{{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
