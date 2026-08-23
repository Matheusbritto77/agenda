<script setup>
defineProps({
    filteredAppointments: {
        type: Array,
        default: () => [],
    },
    companies: {
        type: Array,
        default: () => [],
    },
    reviewForms: {
        type: Object,
        required: true,
    },
    quickCompliments: {
        type: Array,
        default: () => [],
    },
    statusBadge: {
        type: Function,
        required: true,
    },
    getRatingLabel: {
        type: Function,
        required: true,
    },
});

defineEmits(['switch-tab', 'append-compliment', 'save-review']);
</script>

<template>
    <div class="space-y-4">
        <div v-if="filteredAppointments.length === 0" class="rounded-3xl border border-dashed border-slate-300 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 p-12 text-center space-y-4">
            <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 flex items-center justify-center mx-auto text-2xl">
                <i class="fa-solid fa-calendar-xmark"></i>
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Nenhum agendamento encontrado</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Você não possui agendamentos com este filtro no momento. Visite a página de uma empresa para agendar!
                </p>
            </div>
            <button
                v-if="companies.length > 0"
                type="button"
                @click="$emit('switch-tab', 'companies')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500 transition-all cursor-pointer shadow-md"
            >
                <i class="fa-solid fa-building-store"></i>
                <span>Ver Empresas Visitadas</span>
            </button>
        </div>

        <div v-else class="space-y-5">
            <article
                v-for="appointment in filteredAppointments"
                :key="appointment.id"
                class="rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-all overflow-hidden"
            >
                <!-- Main appointment header/body -->
                <div class="p-5 sm:p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">
                                    {{ appointment.service }}
                                </h3>
                                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold', statusBadge(appointment.status).classes]">
                                    <i :class="statusBadge(appointment.status).icon" class="text-[10px]"></i>
                                    <span>{{ statusBadge(appointment.status).label }}</span>
                                </span>
                            </div>

                            <!-- Company & Professional info -->
                            <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                                <div class="flex items-center gap-1.5 font-bold text-slate-800 dark:text-slate-200">
                                    <i class="fa-solid fa-store text-indigo-500 text-xs"></i>
                                    <span>{{ appointment.company }}</span>
                                </div>
                                <div v-if="appointment.professional && appointment.show_professionals !== false" class="flex items-center gap-1.5 font-medium text-slate-500 dark:text-slate-400">
                                    <i class="fa-solid fa-user text-xs"></i>
                                    <span>Atendido por: <strong class="text-slate-700 dark:text-slate-300">{{ appointment.professional }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Date & Time Badge + Price -->
                        <div class="flex sm:flex-col items-center sm:items-end justify-between gap-2 border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100 dark:border-slate-800">
                            <div class="text-left sm:text-right">
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-black">
                                    <i class="fa-regular fa-calendar text-indigo-500"></i>
                                    <span>{{ appointment.date }}</span>
                                    <span class="opacity-40">|</span>
                                    <i class="fa-regular fa-clock text-indigo-500"></i>
                                    <span>{{ appointment.time }}</span>
                                </div>
                                <span class="block text-[11px] font-bold text-slate-400 mt-1">Duração: {{ appointment.duration_minutes }} min</span>
                            </div>
                            <div v-if="appointment.show_service_prices !== false" class="text-right">
                                <span class="text-base sm:text-lg font-black text-indigo-600 dark:text-cyan-400">{{ appointment.service_price }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions bar (Visit company page / Re-book) -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3">
                            <a
                                v-if="appointment.company_booking_url"
                                :href="appointment.company_booking_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 font-bold text-indigo-600 dark:text-cyan-400 hover:underline"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square text-[11px]"></i>
                                <span>Visitar Página da Empresa</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                v-if="appointment.company_booking_url"
                                :href="appointment.company_booking_url"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 text-slate-700 dark:text-slate-200 hover:text-indigo-600 font-bold transition-all"
                            >
                                <i class="fa-solid fa-calendar-plus text-xs"></i>
                                <span>Novo Agendamento</span>
                            </a>

                            <button
                                v-if="appointment.can_review && (!appointment.review || reviewForms[appointment.id]?.isEditing)"
                                type="button"
                                @click="reviewForms[appointment.id].isEditing = !reviewForms[appointment.id].isEditing"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-400 hover:bg-amber-500/20 font-bold transition-all cursor-pointer"
                            >
                                <i class="fa-solid fa-star text-xs"></i>
                                <span>{{ appointment.review ? 'Fechar Edição' : 'Avaliar Atendimento' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Interactive Review Box (For completed appointments) -->
                <div v-if="appointment.can_review && reviewForms[appointment.id]" class="border-t border-slate-200/70 dark:border-slate-800/70 bg-slate-50/70 dark:bg-slate-950/40 p-5 sm:p-6">
                    <!-- Already reviewed display state -->
                    <div v-if="appointment.review && !reviewForms[appointment.id].isEditing" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <div class="flex gap-1 text-amber-400 text-base">
                                    <i v-for="s in 5" :key="s" class="fa-star" :class="s <= appointment.review.rating ? 'fa-solid text-amber-400' : 'fa-regular text-slate-300 dark:text-slate-700'"></i>
                                </div>
                                <span class="text-xs font-black text-slate-900 dark:text-white">{{ getRatingLabel(appointment.review.rating) }}</span>
                                <span v-if="appointment.review.updated_at" class="text-[10px] text-slate-400 font-semibold">• Avaliado em {{ appointment.review.updated_at }}</span>
                            </div>
                            <p v-if="appointment.review.comment" class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 italic">
                                "{{ appointment.review.comment }}"
                            </p>
                            <p v-else class="text-xs text-slate-400 italic">Você avaliou com {{ appointment.review.rating }} estrelas sem comentário adicional.</p>
                        </div>

                        <button
                            type="button"
                            @click="reviewForms[appointment.id].isEditing = true"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 transition-all self-start sm:self-auto cursor-pointer"
                        >
                            <i class="fa-solid fa-pen-to-square text-xs text-indigo-500"></i>
                            <span>Editar Avaliação</span>
                        </button>
                    </div>

                    <!-- Review Form (Creating or Editing) -->
                    <form v-else @submit.prevent="$emit('save-review', appointment)" class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-star text-amber-400"></i>
                                    <span>{{ appointment.review ? 'Editar sua avaliação' : 'Avaliação do Atendimento & Serviço' }}</span>
                                </h4>
                                <p class="text-xs text-slate-500">Seu feedback é enviado diretamente para a administração do estabelecimento e para o profissional responsável.</p>
                            </div>

                            <!-- Interactive Star Rating Picker -->
                            <div class="flex items-center gap-1 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs self-start sm:self-auto">
                                <button
                                    v-for="star in 5"
                                    :key="star"
                                    type="button"
                                    @click="reviewForms[appointment.id].rating = star"
                                    @mouseenter="reviewForms[appointment.id].hoverRating = star"
                                    @mouseleave="reviewForms[appointment.id].hoverRating = 0"
                                    class="p-1 text-2xl transition-transform hover:scale-125 focus:outline-none cursor-pointer"
                                    :title="getRatingLabel(star)"
                                >
                                    <i
                                        class="fa-star"
                                        :class="star <= (reviewForms[appointment.id].hoverRating || reviewForms[appointment.id].rating) ? 'fa-solid text-amber-400 drop-shadow-xs' : 'fa-regular text-slate-300 dark:text-slate-700'"
                                    ></i>
                                </button>
                                <span class="ml-2 text-xs font-black text-slate-700 dark:text-slate-200 min-w-[110px]">
                                    {{ getRatingLabel(reviewForms[appointment.id].hoverRating || reviewForms[appointment.id].rating) }}
                                </span>
                            </div>
                        </div>

                        <!-- Quick Compliment Chips -->
                        <div class="space-y-1.5">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Elogios Rápidos (clique para adicionar):</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="chip in quickCompliments"
                                    :key="chip"
                                    type="button"
                                    @click="$emit('append-compliment', appointment.id, chip)"
                                    class="px-2.5 py-1 rounded-xl text-[11px] font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-cyan-400 transition-all cursor-pointer shadow-2xs"
                                >
                                    {{ chip }}
                                </button>
                            </div>
                        </div>

                        <!-- Comment Textarea & Submit -->
                        <div class="space-y-2">
                            <textarea
                                v-model="reviewForms[appointment.id].comment"
                                rows="2"
                                maxlength="2000"
                                placeholder="Escreva detalhes sobre o que você mais gostou ou pontos a melhorar... (opcional)"
                                class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all shadow-xs resize-y min-h-[70px]"
                            ></textarea>

                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[11px] text-slate-400">
                                    {{ reviewForms[appointment.id].comment ? reviewForms[appointment.id].comment.length : 0 }} / 2000 caracteres
                                </span>

                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="appointment.review"
                                        type="button"
                                        @click="reviewForms[appointment.id].isEditing = false"
                                        class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 cursor-pointer"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="reviewForms[appointment.id].saving"
                                        class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black shadow-md shadow-indigo-600/30 transition-all cursor-pointer disabled:opacity-50"
                                    >
                                        <i v-if="reviewForms[appointment.id].saving" class="fa-solid fa-spinner fa-spin"></i>
                                        <i v-else class="fa-solid fa-paper-plane text-xs"></i>
                                        <span>{{ reviewForms[appointment.id].saving ? 'Enviando...' : (appointment.review ? 'Atualizar Avaliação' : 'Enviar Avaliação') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </article>
        </div>
    </div>
</template>
