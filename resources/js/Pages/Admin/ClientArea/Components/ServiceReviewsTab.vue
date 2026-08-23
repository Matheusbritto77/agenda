<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    serviceReviews: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    services: {
        type: Array,
        default: () => [],
    },
    filterForm: {
        type: Object,
        required: true,
    },
    moderationBusy: {
        type: String,
        default: null,
    },
    hasPermission: {
        type: Function,
        required: true,
    },
    paginationLabel: {
        type: Function,
        required: true,
    },
});

defineEmits(['submit-filters', 'clear-filters', 'toggle-service-review']);
</script>

<template>
    <section class="space-y-4">
        <form @submit.prevent="$emit('submit-filters')" class="glass-card-3d rounded-2xl p-3 sm:p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <select v-model="filterForm.review_service" class="form-control">
                <option value="">Todos os serviços</option>
                <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
            </select>
            <select v-model="filterForm.review_rating" class="form-control">
                <option value="">Todas as notas</option>
                <option v-for="rating in [5,4,3,2,1]" :key="rating" :value="rating">{{ rating }} estrelas</option>
            </select>
            <select v-model="filterForm.review_visibility" class="form-control">
                <option value="">Internas e públicas</option>
                <option value="internal">Somente internas</option>
                <option value="public">Publicadas</option>
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary rounded-xl flex-1" type="submit">Filtrar</button>
                <button class="btn btn-outline rounded-xl !px-3" type="button" @click="$emit('clear-filters')">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
            </div>
        </form>

        <div v-if="serviceReviews.data?.length" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <article v-for="review in serviceReviews.data" :key="review.id" class="glass-card-3d rounded-2xl p-5 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-black" style="color: var(--text-heading);">{{ review.service }}</p>
                        <p class="text-xs opacity-60">{{ review.client_name }} · {{ review.professional }}</p>
                    </div>
                    <span
                        class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-extrabold"
                        :class="review.is_public ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-slate-500/15 text-slate-600 dark:text-slate-400'"
                    >
                        <i :class="review.is_public ? 'fa-solid fa-globe mr-1' : 'fa-solid fa-lock mr-1'"></i>
                        {{ review.is_public ? 'Página pública' : 'Feedback interno' }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="text-amber-400">
                        <i v-for="star in 5" :key="star" class="fa-star mr-0.5" :class="star <= review.rating ? 'fa-solid' : 'fa-regular'"></i>
                    </div>
                    <strong>{{ review.rating }}/5</strong>
                </div>
                <p class="text-sm leading-relaxed italic" :class="review.comment ? 'opacity-85' : 'opacity-45'">
                    {{ review.comment ? `“${review.comment}”` : 'Avaliação sem comentário.' }}
                </p>
                <div class="flex items-center justify-between gap-3 pt-3 border-t" style="border-color: var(--border);">
                    <span class="text-[11px] opacity-55">Atendimento: {{ review.appointment_date }} · Enviada: {{ review.created_at }}</span>
                    <button
                        v-if="hasPermission('clients.reviews')"
                        type="button"
                        :disabled="moderationBusy === `service-${review.id}`"
                        @click="$emit('toggle-service-review', review)"
                        class="btn btn-outline !px-3 !py-2 rounded-xl text-xs"
                    >
                        <i :class="review.is_public ? 'fa-solid fa-eye-slash mr-1' : 'fa-solid fa-share-nodes mr-1'"></i>
                        {{ review.is_public ? 'Tornar interna' : 'Publicar' }}
                    </button>
                </div>
            </article>
        </div>

        <div v-else class="glass-card-3d rounded-3xl p-10 text-center">
            <i class="fa-solid fa-comments text-4xl text-amber-400 mb-3"></i>
            <h3 class="font-black text-lg">Nenhuma avaliação de serviço</h3>
            <p class="text-sm opacity-60 mt-1">Estes feedbacks são internos por padrão e só aparecem publicamente após moderação.</p>
        </div>

        <div v-if="serviceReviews.last_page > 1" class="flex flex-wrap justify-center gap-2">
            <Link
                v-for="link in serviceReviews.links"
                :key="link.label"
                :href="link.url || '#'"
                preserve-scroll
                preserve-state
                class="px-3 py-2 rounded-xl text-xs font-bold border"
                :class="[link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-800', !link.url ? 'opacity-40 pointer-events-none' : '']"
            >
                {{ paginationLabel(link.label) }}
            </Link>
        </div>
    </section>
</template>
