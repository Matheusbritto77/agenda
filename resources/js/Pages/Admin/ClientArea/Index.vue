<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();

const props = defineProps({
    clients: { type: Object, default: () => ({ data: [], links: [] }) },
    serviceReviews: { type: Object, default: () => ({ data: [], links: [] }) },
    companyReviews: { type: Object, default: () => ({ data: [], links: [] }) },
    services: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    scopeLabel: { type: String, default: 'Toda a empresa' },
});

const activeTab = ref('clients');
const selectedClient = ref(null);
const showClientModal = ref(false);
const showEditModal = ref(false);
const moderationBusy = ref(null);

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    return (page.props.auth?.permissions || []).includes(permission);
};

const filterForm = useForm({
    search: props.filters.search || '',
    review_service: props.filters.review_service || '',
    review_rating: props.filters.review_rating || '',
    review_visibility: props.filters.review_visibility || '',
});

const editForm = useForm({
    name: '',
    phone: '',
});

const tabs = computed(() => [
    { id: 'clients', label: 'Clientes', icon: 'fa-solid fa-users', count: props.stats.clients || 0 },
    { id: 'service-reviews', label: 'Avaliações por serviço', icon: 'fa-solid fa-star-half-stroke', count: props.stats.service_reviews || 0 },
    { id: 'company-reviews', label: 'Avaliações públicas', icon: 'fa-solid fa-building-circle-check', count: props.companyReviews.total || 0 },
]);

