<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    company: {
        type: Object,
        default: null,
    },
    companyReviewForm: {
        type: Object,
        required: true,
    },
    companyQuickCompliments: {
        type: Array,
        default: () => [],
    },
    getRatingLabel: {
        type: Function,
        required: true,
    },
});

defineEmits(['close', 'save', 'append-compliment']);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show && company"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md"
            @click.self="$emit('close')"
        >
            <div class="w-full max-w-lg rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl p-6 sm:p-7 space-y-6 relative overflow-hidden" @click.stop>
                <!-- Modal Header -->
                <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-12 h-12 rounded-2xl overflow-hidden bg-gradient-to-tr from-indigo-600 to-cyan-500 text-white flex items-center justify-center font-black text-base shadow-md shrink-0">
                            <img v-if="company.logo_url" :src="company.logo_url" :alt="company.name" class="w-full h-full object-cover" />
                            <i v-else class="fa-solid fa-store"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white truncate">
                                    {{ company.name }}
                                </h3>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 mt-1">
                                <i class="fa-solid fa-globe text-[9px]"></i>
                                <span>Avaliação Pública da Empresa</span>
                            </span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="w-8 h-8 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 transition-all cursor-pointer"
                    >
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <form @submit.prevent="$emit('save')" class="space-y-5">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Sua Nota para o Estabelecimento</label>
                            <span class="text-xs font-black text-amber-500">
                                {{ getRatingLabel(companyReviewForm.hoverRating || companyReviewForm.rating) }}
                            </span>
                        </div>

                        <!-- Star Rating Picker -->
                        <div class="flex items-center justify-center gap-2 bg-slate-50 dark:bg-slate-950/60 p-3.5 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                @click="companyReviewForm.rating = star"
                                @mouseenter="companyReviewForm.hoverRating = star"
                                @mouseleave="companyReviewForm.hoverRating = 0"
                                class="p-1.5 text-3xl transition-transform hover:scale-125 focus:outline-none cursor-pointer"
                                :title="getRatingLabel(star)"
                            >
                                <i
                                    class="fa-star"
                                    :class="star <= (companyReviewForm.hoverRating || companyReviewForm.rating) ? 'fa-solid text-amber-400 drop-shadow-sm' : 'fa-regular text-slate-300 dark:text-slate-700'"
                                ></i>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Compliments for Company -->
                    <div class="space-y-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Elogios Rápidos para a Empresa:</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="chip in companyQuickCompliments"
                                :key="chip"
                                type="button"
                                @click="$emit('append-compliment', chip)"
                                class="px-2.5 py-1 rounded-xl text-[11px] font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-cyan-400 transition-all cursor-pointer shadow-2xs"
                            >
                                {{ chip }}
                            </button>
                        </div>
                    </div>

                    <!-- Testimonial Textarea -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Seu Depoimento / Comentário Público</label>
                        <textarea
                            v-model="companyReviewForm.comment"
                            rows="3"
                            maxlength="2000"
                            placeholder="Escreva como foi sua experiência geral com este estabelecimento, ambiente, atendimento e estrutura..."
                            class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all shadow-xs resize-y min-h-[90px]"
                        ></textarea>
                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span>Esta mensagem será exibida na página pública da empresa.</span>
                            <span>{{ companyReviewForm.comment ? companyReviewForm.comment.length : 0 }} / 2000</span>
                        </div>
                    </div>

                    <!-- Modal Action Buttons -->
                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-all cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="companyReviewForm.processing"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-xs font-black shadow-lg shadow-amber-500/25 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <i v-if="companyReviewForm.processing" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-star text-xs"></i>
                            <span>{{ companyReviewForm.processing ? 'Publicando...' : (company?.company_review ? 'Atualizar Avaliação Pública' : 'Publicar Avaliação na Página') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
