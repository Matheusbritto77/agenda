<script setup>
import { computed } from 'vue';

const props = defineProps({
    companyProfile: {
        type: Object,
        required: true,
    },
    showProfessionalStep: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['start-booking']);

const coverImage = computed(() => props.companyProfile.banner_url || props.companyProfile.logo_url || null);

const statusClasses = computed(() => {
    return props.companyProfile.status?.is_open_now
        ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/25'
        : 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/25';
});

const primaryServices = computed(() => props.companyProfile.services_preview || []);
const primaryProfessionals = computed(() => props.companyProfile.professionals_preview || []);
const getInitials = (name) => (name || 'P').substring(0, 2).toUpperCase();
const hours = computed(() => props.companyProfile.hours_summary || []);
const reviews = computed(() => props.companyProfile.reviews || { average: null, count: 0, items: [] });
const display = computed(() => props.companyProfile.display || {
    show_hours: true,
    show_services: true,
    show_professionals: true,
});
const ctaLabel = computed(() => props.companyProfile.cta_label || 'Agendar agora');
</script>

<template>
    <section class="space-y-5">
        <!-- Hero Header -->
        <div class="overflow-hidden border shadow-sm" :style="{ borderRadius: 'var(--radius, 1rem)', backgroundColor: 'var(--surface, #ffffff)', borderColor: 'var(--border, #e2e8f0)' }">
            <div class="relative min-h-[260px] sm:min-h-[360px] flex items-end">
                <img
                    v-if="coverImage"
                    :src="coverImage"
                    :alt="companyProfile.business_name"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div v-else class="absolute inset-0 bg-slate-900"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-slate-950/15"></div>

                <div class="relative z-10 w-full p-5 sm:p-8 text-white">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">
                        <div class="min-w-0 space-y-4">
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="companyProfile.logo_url"
                                    :src="companyProfile.logo_url"
                                    :alt="companyProfile.business_name"
                                    class="h-14 w-14 rounded-2xl object-cover border border-white/25 bg-white/10"
                                />
                                <div v-else class="h-14 w-14 rounded-2xl border border-white/20 bg-white/10 flex items-center justify-center">
                                    <i class="fa-solid fa-store text-xl"></i>
                                </div>

                                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-extrabold" :class="statusClasses">
                                    <i class="fa-solid fa-circle text-[7px]"></i>
                                    {{ companyProfile.status?.label }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <p v-if="companyProfile.tagline" class="text-xs sm:text-sm font-bold uppercase tracking-wider text-white/80">
                                    {{ companyProfile.tagline }}
                                </p>
                                <h1 class="text-3xl sm:text-5xl font-black leading-tight text-white drop-shadow-sm">
                                    {{ companyProfile.business_name }}
                                </h1>
                                <p class="max-w-2xl text-sm sm:text-base leading-relaxed text-white/90">
                                    {{ companyProfile.description || 'Confira os serviços, horários e profissionais disponíveis para agendar seu atendimento.' }}
                                </p>
                                <div v-if="reviews.count" class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold backdrop-blur-sm">
                                    <span class="text-amber-300" aria-hidden="true">★</span>
                                    <strong>{{ Number(reviews.average).toFixed(1) }}</strong>
                                    <span class="text-white/75">{{ reviews.count }} avaliação{{ reviews.count === 1 ? '' : 'ões' }}</span>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="$emit('start-booking')"
                            class="inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-black transition-all shadow-lg hover:scale-105 shrink-0 cursor-pointer"
                            :style="{
                                backgroundColor: 'var(--primary)',
                                color: 'var(--btn-text, #ffffff)',
                                borderRadius: 'var(--radius-sm, 0.75rem)'
                            }"
                        >
                            <i class="fa-solid fa-calendar-check"></i>
                            <span>{{ ctaLabel }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats & Overview bar -->
            <div class="grid gap-0 border-t md:grid-cols-3" :style="{ borderColor: 'var(--border, #e2e8f0)' }">
                <div v-if="display.show_hours" class="p-5 border-b md:border-b-0 md:border-r" :style="{ borderColor: 'var(--border, #e2e8f0)' }">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-extrabold uppercase tracking-wider opacity-60" :style="{ color: 'var(--text)' }">Agora</p>
                            <p class="text-sm font-black" :style="{ color: 'var(--text-heading)' }">
                                {{ companyProfile.status?.label }}
                            </p>
                            <p class="text-xs opacity-75 mt-1" :style="{ color: 'var(--text-muted)' }">
                                Hoje: {{ companyProfile.status?.today_summary }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="display.show_services" class="p-5 border-b md:border-b-0 md:border-r" :style="{ borderColor: 'var(--border, #e2e8f0)' }">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
                            <i class="fa-solid fa-scissors"></i>
                        </div>
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wider opacity-60" :style="{ color: 'var(--text)' }">Serviços</p>
                            <p class="text-sm font-black" :style="{ color: 'var(--text-heading)' }">
                                {{ companyProfile.services_count }} disponíveis
                            </p>
                            <p class="text-xs opacity-75 mt-1" :style="{ color: 'var(--text-muted)' }">
                                Escolha o serviço na próxima etapa.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="display.show_professionals" class="p-5">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
                            <i class="fa-solid fa-user-group"></i>
                        </div>
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wider opacity-60" :style="{ color: 'var(--text)' }">Profissionais</p>
                            <p class="text-sm font-black" :style="{ color: 'var(--text-heading)' }">
                                {{ (companyProfile.professionals_count || primaryProfessionals.length) > 0 ? `${companyProfile.professionals_count || primaryProfessionals.length} para escolher` : 'Atendimento direto' }}
                            </p>
                            <p class="text-xs opacity-75 mt-1" :style="{ color: 'var(--text-muted)' }">
                                {{ showProfessionalStep ? 'Seleção feita ao agendar.' : 'Sem etapa de profissional.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Showcase Section -->
        <section v-if="display.show_professionals && primaryProfessionals.length" class="card shadow-sm">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-black" :style="{ color: 'var(--text-heading)' }">Nossa Equipe</h2>
                    <p class="text-xs opacity-70" :style="{ color: 'var(--text-muted)' }">Especialistas prontos para lhe atender.</p>
                </div>
                <button
                    type="button"
                    @click="$emit('start-booking')"
                    class="h-9 w-9 rounded-xl inline-flex items-center justify-center transition-opacity hover:opacity-90 cursor-pointer shadow-sm"
                    :style="{ backgroundColor: 'var(--primary)', color: 'var(--btn-text, #ffffff)' }"
                    aria-label="Agendar atendimento"
                >
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                <div
                    v-for="pro in primaryProfessionals"
                    :key="pro.id"
                    @click="$emit('start-booking', pro)"
                    class="p-3.5 rounded-2xl border transition-all hover:scale-102 cursor-pointer flex items-center gap-3 shadow-xs"
                    :style="{
                        borderColor: 'var(--border, #e2e8f0)',
                        backgroundColor: 'var(--surface-hover, rgba(0,0,0,0.02))'
                    }"
                >
                    <div class="w-12 h-12 rounded-2xl overflow-hidden flex items-center justify-center font-bold text-sm shrink-0 shadow-md" :style="{ background: 'var(--primary-gradient)', color: 'var(--btn-text, #ffffff)' }">
                        <img v-if="pro.avatar_url" :src="pro.avatar_url" :alt="pro.name" class="w-full h-full object-cover" />
                        <span v-else>{{ getInitials(pro.name) }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="font-extrabold text-sm truncate" :style="{ color: 'var(--text-heading)' }">{{ pro.name }}</h4>
                        <p class="text-xs font-semibold truncate" :style="{ color: 'var(--primary)' }">{{ pro.job_title || 'Especialista' }}</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-xs opacity-40" :style="{ color: 'var(--text-muted)' }"></i>
                </div>
            </div>
        </section>

        <div class="grid gap-5 lg:grid-cols-[1.15fr_0.85fr]">
            <!-- Featured Services -->
            <section v-if="display.show_services" class="card shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-base font-black" :style="{ color: 'var(--text-heading)' }">Serviços em destaque</h2>
                        <p class="text-xs opacity-70" :style="{ color: 'var(--text-muted)' }">Resumo dos atendimentos disponíveis.</p>
                    </div>
                    <button
                        type="button"
                        @click="$emit('start-booking')"
                        class="h-9 w-9 rounded-xl inline-flex items-center justify-center transition-opacity hover:opacity-90 cursor-pointer shadow-sm"
                        :style="{ backgroundColor: 'var(--primary)', color: 'var(--btn-text, #ffffff)' }"
                        aria-label="Agendar agora"
                    >
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>

                <div v-if="primaryServices.length" class="divide-y" :style="{ borderColor: 'var(--border, #e2e8f0)' }">
                    <div v-for="service in primaryServices" :key="service.id" class="py-3 first:pt-0 last:pb-0 flex items-center gap-3">
                        <img
                            v-if="service.image_url"
                            :src="service.image_url"
                            :alt="service.name"
                            class="h-12 w-12 rounded-xl object-cover"
                            :style="{ backgroundColor: 'var(--primary-light)' }"
                        />
                        <div v-else class="h-12 w-12 rounded-xl flex items-center justify-center" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
                            <i class="fa-solid fa-scissors"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-extrabold truncate" :style="{ color: 'var(--text-heading)' }">{{ service.name }}</p>
                            <p class="text-xs opacity-70 truncate" :style="{ color: 'var(--text-muted)' }">{{ service.duration_minutes }} min</p>
                        </div>
                        <p class="text-sm font-black" :style="{ color: 'var(--primary)' }">{{ service.formatted_price }}</p>
                    </div>
                </div>

                <p v-else class="text-sm opacity-60" :style="{ color: 'var(--text-muted)' }">
                    Nenhum serviço ativo cadastrado no momento.
                </p>
            </section>

            <!-- Hours Summary & Location -->
            <section v-if="display.show_hours || companyProfile.contact?.company_address" class="card shadow-sm space-y-4">
                <div v-if="display.show_hours">
                    <h2 class="text-base font-black mb-4" :style="{ color: 'var(--text-heading)' }">Resumo de horários</h2>

                    <div class="space-y-2">
                        <div
                            v-for="day in hours"
                            :key="day.day"
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span class="font-bold opacity-80" :style="{ color: 'var(--text)' }">{{ day.day }}</span>
                            <span class="text-right text-xs font-semibold" :style="day.is_open ? { color: 'var(--text-heading)' } : { color: 'var(--text-muted)' }">
                                {{ day.summary }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="companyProfile.contact?.company_address" class="pt-4 border-t" :style="{ borderColor: 'var(--border, #e2e8f0)' }">
                    <div class="flex items-start gap-2.5">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
                            <i class="fa-solid fa-location-dot text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wider opacity-60" :style="{ color: 'var(--text)' }">Localização</p>
                            <p class="text-xs font-bold mt-0.5" :style="{ color: 'var(--text-heading)' }">{{ companyProfile.contact.company_address }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section v-if="(display.show_reviews ?? true) && reviews.count" class="card shadow-sm">
            <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wider opacity-60" :style="{ color: 'var(--text-muted)' }">Experiências reais</p>
                    <h2 class="mt-1 text-xl font-black" :style="{ color: 'var(--text-heading)' }">{{ companyProfile.reviews_title || 'O que os clientes dizem' }}</h2>
                    <p class="mt-1 text-xs opacity-70" :style="{ color: 'var(--text-muted)' }">{{ companyProfile.reviews_subtitle || 'Avaliações de atendimentos concluídos nesta empresa.' }}</p>
                </div>
                <div class="flex items-center gap-3 rounded-2xl px-4 py-3" :style="{ backgroundColor: 'var(--primary-light)' }">
                    <span class="text-3xl font-black" :style="{ color: 'var(--primary)' }">{{ Number(reviews.average).toFixed(1) }}</span>
                    <div>
                        <div class="flex gap-0.5 text-amber-400" :aria-label="`${reviews.average} de 5 estrelas`">
                            <i v-for="star in 5" :key="star" class="fa-star text-xs" :class="star <= Math.round(reviews.average) ? 'fa-solid' : 'fa-regular'"></i>
                        </div>
                        <p class="mt-0.5 text-[11px] font-bold" :style="{ color: 'var(--text-muted)' }">{{ reviews.count }} avaliação{{ reviews.count === 1 ? '' : 'ões' }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="review in reviews.items"
                    :key="review.id"
                    class="flex flex-col rounded-2xl border p-4 shadow-xs"
                    :style="{
                        borderColor: 'var(--border)',
                        backgroundColor: 'var(--surface-hover, rgba(0,0,0,0.03))',
                        color: 'var(--text)'
                    }"
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex gap-0.5 text-amber-400" :aria-label="`${review.rating} de 5 estrelas`">
                            <i v-for="star in 5" :key="star" class="fa-star text-xs" :class="star <= review.rating ? 'fa-solid' : 'fa-regular'"></i>
                        </div>
                        <span class="text-[10px] font-semibold opacity-60" :style="{ color: 'var(--text-muted)' }">{{ review.created_at }}</span>
                    </div>
                    <p v-if="review.comment" class="flex-1 text-sm leading-relaxed font-medium" :style="{ color: 'var(--text)' }">“{{ review.comment }}”</p>
                    <p v-else class="flex-1 text-sm italic opacity-60" :style="{ color: 'var(--text-muted)' }">Cliente avaliou este atendimento com {{ review.rating }} estrelas.</p>
                    <div class="mt-4 border-t pt-3 flex items-center gap-3" :style="{ borderColor: 'var(--border)' }">
                        <div
                            class="w-10 h-10 rounded-full overflow-hidden shrink-0 flex items-center justify-center text-xs font-black text-white shadow-xs border border-white/20 dark:border-slate-700/50"
                            :style="{
                                background: 'linear-gradient(135deg, var(--primary) 0%, var(--secondary, var(--primary)) 100%)'
                            }"
                        >
                            <img
                                v-if="review.client_avatar_url"
                                :src="review.client_avatar_url"
                                :alt="review.client_name"
                                class="w-full h-full object-cover"
                            />
                            <span v-else>{{ review.client_initials || review.client_name?.charAt(0)?.toUpperCase() || 'C' }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-black truncate" :style="{ color: 'var(--text-heading)' }">{{ review.client_name }}</p>
                            <p v-if="review.service_name" class="mt-0.5 text-[11px] opacity-75 font-medium truncate" :style="{ color: 'var(--text-muted)' }">{{ review.service_name }} · Atendimento verificado</p>
                        </div>
                    </div>
                </article>
            </div>

            <div class="mt-4 pt-4 border-t flex flex-col sm:flex-row items-center justify-between gap-3 text-xs" :style="{ borderColor: 'var(--border)' }">
                <span class="opacity-75" :style="{ color: 'var(--text-muted)' }">
                    <i class="fa-solid fa-shield-halved text-emerald-500 mr-1"></i>
                    Todas as avaliações são verificadas de clientes reais que agendaram pelo sistema.
                </span>
                <a
                    href="/cliente"
                    class="inline-flex items-center gap-1.5 font-bold hover:underline"
                    :style="{ color: 'var(--primary)' }"
                >
                    <span>Já foi atendido aqui? Deixe sua avaliação na Área do Cliente</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </section>
    </section>
</template>
