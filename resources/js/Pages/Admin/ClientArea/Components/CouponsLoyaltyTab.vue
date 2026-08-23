<script setup>
defineProps({
    coupons: {
        type: Array,
        default: () => [],
    },
    loyaltyTiersList: {
        type: Array,
        default: () => [],
    },
    couponSubTab: {
        type: String,
        default: 'coupons',
    },
    loyaltyForm: {
        type: Object,
        required: true,
    },
    hasPermission: {
        type: Function,
        required: true,
    },
    currency: {
        type: Function,
        required: true,
    },
});

defineEmits([
    'update:couponSubTab',
    'open-create-coupon',
    'open-edit-coupon',
    'toggle-coupon',
    'delete-coupon',
    'add-loyalty-tier',
    'remove-loyalty-tier',
    'save-loyalty-tiers',
]);
</script>

<template>
    <section class="space-y-6">
        <!-- Sub-nav bar: Cupons vs Níveis de Fidelidade -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-900 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                <button
                    type="button"
                    @click="$emit('update:couponSubTab', 'coupons')"
                    :class="['px-4 py-2 rounded-xl text-xs sm:text-sm font-black transition-all cursor-pointer flex items-center gap-2', couponSubTab === 'coupons' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
                >
                    <i class="fa-solid fa-ticket"></i>
                    <span>Cupons de Desconto</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="couponSubTab === 'coupons' ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-800'">
                        {{ coupons.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="$emit('update:couponSubTab', 'tiers')"
                    :class="['px-4 py-2 rounded-xl text-xs sm:text-sm font-black transition-all cursor-pointer flex items-center gap-2', couponSubTab === 'tiers' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
                >
                    <i class="fa-solid fa-trophy"></i>
                    <span>Regras & Medalhas de Fidelidade</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="couponSubTab === 'tiers' ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-800'">
                        {{ loyaltyTiersList.length }}
                    </span>
                </button>
            </div>

            <div v-if="couponSubTab === 'coupons'" class="flex items-center gap-2">
                <button
                    v-if="hasPermission('clients.edit')"
                    type="button"
                    @click="$emit('open-create-coupon')"
                    class="btn btn-primary rounded-xl text-xs font-black flex items-center gap-2"
                >
                    <i class="fa-solid fa-plus"></i>
                    <span>Novo Cupom</span>
                </button>
            </div>
        </div>

        <!-- SUB-TAB 1: CUPONS LIST -->
        <div v-if="couponSubTab === 'coupons'" class="space-y-4">
            <div v-if="coupons.length === 0" class="glass-card-3d rounded-3xl p-10 text-center space-y-3">
                <i class="fa-solid fa-ticket text-4xl text-indigo-400 mb-2"></i>
                <h3 class="font-black text-lg">Nenhum cupom cadastrado</h3>
                <p class="text-sm opacity-60 max-w-sm mx-auto">
                    Crie cupons de desconto em percentual ou valor fixo para presentear clientes fiéis e atrair novos agendamentos!
                </p>
                <button
                    v-if="hasPermission('clients.edit')"
                    type="button"
                    @click="$emit('open-create-coupon')"
                    class="btn btn-primary rounded-xl text-xs font-bold mt-2"
                >
                    <i class="fa-solid fa-plus mr-1.5"></i>Criar Primeiro Cupom
                </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <article
                    v-for="coupon in coupons"
                    :key="coupon.id"
                    class="glass-card-3d rounded-2xl p-5 space-y-4 flex flex-col justify-between"
                >
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <span class="px-3 py-1 rounded-xl font-black text-xs tracking-wider uppercase" :class="coupon.is_valid ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : 'bg-slate-500/15 text-slate-500'">
                                {{ coupon.formatted_discount }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="coupon.is_active ? 'bg-indigo-500/15 text-indigo-600 dark:text-cyan-400' : 'bg-rose-500/15 text-rose-500'">
                                {{ coupon.is_active ? 'Ativo' : 'Pausado' }}
                            </span>
                        </div>

                        <div>
                            <h4 class="text-base font-black tracking-wider text-indigo-600 dark:text-cyan-400">
                                {{ coupon.code }}
                            </h4>
                            <p class="text-xs opacity-75 mt-0.5">{{ coupon.description || 'Desconto promocional' }}</p>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-500/5 text-xs space-y-1">
                            <p v-if="coupon.client_name" class="font-bold text-purple-600 dark:text-purple-400">
                                <i class="fa-solid fa-user-tag mr-1"></i>Exclusivo para: {{ coupon.client_name }}
                            </p>
                            <p v-if="coupon.min_spend">
                                <i class="fa-solid fa-circle-info text-[10px] mr-1 opacity-50"></i>
                                Gasto mínimo: {{ currency(coupon.min_spend) }}
                            </p>
                            <p>
                                <i class="fa-solid fa-chart-pie text-[10px] mr-1 opacity-50"></i>
                                Utilizações: <strong>{{ coupon.uses_count }}</strong>{{ coupon.max_uses ? ` de ${coupon.max_uses}` : ' (sem limite)' }}
                            </p>
                            <p v-if="coupon.expires_at_formatted">
                                <i class="fa-solid fa-calendar-xmark text-[10px] mr-1 opacity-50"></i>
                                Expira em: {{ coupon.expires_at_formatted }}
                            </p>
                        </div>
                    </div>

                    <div v-if="hasPermission('clients.edit')" class="flex items-center justify-between gap-2 pt-3 border-t" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="$emit('toggle-coupon', coupon)"
                            class="btn btn-outline !px-2.5 !py-1.5 rounded-xl text-xs"
                        >
                            <i :class="coupon.is_active ? 'fa-solid fa-pause' : 'fa-solid fa-play'"></i>
                            <span>{{ coupon.is_active ? 'Pausar' : 'Ativar' }}</span>
                        </button>
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="$emit('open-edit-coupon', coupon)"
                                class="btn btn-outline !px-2.5 !py-1.5 rounded-xl text-xs"
                            >
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button
                                type="button"
                                @click="$emit('delete-coupon', coupon)"
                                class="btn btn-outline !px-2.5 !py-1.5 rounded-xl text-xs text-rose-500 hover:text-rose-700"
                            >
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </div>

        <!-- SUB-TAB 2: LOYALTY TIERS & BADGES CONFIG -->
        <div v-else class="space-y-6">
            <div class="rounded-3xl border border-indigo-500/25 bg-gradient-to-r from-indigo-900/30 via-slate-900/40 to-slate-950/60 p-5 sm:p-6 text-white flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-xl">
                <div class="space-y-1">
                    <h3 class="text-lg sm:text-xl font-black text-white">Programa de Medalhas & Recompensas por Visitas</h3>
                    <p class="text-xs sm:text-sm text-slate-300 max-w-2xl">
                        Defina quantos atendimentos concluídos são necessários para o cliente desbloquear cada nível e informe o benefício que ele ganha (ex: desconto, brinde ou cortesia).
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        @click="$emit('add-loyalty-tier')"
                        class="btn btn-outline !text-white !border-white/20 hover:!bg-white/10 rounded-xl text-xs flex items-center gap-2"
                    >
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Adicionar Nível</span>
                    </button>
                    <button
                        type="button"
                        @click="$emit('save-loyalty-tiers')"
                        :disabled="loyaltyForm.processing"
                        class="btn btn-primary rounded-xl text-xs flex items-center gap-2 shadow-lg shadow-indigo-600/30"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>{{ loyaltyForm.processing ? 'Salvando...' : 'Salvar Regras' }}</span>
                    </button>
                </div>
            </div>

            <div class="space-y-3">
                <div
                    v-for="(tier, idx) in loyaltyTiersList"
                    :key="idx"
                    class="glass-card-3d rounded-2xl p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center gap-4 justify-between"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/15 text-amber-500 flex items-center justify-center text-lg font-black shrink-0">
                            {{ {sparkles:'✨', star:'⭐', heart:'💜', crown:'👑', trophy:'🏆', gem:'💎'}[tier.icon] || '🎖️' }}
                        </div>
                        <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-indigo-500/10 text-indigo-600 dark:text-cyan-400">
                            #{{ idx + 1 }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 flex-1">
                        <div>
                            <label class="form-label text-[10px]">Nome da Medalha / Nível</label>
                            <input v-model="tier.name" class="form-control text-xs font-bold" placeholder="Ex: Cliente VIP Ouro" required />
                        </div>
                        <div>
                            <label class="form-label text-[10px]">Mínimo de Visitas</label>
                            <input type="number" min="1" v-model.number="tier.minimum" class="form-control text-xs font-bold" required />
                        </div>
                        <div>
                            <label class="form-label text-[10px]">Ícone</label>
                            <select v-model="tier.icon" class="form-control text-xs font-bold">
                                <option value="sparkles">✨ Brilho (sparkles)</option>
                                <option value="star">⭐ Estrela (star)</option>
                                <option value="heart">💜 Coração (heart)</option>
                                <option value="crown">👑 Coroa (crown)</option>
                                <option value="trophy">🏆 Troféu (trophy)</option>
                                <option value="gem">💎 Diamante (gem)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-[10px]">Recompensa / Benefício</label>
                            <input v-model="tier.reward" class="form-control text-xs" placeholder="Ex: 10% OFF no corte" />
                        </div>
                    </div>

                    <button
                        v-if="loyaltyTiersList.length > 1"
                        type="button"
                        @click="$emit('remove-loyalty-tier', idx)"
                        class="w-8 h-8 rounded-xl hover:bg-rose-500/10 text-rose-500 flex items-center justify-center self-end lg:self-center shrink-0 cursor-pointer"
                        title="Remover nível"
                    >
                        <i class="fa-solid fa-trash-can text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
