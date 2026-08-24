<script setup>
defineProps({
    companies: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['select-company', 'open-company-review']);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Empresas & Estabelecimentos</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Acesse a página pública de agendamento e avalie suas experiências.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 border border-indigo-500/20 self-start sm:self-auto">
                {{ companies.length }} empresa(s) visitada(s)
            </span>
        </div>

        <div v-if="companies.length === 0" class="rounded-3xl border border-dashed border-slate-300 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 p-12 text-center space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 flex items-center justify-center mx-auto text-xl">
                <i class="fa-solid fa-store"></i>
            </div>
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Nenhuma empresa no histórico</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Assim que você realizar seu primeiro agendamento, o estabelecimento aparecerá aqui.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <article
                v-for="company in companies"
                :key="company.id"
                class="rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group"
            >
                <div class="p-5 sm:p-6 space-y-4">
                    <!-- Company Header -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div
                                class="w-12 h-12 rounded-2xl overflow-hidden text-white flex items-center justify-center font-black text-base shadow-md shrink-0"
                                :style="{ background: `linear-gradient(135deg, ${company.primary_color || '#6366f1'}, ${company.secondary_color || '#06b6d4'})` }"
                            >
                                <img v-if="company.logo_url" :src="company.logo_url" :alt="company.name" class="w-full h-full object-cover" />
                                <i v-else class="fa-solid fa-store"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-black text-base text-slate-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-cyan-400 transition-colors">
                                    {{ company.name }}
                                </h3>
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 block">
                                    {{ company.services_count }} atendimento(s) realizado(s)
                                </span>
                            </div>
                        </div>

                        <span v-if="company.badge" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/25 shrink-0">
                            {{ company.badge.name }}
                        </span>
                    </div>

                    <!-- Professionals list -->
                    <div v-if="company.show_professionals !== false && company.professionals && company.professionals.length" class="space-y-1 text-xs">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Profissionais que te atenderam:</span>
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="prof in company.professionals"
                                :key="prof"
                                class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-[11px]"
                            >
                                {{ prof }}
                            </span>
                        </div>
                    </div>

                    <!-- Existing Company Review Display -->
                    <div v-if="company.show_reviews !== false && company.company_review" class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-between gap-2">
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400 block">Sua Avaliação Pública</span>
                            <div class="flex items-center gap-1 text-amber-400 text-xs">
                                <i v-for="s in 5" :key="s" class="fa-star" :class="s <= company.company_review.rating ? 'fa-solid' : 'fa-regular'"></i>
                                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 ml-1">{{ company.company_review.rating }}.0</span>
                            </div>
                            <p v-if="company.company_review.comment" class="text-[11px] text-slate-600 dark:text-slate-300 italic truncate max-w-[200px]">
                                "{{ company.company_review.comment }}"
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="$emit('open-company-review', company)"
                            class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition-all cursor-pointer shadow-2xs"
                        >
                            Editar
                        </button>
                    </div>
                </div>

                <!-- Card Action Buttons -->
                <div class="p-5 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/40 space-y-2">
                    <!-- Switch Portal Context to this company -->
                    <div
                        v-if="company.is_active"
                        class="w-full py-2.5 px-3 rounded-xl text-xs font-black bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25 flex items-center justify-center gap-1.5"
                    >
                        <i class="fa-solid fa-circle-check text-xs"></i>
                        <span>Espaço Selecionado (Ativo)</span>
                    </div>
                    <button
                        v-else
                        type="button"
                        @click="$emit('select-company', company.id)"
                        class="w-full py-2.5 px-3 rounded-xl text-xs font-black text-white shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                        :style="{ backgroundColor: company.primary_color || '#6366f1' }"
                    >
                        <i class="fa-solid fa-arrow-right-to-bracket text-xs"></i>
                        <span>Acessar Espaço Desta Empresa</span>
                    </button>

                    <!-- Direct link to visit the public company page -->
                    <a
                        v-if="company.booking_url"
                        :href="company.booking_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-white border border-slate-200 dark:border-slate-700 transition-all flex items-center justify-center gap-2 shadow-2xs"
                    >
                        <i class="fa-solid fa-globe text-cyan-500"></i>
                        <span>Página Pública da Empresa</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px] opacity-60"></i>
                    </a>

                    <div class="grid gap-2" :class="company.show_reviews !== false ? 'grid-cols-2' : 'grid-cols-1'">
                        <a
                            v-if="company.booking_url"
                            :href="company.booking_url"
                            class="py-2 px-3 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition-all flex items-center justify-center gap-1.5 shadow-2xs"
                        >
                            <i class="fa-solid fa-calendar-plus text-xs text-indigo-600 dark:text-cyan-400"></i>
                            <span>Agendar</span>
                        </a>

                        <button
                            v-if="company.show_reviews !== false"
                            type="button"
                            @click="$emit('open-company-review', company)"
                            class="py-2 px-3 rounded-xl text-xs font-bold bg-amber-500/15 hover:bg-amber-500/25 text-amber-700 dark:text-amber-300 border border-amber-500/25 transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-star text-xs"></i>
                            <span>{{ company.company_review ? 'Editar' : 'Avaliar' }}</span>
                        </button>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