const submitFilters = () => {
    router.get(route('admin.client-area.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const clearReviewFilters = () => {
    filterForm.review_service = '';
    filterForm.review_rating = '';
    filterForm.review_visibility = '';
    submitFilters();
};

const openClient = (client) => {
    selectedClient.value = client;
    showClientModal.value = true;
};

const closeClient = () => {
    showClientModal.value = false;
    selectedClient.value = null;
};

const openEdit = (client) => {
    selectedClient.value = client;
    editForm.name = client.name || '';
    editForm.phone = client.phone || '';
    editForm.clearErrors();
    showEditModal.value = true;
};

const closeEdit = () => {
    showEditModal.value = false;
    editForm.reset();
};

const saveClient = () => {
    editForm.patch(route('admin.client-area.clients.update', selectedClient.value.id), {
        preserveScroll: true,
        onSuccess: closeEdit,
    });
};

const toggleServiceReview = (review) => {
    moderationBusy.value = `service-${review.id}`;
    router.patch(route('admin.client-area.service-reviews.toggle-public', review.id), {}, {
        preserveScroll: true,
        onFinish: () => { moderationBusy.value = null; },
    });
};

const toggleCompanyReview = (review) => {
    moderationBusy.value = `company-${review.id}`;
    router.patch(route('admin.client-area.company-reviews.toggle-public', review.id), {}, {
        preserveScroll: true,
        onFinish: () => { moderationBusy.value = null; },
    });
};

const currency = (value) => Number(value || 0).toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const statusLabel = (status) => ({
    pending: 'Pendente',
    confirmed: 'Confirmado',
    completed: 'Concluído',
    cancelled: 'Cancelado',
}[status] || status);

const statusClass = (status) => ({
    pending: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    confirmed: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    completed: 'bg-blue-500/15 text-blue-600 dark:text-blue-400',
    cancelled: 'bg-rose-500/15 text-rose-600 dark:text-rose-400',
}[status] || 'bg-slate-500/15 text-slate-500');

const paginationLabel = (label) => {
    if (label.includes('Previous')) return 'Anterior';
    if (label.includes('Next')) return 'Próxima';
    return label.replace('&laquo;', '').replace('&raquo;', '').trim();
};
</script>

<template>
    <AdminLayout>
        <Head title="Área do Cliente - Agendae" />

        <template #header>
            <div class="min-w-0">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Área do Cliente</h1>
                <p class="text-xs opacity-60 hidden sm:block truncate">Relacionamento, histórico e avaliações dos seus clientes</p>
            </div>
        </template>

        <div class="space-y-6">
            <div v-if="page.props.flash?.success" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-bold text-emerald-700 dark:text-emerald-300">
                <i class="fa-solid fa-circle-check mr-2"></i>{{ page.props.flash.success }}
            </div>

            <section class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h2 class="text-2xl font-black tracking-tight" style="color: var(--text-heading);">Relacionamento com clientes</h2>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                            {{ scopeLabel }}
                        </span>
                    </div>
                    <p class="text-sm opacity-65">Consulte atendimentos, edite contatos locais e modere feedbacks sem alterar a conta de acesso do cliente.</p>
                </div>
            </section>

            <section class="grid grid-cols-2 xl:grid-cols-5 gap-3 sm:gap-4">
                <div class="glass-card-3d rounded-2xl p-4 sm:p-5" v-for="card in [
                    { label: 'Clientes', value: stats.clients || 0, icon: 'fa-users', colorClass: 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400' },
                    { label: 'Atendimentos', value: stats.appointments || 0, icon: 'fa-calendar-check', colorClass: 'bg-cyan-500/15 text-cyan-600 dark:text-cyan-400' },
                    { label: 'Concluídos', value: stats.completed || 0, icon: 'fa-circle-check', colorClass: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' },
                    { label: 'Feedbacks internos', value: stats.internal_reviews || 0, icon: 'fa-lock', colorClass: 'bg-amber-500/15 text-amber-600 dark:text-amber-400' },
                    { label: 'Nota média', value: stats.average_rating ? stats.average_rating + '/5' : '—', icon: 'fa-star', colorClass: 'bg-violet-500/15 text-violet-600 dark:text-violet-400' },
                ]" :key="card.label">
                    <div class="flex items-center gap-3">
                        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', card.colorClass]">
                            <i :class="`fa-solid ${card.icon}`"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider opacity-55 truncate">{{ card.label }}</p>
                            <p class="text-xl sm:text-2xl font-black" style="color: var(--text-heading);">{{ card.value }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold border transition-all"
                    :class="activeTab === tab.id
                        ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-600/25'
                        : 'bg-white/70 dark:bg-slate-900/70 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:border-indigo-500/50'"
                >
                    <i :class="tab.icon"></i>
                    <span>{{ tab.label }}</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px]" :class="activeTab === tab.id ? 'bg-white/20' : 'bg-slate-500/10'">{{ tab.count }}</span>
                </button>
            </div>

            <section v-if="activeTab === 'clients'" class="space-y-4">
                <form @submit.prevent="submitFilters" class="glass-card-3d rounded-2xl p-3 sm:p-4 flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input v-model="filterForm.search" type="search" class="form-control pl-9" placeholder="Buscar por nome, e-mail ou telefone" />
                    </div>
                    <button type="submit" class="btn btn-primary rounded-xl"><i class="fa-solid fa-search"></i>Buscar</button>
                </form>

                <div v-if="clients.data?.length" class="glass-card-3d rounded-3xl overflow-hidden p-0">
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead><tr><th>Cliente</th><th>Relacionamento</th><th>Última visita</th><th>Total concluído</th><th class="text-right">Ações</th></tr></thead>
                            <tbody>
                                <tr v-for="client in clients.data" :key="client.id">
                                    <td>
                                        <div class="flex items-center gap-3 min-w-[220px]">
                                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-cyan-500 text-white flex items-center justify-center font-black shrink-0">
                                                {{ (client.name || 'C').substring(0, 2).toUpperCase() }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-extrabold truncate" style="color: var(--text-heading);">{{ client.name }}</p>
                                                <p class="text-xs opacity-60 truncate">{{ client.email }}</p>
                                                <p v-if="client.phone" class="text-xs opacity-60">{{ client.phone }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><p class="font-bold">{{ client.appointments_count }} atendimentos</p><p class="text-xs opacity-55">{{ client.completed_count }} concluídos · {{ client.reviews_count }} avaliações</p></td>
                                    <td><span class="text-xs font-semibold">{{ client.last_visit || 'Sem visita registrada' }}</span></td>
                                    <td><span class="font-black text-emerald-600 dark:text-emerald-400">{{ currency(client.total_spent) }}</span></td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="openClient(client)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs"><i class="fa-solid fa-clock-rotate-left"></i>Histórico</button>
                                            <button v-if="hasPermission('clients.edit')" type="button" @click="openEdit(client)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs"><i class="fa-solid fa-pen"></i>Editar</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else class="glass-card-3d rounded-3xl p-10 text-center">
                    <i class="fa-solid fa-user-group text-4xl text-indigo-400 mb-3"></i><h3 class="font-black text-lg">Nenhum cliente encontrado</h3><p class="text-sm opacity-60 mt-1">Os clientes aparecerão aqui após o primeiro agendamento.</p>
                </div>

                <div v-if="clients.last_page > 1" class="flex flex-wrap justify-center gap-2">
                    <Link v-for="link in clients.links" :key="link.label" :href="link.url || '#'" preserve-scroll preserve-state class="px-3 py-2 rounded-xl text-xs font-bold border" :class="[link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-800', !link.url ? 'opacity-40 pointer-events-none' : '']">{{ paginationLabel(link.label) }}</Link>
                </div>
            </section>

            <section v-if="activeTab === 'service-reviews'" class="space-y-4">
                <form @submit.prevent="submitFilters" class="glass-card-3d rounded-2xl p-3 sm:p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <select v-model="filterForm.review_service" class="form-control"><option value="">Todos os serviços</option><option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option></select>
                    <select v-model="filterForm.review_rating" class="form-control"><option value="">Todas as notas</option><option v-for="rating in [5,4,3,2,1]" :key="rating" :value="rating">{{ rating }} estrelas</option></select>
                    <select v-model="filterForm.review_visibility" class="form-control"><option value="">Internas e públicas</option><option value="internal">Somente internas</option><option value="public">Publicadas</option></select>
                    <div class="flex gap-2"><button class="btn btn-primary rounded-xl flex-1" type="submit">Filtrar</button><button class="btn btn-outline rounded-xl !px-3" type="button" @click="clearReviewFilters"><i class="fa-solid fa-rotate-left"></i></button></div>
                </form>

                <div v-if="serviceReviews.data?.length" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <article v-for="review in serviceReviews.data" :key="review.id" class="glass-card-3d rounded-2xl p-5 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="font-black" style="color: var(--text-heading);">{{ review.service }}</p><p class="text-xs opacity-60">{{ review.client_name }} · {{ review.professional }}</p></div>
                            <span class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-extrabold" :class="review.is_public ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-slate-500/15 text-slate-600 dark:text-slate-400'"><i :class="review.is_public ? 'fa-solid fa-globe mr-1' : 'fa-solid fa-lock mr-1'"></i>{{ review.is_public ? 'Página pública' : 'Feedback interno' }}</span>
                        </div>
                        <div class="flex items-center gap-2"><div class="text-amber-400"><i v-for="star in 5" :key="star" class="fa-star mr-0.5" :class="star <= review.rating ? 'fa-solid' : 'fa-regular'"></i></div><strong>{{ review.rating }}/5</strong></div>
                        <p class="text-sm leading-relaxed italic" :class="review.comment ? 'opacity-85' : 'opacity-45'">{{ review.comment ? `“${review.comment}”` : 'Avaliação sem comentário.' }}</p>
                        <div class="flex items-center justify-between gap-3 pt-3 border-t" style="border-color: var(--border);"><span class="text-[11px] opacity-55">Atendimento: {{ review.appointment_date }} · Enviada: {{ review.created_at }}</span><button v-if="hasPermission('clients.reviews')" type="button" :disabled="moderationBusy === `service-${review.id}`" @click="toggleServiceReview(review)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs"><i :class="review.is_public ? 'fa-solid fa-eye-slash' : 'fa-solid fa-share-nodes'"></i>{{ review.is_public ? 'Tornar interna' : 'Publicar' }}</button></div>
                    </article>
                </div>
                <div v-else class="glass-card-3d rounded-3xl p-10 text-center"><i class="fa-solid fa-comments text-4xl text-amber-400 mb-3"></i><h3 class="font-black text-lg">Nenhuma avaliação de serviço</h3><p class="text-sm opacity-60 mt-1">Estes feedbacks são internos por padrão e só aparecem publicamente após moderação.</p></div>
                <div v-if="serviceReviews.last_page > 1" class="flex flex-wrap justify-center gap-2"><Link v-for="link in serviceReviews.links" :key="link.label" :href="link.url || '#'" preserve-scroll preserve-state class="px-3 py-2 rounded-xl text-xs font-bold border" :class="[link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-800', !link.url ? 'opacity-40 pointer-events-none' : '']">{{ paginationLabel(link.label) }}</Link></div>
            </section>

            <section v-if="activeTab === 'company-reviews'" class="space-y-4">
                <div class="rounded-2xl border border-cyan-500/25 bg-cyan-500/10 px-4 py-3 text-sm text-cyan-800 dark:text-cyan-200"><i class="fa-solid fa-circle-info mr-2"></i>Estas são as avaliações gerais da empresa. Diferentemente do feedback por serviço, elas são públicas por padrão.</div>
                <div v-if="companyReviews.data?.length" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <article v-for="review in companyReviews.data" :key="review.id" class="glass-card-3d rounded-2xl p-5 space-y-4">
                        <div class="flex justify-between gap-3"><div><p class="font-black" style="color: var(--text-heading);">{{ review.client_name }}</p><p class="text-xs opacity-55">{{ review.client_email }}</p></div><span class="px-2.5 py-1 h-fit rounded-full text-[10px] font-extrabold" :class="review.is_public ? 'bg-emerald-500/15 text-emerald-600' : 'bg-rose-500/15 text-rose-600'">{{ review.is_public ? 'Visível' : 'Oculta' }}</span></div>
                        <div class="text-amber-400"><i v-for="star in 5" :key="star" class="fa-star mr-0.5" :class="star <= review.rating ? 'fa-solid' : 'fa-regular'"></i><strong class="text-slate-800 dark:text-white ml-2">{{ review.rating }}/5</strong></div>
                        <p class="text-sm italic opacity-85">{{ review.comment ? `“${review.comment}”` : 'Avaliação sem comentário.' }}</p>
                        <div class="flex items-center justify-between pt-3 border-t" style="border-color: var(--border);"><span class="text-[11px] opacity-55">{{ review.created_at }}</span><button v-if="hasPermission('clients.reviews')" type="button" :disabled="moderationBusy === `company-${review.id}`" @click="toggleCompanyReview(review)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs"><i :class="review.is_public ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>{{ review.is_public ? 'Ocultar' : 'Publicar' }}</button></div>
                    </article>
                </div>
                <div v-else class="glass-card-3d rounded-3xl p-10 text-center"><i class="fa-solid fa-building-circle-check text-4xl text-cyan-400 mb-3"></i><h3 class="font-black text-lg">Nenhuma avaliação da empresa</h3><p class="text-sm opacity-60 mt-1">Quando clientes avaliarem o estabelecimento, os comentários aparecerão aqui.</p></div>
                <div v-if="companyReviews.last_page > 1" class="flex flex-wrap justify-center gap-2"><Link v-for="link in companyReviews.links" :key="link.label" :href="link.url || '#'" preserve-scroll preserve-state class="px-3 py-2 rounded-xl text-xs font-bold border" :class="[link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 dark:border-slate-800', !link.url ? 'opacity-40 pointer-events-none' : '']">{{ paginationLabel(link.label) }}</Link></div>
            </section>
        </div>

        <Teleport to="body">
            <div v-if="showClientModal && selectedClient" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="closeClient">
                <div class="liquid-glass-card w-full max-w-3xl max-h-[90vh] overflow-y-auto p-5 sm:p-7 space-y-5">
                    <div class="flex items-start justify-between gap-4"><div><h3 class="text-xl font-black" style="color: var(--text-heading);">Histórico de {{ selectedClient.name }}</h3><p class="text-xs opacity-60">{{ selectedClient.appointments_count }} atendimentos registrados</p></div><button type="button" @click="closeClient" class="w-9 h-9 rounded-xl hover:bg-slate-500/10"><i class="fa-solid fa-xmark"></i></button></div>
                    <div class="space-y-3">
                        <div v-for="item in selectedClient.history" :key="item.id" class="rounded-2xl border p-4" style="border-color: var(--border); background: var(--surface-subtle);">
                            <div class="flex flex-wrap justify-between gap-3"><div><p class="font-extrabold">{{ item.service }}</p><p class="text-xs opacity-60">{{ item.professional }} · {{ item.date }}</p></div><div class="text-right"><span class="px-2 py-1 rounded-lg text-[10px] font-bold" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span><p class="font-black mt-1">{{ currency(item.price) }}</p></div></div>
                            <div v-if="item.review" class="mt-3 pt-3 border-t text-xs" style="border-color: var(--border);"><span class="text-amber-400 mr-2"><i v-for="star in 5" :key="star" class="fa-star" :class="star <= item.review.rating ? 'fa-solid' : 'fa-regular'"></i></span><span class="italic opacity-70">{{ item.review.comment || 'Sem comentário' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showEditModal && selectedClient" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="closeEdit">
                <form @submit.prevent="saveClient" class="liquid-glass-card w-full max-w-lg p-6 space-y-5">
                    <div class="flex justify-between gap-4"><div><h3 class="text-lg font-black">Editar contato do cliente</h3><p class="text-xs opacity-60 mt-1">A alteração vale para o histórico visível da empresa.</p></div><button type="button" @click="closeEdit"><i class="fa-solid fa-xmark"></i></button></div>
                    <div><label class="form-label">Nome</label><input v-model="editForm.name" class="form-control" required /><p v-if="editForm.errors.name" class="text-xs text-rose-500 mt-1">{{ editForm.errors.name }}</p></div>
                    <div><label class="form-label">Telefone</label><input v-model="editForm.phone" class="form-control" placeholder="(11) 99999-9999" /><p v-if="editForm.errors.phone" class="text-xs text-rose-500 mt-1">{{ editForm.errors.phone }}</p></div>
                    <div class="rounded-xl bg-slate-500/10 p-3 text-xs opacity-70"><i class="fa-solid fa-lock mr-1.5"></i>O e-mail <strong>{{ selectedClient.account_email }}</strong> identifica a conta global do cliente e não pode ser alterado pela empresa.</div>
                    <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline rounded-xl" @click="closeEdit">Cancelar</button><button type="submit" class="btn btn-primary rounded-xl" :disabled="editForm.processing"><i class="fa-solid fa-floppy-disk"></i>{{ editForm.processing ? 'Salvando...' : 'Salvar dados' }}</button></div>
                </form>
            </div>
        </Teleport>
    </AdminLayout>
</template>
