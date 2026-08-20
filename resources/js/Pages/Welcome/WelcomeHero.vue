<script setup>
defineProps({
    canRegister: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <section class="relative pt-12 sm:pt-20 pb-16 sm:pb-28 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto text-center">
        <!-- Floating Pill Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 shadow-sm mb-6 animate-bounce">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            <span>NOVO: Multi-Tenancy com Subdomínio Próprio & PIX Automático</span>
        </div>

        <!-- Hero Main Headline -->
        <h1 class="text-3xl sm:text-5xl lg:text-7xl font-black tracking-tight leading-[1.1] max-w-4xl mx-auto mb-6 text-slate-900 dark:text-white">
            Automatize seus Agendamentos e <span class="bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 bg-clip-text text-transparent">Multiplique suas Vendas</span>
        </h1>

        <!-- Subtitle -->
        <p class="text-sm sm:text-lg lg:text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed font-medium">
            A plataforma completa para barbearias, clínicas, consultorias e profissionais autônomos. Link de agendamento personalizado, pagamentos integrados e zero burocracia.
        </p>

        <!-- CTA Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 max-w-md mx-auto">
            <a
                v-if="canRegister"
                href="/register"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 rounded-2xl font-black text-sm sm:text-base bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 text-white shadow-xl shadow-indigo-600/30 hover:shadow-indigo-600/50 hover:scale-105 transition-all btn-motion-3d"
            >
                <span>Criar Conta Gratuita</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>

            <a
                href="#demo"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl font-bold text-sm sm:text-base text-slate-700 dark:text-slate-300 bg-white/80 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all shadow-sm"
            >
                <i class="fa-solid fa-play text-indigo-600 text-xs"></i>
                <span>Ver Demonstração</span>
            </a>
        </div>

        <!-- Social Proof Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-4xl mx-auto mt-16 pt-12 border-t border-slate-200 dark:border-slate-800/80">
            <div>
                <span class="text-2xl sm:text-4xl font-black text-indigo-600 dark:text-indigo-400 block">+15.000</span>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Agendamentos Criados</span>
            </div>
            <div>
                <span class="text-2xl sm:text-4xl font-black text-cyan-600 dark:text-cyan-400 block">99.9%</span>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Disponibilidade</span>
            </div>
            <div>
                <span class="text-2xl sm:text-4xl font-black text-blue-600 dark:text-blue-400 block">30 seg</span>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Tempo Médio de Reserva</span>
            </div>
            <div>
                <span class="text-2xl sm:text-4xl font-black text-emerald-600 dark:text-emerald-400 block">0%</span>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Taxa de Adesão</span>
            </div>
        </div>
    </section>
</template>
