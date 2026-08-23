<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    clients: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filterForm: {
        type: Object,
        required: true,
    },
    hasPermission: {
        type: Function,
        required: true,
    },
    currency: {
        type: Function,
        required: true,
    },
    paginationLabel: {
        type: Function,
        required: true,
    },
});

defineEmits(['submit-filters', 'open-client', 'open-edit', 'open-gift']);
</script>

<template>
    <section class="space-y-4">
        <form @submit.prevent="$emit('submit-filters')" class="glass-card-3d rounded-2xl p-3 sm:p-4 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input v-model="filterForm.search" type="search" class="form-control pl-9" placeholder="Buscar por nome, e-mail ou telefone" />
            </div>
            <button type="submit" class="btn btn-primary rounded-xl"><i class="fa-solid fa-search mr-1.5"></i>Buscar</button>
        </form>

        <div v-if="clients.data?.length" class="glass-card-3d rounded-3xl overflow-hidden p-0">
            <div class="table-responsive">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Relacionamento</th>
                            <th>Última visita</th>
                            <th>Total concluído</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
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
                            <td>
                                <p class="font-bold">{{ client.appointments_count }} atendimentos</p>
                                <p class="text-xs opacity-55">{{ client.completed_count }} concluídos · {{ client.reviews_count }} avaliações</p>
                            </td>
                            <td><span class="text-xs font-semibold">{{ client.last_visit || 'Sem visita registrada' }}</span></td>
                            <td><span class="font-black text-emerald-600 dark:text-emerald-400">{{ currency(client.total_spent) }}</span></td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <button
                                        v-if="hasPermission('clients.edit')"
                                        type="button"
                                        @click="$emit('open-gift', client)"
                                        class="btn btn-outline !px-3 !py-2 rounded-xl text-xs text-purple-600 dark:text-purple-400 border-purple-300 dark:border-purple-800 hover:bg-purple-500/10"
                                    >
                                        <i class="fa-solid fa-gift mr-1"></i>Presentear Cupom
                                    </button>
                                    <button type="button" @click="$emit('open-client', client)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs">
                                        <i class="fa-solid fa-clock-rotate-left mr-1"></i>Histórico
                                    </button>
                                    <button v-if="hasPermission('clients.edit')" type="button" @click="$emit('open-edit', client)" class="btn btn-outline !px-3 !py-2 rounded-xl text-xs">
                                        <i class="fa-solid fa-pen mr-1"></i>Editar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else class="glass-card-3d rounded-3xl p-10 text-center">
            <i class="fa-solid fa-user-group text-4xl text-indigo-400 mb-3"></i>
            <h3 class="font-black text-lg">Nenhum cliente encontrado</h3>
            <p class="text-sm opacity-60 mt-1">Os clientes aparecerão aqui após o primeiro agendamento.</p>
        </div>

        <div v-if="clients.last_page > 1" class="flex flex-wrap justify-center gap-2">
            <Link
                v-for="link in clients.links"
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
