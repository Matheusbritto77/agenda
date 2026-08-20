<script setup>
import { ref } from 'vue';

defineProps({
    canRegister: {
        type: Boolean,
        default: true,
    },
});

const activeFaq = ref(null);

const faqs = [
    {
        question: 'Preciso cadastrar meu cartão de crédito para testar o Agendae?',
        answer: 'Não. O plano Iniciante é 100% gratuito e não exige cartão de crédito. Você pode criar sua conta em menos de 1 minuto e começar a agendar imediatamente.'
    },
    {
        question: 'Como funciona a integração de subdomínio e domínio próprio?',
        answer: 'Toda empresa ganha instantaneamente um subdomínio exclusivo como suaempresa.agendae.app. No plano Pro ou Enterprise, você pode conectar seu domínio próprio (ex: agendamentos.suaempresa.com.br) com certificado SSL automatizado.'
    },
    {
        question: 'Meus clientes precisam baixar algum aplicativo para agendar?',
        answer: 'Não. O Agendae é uma aplicação web progressiva de altíssima velocidade. O cliente acessa seu link via navegador em qualquer celular, tablet ou computador sem precisar instalar nada.'
    },
    {
        question: 'Posso alterar a qualquer momento entre o plano Mensal e Anual?',
        answer: 'Sim. Você pode fazer upgrade, downgrade ou trocar a frequência de cobrança a qualquer momento diretamente pelo seu painel administrativo.'
    }
];

function toggleFaq(index) {
    activeFaq.value = activeFaq.value === index ? null : index;
}
</script>

<template>
    <div>
        <!-- FAQ Section -->
        <section id="faq" class="py-16 sm:py-24 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">
                    Perguntas Frequentes
                </span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white mt-3">
                    Ficou com alguma dúvida?
                </h2>
            </div>

            <div class="space-y-3">
                <div
                    v-for="(faq, idx) in faqs"
                    :key="idx"
                    class="card p-5 border border-slate-200 dark:border-slate-800 shadow-sm cursor-pointer transition-all"
                    @click="toggleFaq(idx)"
                >
                    <div class="flex items-center justify-between gap-4">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ faq.question }}</h4>
                        <i :class="['fa-solid text-xs text-indigo-500 transition-transform', activeFaq === idx ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                    </div>
                    <p v-if="activeFaq === idx" class="text-xs text-slate-500 dark:text-slate-400 mt-3 pt-3 border-t border-slate-200 dark:border-slate-800 leading-relaxed">
                        {{ faq.answer }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Final CTA Banner -->
        <section class="py-16 sm:py-20 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
            <div class="rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 p-8 sm:p-12 text-center text-white space-y-6 shadow-2xl relative overflow-hidden">
                <div class="space-y-2 relative z-10 max-w-2xl mx-auto">
                    <h3 class="text-2xl sm:text-4xl font-black tracking-tight">Comece a transformar seu negócio hoje mesmo</h3>
                    <p class="text-xs sm:text-sm text-white/80 font-medium">Crie sua conta em segundos e libere agendamentos online para todos os seus clientes.</p>
                </div>
                <div class="relative z-10">
                    <a
                        v-if="canRegister"
                        href="/register"
                        class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl font-black text-sm text-indigo-900 bg-white hover:bg-slate-100 shadow-xl transition-all hover:scale-105"
                    >
                        <span>Criar Conta Gratuita Agora</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-8 px-4 border-t border-slate-200 dark:border-slate-800 text-center text-xs text-slate-500">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-black">A</div>
                    <span class="font-bold text-slate-900 dark:text-white">Agendae SaaS 2026</span>
                </div>
                <p>&copy; {{ new Date().getFullYear() }} Agendae. Todos os direitos reservados.</p>
            </div>
        </footer>
    </div>
</template>
