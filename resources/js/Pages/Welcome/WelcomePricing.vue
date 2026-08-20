<script setup>
import { ref } from 'vue';

const isAnnual = ref(true);

const plans = [
    {
        name: 'Iniciante',
        desc: 'Ideal para profissionais autônomos iniciando seus agendamentos online.',
        priceMonthly: 'Grátis',
        priceAnnual: 'Grátis',
        period: 'para sempre',
        popular: false,
        features: [
            'Até 50 agendamentos/mês',
            'Subdomínio exclusivo',
            'Painel administrativo básico',
            '1 profissional cadastrado',
            'Suporte via e-mail'
        ],
        ctaText: 'Começar Grátis',
        ctaLink: '/register',
        btnClass: 'btn-outline'
    },
    {
        name: 'Profissional',
        desc: 'Para barbearias, salões, clínicas e empresas em crescimento.',
        priceMonthly: 'R$ 49,90',
        priceAnnual: 'R$ 39,90',
        period: '/mês',
        popular: true,
        features: [
            'Agendamentos ilimitados',
            'Múltiplos profissionais e comissões',
            'Pagamento PIX automatizado',
            'Personalização visual completa',
            'WhatsApp flutuante & lembretes',
            'Relatórios financeiros e métricas',
            'Suporte prioritário'
        ],
        ctaText: 'Testar Plano Profissional',
        ctaLink: '/register',
        btnClass: 'btn-primary shadow-lg shadow-indigo-600/30'
    },
    {
        name: 'Enterprise',
        desc: 'Para redes, franquias e negócios que precisam de suporte dedicado.',
        priceMonthly: 'R$ 119,90',
        priceAnnual: 'R$ 99,90',
        period: '/mês',
        popular: false,
        features: [
            'Tudo do plano Profissional',
            'Domínio personalizado próprio',
            'Múltiplas filiais / locais',
            'Acesso antecipado a novas funções',
            'Gerente de conta dedicado',
            'Treinamento de equipe'
        ],
        ctaText: 'Falar com Consultor',
        ctaLink: '/register',
        btnClass: 'btn-outline'
    }
];
</script>

<template>
    <section id="planos" class="py-16 sm:py-24 bg-white/60 dark:bg-slate-900/40 border-y border-slate-200 dark:border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <span class="text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">
                    Planos Transparentes
                </span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white mt-3">
                    Invista no crescimento do seu negócio
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2">
                    Sem taxas ocultas. Cancele ou altere seu plano quando quiser.
                </p>

                <!-- Billing Toggle Switch -->
                <div class="flex items-center justify-center gap-3 mt-6">
                    <span :class="['text-xs font-bold', !isAnnual ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500']">Mensal</span>
                    <button
                        type="button"
                        @click="isAnnual = !isAnnual"
                        class="w-12 h-6 rounded-full bg-indigo-600 p-1 flex items-center transition-all cursor-pointer relative"
                    >
                        <div :class="['w-4 h-4 rounded-full bg-white transition-transform', isAnnual ? 'translate-x-6' : 'translate-x-0']"></div>
                    </button>
                    <div class="flex items-center gap-1.5">
                        <span :class="['text-xs font-bold', isAnnual ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500']">Anual</span>
                        <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                            Economize 20%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pricing Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 items-stretch">
                <div
                    v-for="plan in plans"
                    :key="plan.name"
                    :class="[
                        'card p-6 sm:p-8 space-y-6 flex flex-col justify-between relative transition-all',
                        plan.popular ? 'border-indigo-600 shadow-2xl scale-102 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800'
                    ]"
                >
                    <div v-if="plan.popular" class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-md">
                        Mais Escolhido
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ plan.name }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 min-h-[32px]">{{ plan.desc }}</p>
                        </div>

                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white">
                                {{ isAnnual ? plan.priceAnnual : plan.priceMonthly }}
                            </span>
                            <span class="text-xs font-semibold text-slate-500">{{ plan.period }}</span>
                        </div>

                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-2.5">
                            <div v-for="feat in plan.features" :key="feat" class="flex items-center gap-2.5 text-xs text-slate-600 dark:text-slate-300">
                                <i class="fa-solid fa-check text-emerald-500 text-[11px] shrink-0"></i>
                                <span>{{ feat }}</span>
                            </div>
                        </div>
                    </div>

                    <a
                        :href="plan.ctaLink"
                        :class="['w-full py-3 px-4 rounded-xl font-bold text-xs sm:text-sm text-center transition-all', plan.btnClass]"
                    >
                        {{ plan.ctaText }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
