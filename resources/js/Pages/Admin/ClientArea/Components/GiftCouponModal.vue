<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    client: {
        type: Object,
        default: null,
    },
    giftForm: {
        type: Object,
        required: true,
    },
});

defineEmits(['close', 'submit']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show && client" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="$emit('close')">
            <form @submit.prevent="$emit('submit')" class="liquid-glass-card w-full max-w-lg p-6 space-y-5">
                <div class="flex items-start justify-between gap-4 border-b pb-3" style="border-color: var(--border);">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-purple-500/15 text-purple-600 dark:text-purple-400">
                                <i class="fa-solid fa-gift"></i>
                            </span>
                            <h3 class="text-lg font-black" style="color: var(--text-heading);">Presentear Cupom</h3>
                        </div>
                        <p class="text-xs opacity-60 mt-1">
                            Gere um voucher exclusivo e nominal para <strong>{{ client.name }}</strong>.
                        </p>
                    </div>
                    <button type="button" @click="$emit('close')" class="w-8 h-8 rounded-xl hover:bg-slate-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="form-label text-xs font-bold block mb-1">Código do Cupom *</label>
                        <input
                            v-model="giftForm.code"
                            type="text"
                            class="form-control font-black text-sm uppercase tracking-wider"
                            required
                        />
                        <p v-if="giftForm.errors.code" class="text-xs text-rose-500 mt-1">{{ giftForm.errors.code }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold block mb-1">Tipo de Desconto</label>
                            <select v-model="giftForm.discount_type" class="form-control text-xs font-bold">
                                <option value="percentage">Porcentagem (%)</option>
                                <option value="fixed">Valor Fixo (R$)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold block mb-1">Valor do Desconto</label>
                            <input
                                v-model.number="giftForm.discount_value"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="form-control text-xs font-bold"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold block mb-1">Mensagem / Descrição do Presente</label>
                        <input
                            v-model="giftForm.description"
                            type="text"
                            class="form-control text-xs"
                            placeholder="Ex: Presente de aniversário / Cliente especial"
                        />
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold block mb-1">Data de Validade</label>
                        <input
                            v-model="giftForm.expires_at"
                            type="date"
                            class="form-control text-xs"
                        />
                    </div>

                    <div class="p-3 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-xs text-purple-900 dark:text-purple-200">
                        <i class="fa-solid fa-sparkles mr-1.5 text-purple-500"></i>
                        Este cupom será exibido imediatamente na Área do Cliente de <strong>{{ client.name }}</strong> ({{ client.account_email }}).
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t" style="border-color: var(--border);">
                    <button type="button" @click="$emit('close')" class="btn btn-outline text-xs rounded-xl">Cancelar</button>
                    <button type="submit" :disabled="giftForm.processing" class="btn btn-primary text-xs rounded-xl bg-purple-600 hover:bg-purple-500 text-white flex items-center gap-1.5 shadow-md">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>{{ giftForm.processing ? 'Enviando...' : 'Enviar Cupom Presente' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </Teleport>
</template>
