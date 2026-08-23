<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    editingCoupon: {
        type: Object,
        default: null,
    },
    couponForm: {
        type: Object,
        required: true,
    },
});

defineEmits(['close', 'save']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="$emit('close')">
            <form @submit.prevent="$emit('save')" class="liquid-glass-card w-full max-w-xl p-6 sm:p-7 space-y-5 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between gap-4 border-b pb-3" style="border-color: var(--border);">
                    <div>
                        <h3 class="text-lg font-black" style="color: var(--text-heading);">
                            {{ editingCoupon ? 'Editar Cupom de Desconto' : 'Novo Cupom de Desconto' }}
                        </h3>
                        <p class="text-xs opacity-60">Configure o código, tipo de desconto e regras de aplicação.</p>
                    </div>
                    <button type="button" @click="$emit('close')" class="w-8 h-8 rounded-xl hover:bg-slate-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="form-label text-xs font-bold block mb-1">Código do Cupom *</label>
                        <input
                            v-model="couponForm.code"
                            type="text"
                            class="form-control font-black text-sm uppercase tracking-wider"
                            placeholder="EX: VERAO15"
                            required
                        />
                        <p v-if="couponForm.errors.code" class="text-xs text-rose-500 mt-1">{{ couponForm.errors.code }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label text-xs font-bold block mb-1">Descrição / Finalidade</label>
                        <input
                            v-model="couponForm.description"
                            type="text"
                            class="form-control text-xs"
                            placeholder="Ex: Desconto especial de boas-vindas"
                        />
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold block mb-1">Tipo de Desconto *</label>
                        <select v-model="couponForm.discount_type" class="form-control text-xs font-bold" required>
                            <option value="percentage">Porcentagem (%)</option>
                            <option value="fixed">Valor Fixo (R$)</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold block mb-1">
                            {{ couponForm.discount_type === 'percentage' ? 'Percentual (%) *' : 'Valor (R$) *' }}
                        </label>
                        <input
                            v-model.number="couponForm.discount_value"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="form-control text-xs font-bold"
                            required
                        />
                        <p v-if="couponForm.errors.discount_value" class="text-xs text-rose-500 mt-1">{{ couponForm.errors.discount_value }}</p>
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold block mb-1">Valor Mínimo do Serviço (R$)</label>
                        <input
                            v-model.number="couponForm.min_spend"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control text-xs"
                            placeholder="Opcional"
                        />
                    </div>

                    <div>
                        <label class="form-label text-xs font-bold block mb-1">Limite Máximo de Usos</label>
                        <input
                            v-model.number="couponForm.max_uses"
                            type="number"
                            min="1"
                            class="form-control text-xs"
                            placeholder="Ilimitado se vazio"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label text-xs font-bold block mb-1">Data de Validade (Expiração)</label>
                        <input
                            v-model="couponForm.expires_at"
                            type="date"
                            class="form-control text-xs"
                        />
                    </div>

                    <div class="sm:col-span-2 flex items-center gap-2 pt-2">
                        <input
                            type="checkbox"
                            id="coupon_modal_is_active"
                            v-model="couponForm.is_active"
                            class="rounded"
                        />
                        <label for="coupon_modal_is_active" class="text-xs font-bold cursor-pointer">
                            Cupom Ativo (disponível para uso)
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t" style="border-color: var(--border);">
                    <button type="button" @click="$emit('close')" class="btn btn-outline text-xs rounded-xl">Cancelar</button>
                    <button type="submit" :disabled="couponForm.processing" class="btn btn-primary text-xs rounded-xl flex items-center gap-1.5 shadow-md">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>{{ couponForm.processing ? 'Salvando...' : (editingCoupon ? 'Atualizar Cupom' : 'Criar Cupom') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </Teleport>
</template>
