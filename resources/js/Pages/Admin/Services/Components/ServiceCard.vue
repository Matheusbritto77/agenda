<script setup>
defineProps({
    services: {
        type: Array,
        default: () => [],
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
    canDelete: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['open-create', 'open-edit', 'open-delete']);

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <div class="card overflow-hidden p-0">
        <template v-if="services.length === 0">
            <div class="text-center py-16 px-4 text-slate-500 dark:text-slate-400">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 flex items-center justify-center mx-auto mb-3 text-2xl">
                    <i class="fa-solid fa-scissors"></i>
                </div>
                <h4 class="text-base font-bold" style="color: var(--text-heading);">Nenhum serviço cadastrado ainda</h4>
                <p class="text-xs opacity-70 mt-1 max-w-sm mx-auto">
                    Cadastre os serviços do seu estabelecimento para que os clientes possam agendar online.
                </p>
                <div v-if="canEdit" class="mt-4">
                    <button type="button" @click="$emit('open-create')" class="btn btn-primary text-xs py-2 px-4">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Adicionar Primeiro Serviço</span>
                    </button>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="w-16">Foto</th>
                            <th>Serviço & Descrição</th>
                            <th>Duração</th>
                            <th>Preço</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="service in services" :key="service.id">
                            <td>
                                <div class="w-12 h-12 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center shrink-0">
                                    <img v-if="service.image_url" :src="service.image_url" :alt="service.name" class="w-full h-full object-cover" />
                                    <i v-else class="fa-solid fa-scissors text-slate-400 text-sm"></i>
                                </div>
                            </td>
                            <td>
                                <div class="font-bold text-sm" style="color: var(--text-heading);">{{ service.name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 max-w-md line-clamp-1 mt-0.5">
                                    {{ service.description || 'Sem descrição' }}
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                    <i class="fa-regular fa-clock text-slate-400 text-xs"></i>
                                    {{ service.duration_minutes }} min
                                </div>
                            </td>
                            <td>
                                <span class="font-extrabold text-sm text-slate-900 dark:text-white">
                                    R$ {{ formatCurrency(service.price) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        v-if="canEdit"
                                        type="button"
                                        @click="$emit('open-edit', service)"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all text-indigo-500"
                                        title="Editar serviço"
                                    >
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button
                                        v-if="canDelete"
                                        type="button"
                                        @click="$emit('open-delete', service)"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-rose-100 dark:hover:bg-rose-950/40 text-rose-500 transition-all"
                                        title="Excluir serviço"
                                    >
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>
