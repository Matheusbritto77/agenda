<script setup>
const features = [
    {
        icon: 'fa-solid fa-globe',
        color: 'text-indigo-500 bg-indigo-500/10',
        title: 'Subdomínio & Domínio Próprio',
        desc: 'Tenha seu link exclusivo suaempresa.agendae.app ou conecte seu próprio domínio personalizado com SSL automático.'
    },
    {
        icon: 'fa-solid fa-qrcode',
        color: 'text-emerald-500 bg-emerald-500/10',
        title: 'Pagamento PIX Integrado',
        desc: 'Receba confirmações de pagamento instantâneas via Mercado Pago ou PIX direto na hora do agendamento.'
    },
    {
        icon: 'fa-solid fa-users',
        color: 'text-blue-500 bg-blue-500/10',
        title: 'Gestão de Múltiplos Profissionais',
        desc: 'Cadastre sua equipe inteira, atribua serviços individuais e calcule comissões de forma 100% automatizada.'
    },
    {
        icon: 'fa-solid fa-palette',
        color: 'text-pink-500 bg-pink-500/10',
        title: 'Personalização Visual Completa',
        desc: 'Adicione seu logotipo, foto de capa, escolha suas cores e personalize rodapé e links de redes sociais.'
    },
    {
        icon: 'fa-solid fa-bell',
        color: 'text-amber-500 bg-amber-500/10',
        title: 'Lembretes e Contato WhatsApp',
        desc: 'Botão flutuante integrado para seus clientes tirarem dúvidas e receberem confirmações rápidas no WhatsApp.'
    },
    {
        icon: 'fa-solid fa-chart-pie',
        color: 'text-purple-500 bg-purple-500/10',
        title: 'Relatórios Financeiros & Métricas',
        desc: 'Acompanhe receita diária, faturamento mensal, serviços mais procurados e comissões da equipe em tempo real.'
    }
];
</script>

<template>
    <section id="recursos" class="py-16 sm:py-24 bg-white/60 dark:bg-slate-900/40 border-y border-slate-200 dark:border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">
                    Recursos Inovadores
                </span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white mt-3">
                    Tudo o que você precisa para gerenciar sua agenda em um só lugar
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2">
                    Projetado para eliminar faltas, otimizar seu tempo e proporcionar a melhor experiência ao cliente.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="feat in features"
                    :key="feat.title"
                    class="card p-6 space-y-3.5 border border-slate-200 dark:border-slate-800/80 hover:border-indigo-500/50 hover:shadow-xl transition-all group"
                >
                    <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-xs group-hover:scale-110 transition-transform', feat.color]">
                        <i :class="feat.icon"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ feat.title }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-normal">{{ feat.desc }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
