<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    companyReviews: {
        type: Object,
        default: () => ({ data: [], links: [] }),
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

defineEmits(['toggle-company-review']);
</script>

<template>
    <section class="space-y-4">
        <div class="rounded-2xl border border-cyan-500/25 bg-cyan-500/10 px-4 py-3 text-sm text-cyan-800 dark:text-cyan-200">
            <i class="fa-solid fa-circle-info mr-2"></i>Estas são as avaliações gerais da empresa. Diferentemente do feedback por serviço, elas são públicas por padrão.
        </div>

        <div v-if="companyReviews.data?.length" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <article v-for="review in companyReviews.data" :key="review.id" class="glass-card-3d rounded-2xl p-5 space-y-4">
                <div class="flex justify-between gap-3">
                    <div>
                        <p class="font-black" style="color: var(--text-heading);">{{ review.client_name }}</p>
                        <p class="text-xs opacity-55">{{ review.client_email }}</p>
                    </div>
                    <span
                        class="px-2.5 py-1 h-fit rounded-full text-[10px] font-extrabold"
                        :class="review.is_public ? 'bg-emerald-500/15 text-emerald-600' : 'bg-rose-500/15 text-rose-600'"
                    >
                        {{ review.is_public ? 'Visível' : 'Oculta' }}
                    </span>
                </div>
                <div class="text-amber-400">
                    <i v-for="star in 5" :key="star" class="fa-star mr-0.5" :class="star <= review.rating ? 'fa-solid' : 'fa-regular'"></i>
                    <strong class="text-slate-800 dark:text-white ml-2">{{ review.rating }}/5</strong>
                </div>
                <p class="text-sm italic opacity-85">{{ review.comment ? `“${review.comment}”` : 'Avaliação sem comentário.' }}</p>
                <div class="flex items-center justify-between pt-3 border-t" style="border-color: var(--border);">
                    <span class="text-[11px] opacity-55">{{ review.created_at }}</span>
                    <button
                        v-if="hasPermission('clients.reviews')"
                        type="button"
                        :disabled="moderationBusy === `company-${review.id}`"
                        @click="$emit('toggle-company-review', review)"
                        class="btn btn-outline !px-3 !py-2 rounded-xl text-xs"
                    >
                        <i :class="review.is_public ? 'fa-solid fa-eye-slash mr-1' : 'fa-solid fa-eye mr-1'"></i>
                        {{ review.is_public ? 'Ocultar' : 'Publicar' }}
                    </button>
                </div>
            </article>
        </div>

        <div v-else class="glass-card-3d rounded-3xl p-10 text-center">
            <i class="fa-solid fa-building-circle-check text-4xl text-cyan-400 mb-3"></i>
            <h3 class="font-black text-lg">Nenhuma avaliação da empresa</h3>
            <p class="text-sm opacity-60 mt-1">Quando clientes avaliarem o estabelecimento, os comentários aparecerão aqui.</p>
        </div>

        <div v-if="companyReviews.last_page > 1" class="flex flex-wrap justify-center gap-2">
            <Link
                v-for="link in companyReviews.links"
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
