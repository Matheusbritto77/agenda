<script setup>
defineProps({
    coupons: {
        type: Array,
        default: () => [],
    },
    copiedCouponId: {
        type: [Number, String, null],
        default: null,
    },
});

defineEmits(['copy-coupon']);
</script>

<template>
    <div class="space-y-6">
        <section class="rounded-3xl bg-gradient-to-br from-purple-600 via-indigo-700 to-cyan-600 p-6 sm:p-8 text-white shadow-xl shadow-purple-600/20 relative overflow-hidden">
            <div class="relative z-10 space-y-2">
                <span class="text-xs font-black uppercase tracking-[0.2em] text-white/80">Vouchers & Vantagens</span>
                <h2 class="text-2xl sm:text-3xl font-black">Seus Cupons de Desconto</h2>
                <p class="text-xs sm:text-sm text-white/80 max-w-xl">
                    Copie o código promocional e utilize no momento do agendamento para garantir descontos especiais em seus serviços.
                </p>
            </div>
        </section>

        <div v-if="coupons.length === 0" class="rounded-3xl border border-dashed border-slate-300 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 p-12 text-center space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 flex items-center justify-center mx-auto text-xl">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Nenhum cupom ativo no momento</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Novos cupons e recompensas por visitas serão disponibilizados aqui pelo estabelecimento!</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <article
                v-for="coupon in coupons"
                :key="coupon.id"
                class="rounded-3xl border border-indigo-500/30 bg-white dark:bg-slate-900 p-5 sm:p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4 relative overflow-hidden"
            >
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25">
                            {{ coupon.formatted_discount }}
                        </span>
                        <span v-if="coupon.is_exclusive" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/25">
                            ⭐ Presente Exclusivo
                        </span>
                    </div>

                    <div>
                        <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">
                            {{ coupon.description || 'Desconto no próximo agendamento' }}
                        </h3>
                        <span class="text-[11px] font-bold text-slate-400 block mt-0.5">
                            {{ coupon.company_name }}
                        </span>
                    </div>

                    <!-- Coupon Code Copy Box -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950/80 border border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold text-slate-400 block uppercase">Código</span>
                            <strong class="text-sm font-black tracking-widest text-indigo-600 dark:text-cyan-400 truncate block">
                                {{ coupon.code }}
                            </strong>
                        </div>
                        <button
                            type="button"
                            @click="$emit('copy-coupon', coupon)"
                            class="px-3 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer flex items-center gap-1.5 shrink-0"
                            :class="copiedCouponId === coupon.id ? 'bg-emerald-600 text-white' : 'bg-indigo-600 hover:bg-indigo-500 text-white'"
                        >
                            <i :class="copiedCouponId === coupon.id ? 'fa-solid fa-check' : 'fa-solid fa-copy'" class="text-xs"></i>
                            <span>{{ copiedCouponId === coupon.id ? 'Copiado!' : 'Copiar' }}</span>
                        </button>
                    </div>

                    <div class="space-y-1 text-[11px] text-slate-500 dark:text-slate-400 pt-1">
                        <p v-if="coupon.min_spend">
                            <i class="fa-solid fa-circle-info text-[10px] mr-1 text-slate-400"></i>
                            Válido para serviços a partir de R$ {{ coupon.min_spend.toFixed(2).replace('.', ',') }}
                        </p>
                        <p v-if="coupon.expires_at">
                            <i class="fa-solid fa-calendar-xmark text-[10px] mr-1 text-slate-400"></i>
                            Expira em: {{ coupon.expires_at }}
                        </p>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
