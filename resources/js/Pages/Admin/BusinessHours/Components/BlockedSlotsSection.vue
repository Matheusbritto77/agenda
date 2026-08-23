<script setup>
defineProps({
    blockedSlots: {
        type: Array,
        default: () => [],
    },
    canManageBlocks: {
        type: Boolean,
        default: false,
    },
    selectedMember: {
        type: Object,
        default: null,
    },
});

defineEmits(['open-create-block', 'open-edit-block', 'open-delete-block']);

const formatDateTime = (value) => {
    if (!value) return '';
    const d = new Date(value);
    return d.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <div class="card overflow-hidden p-0 shadow-sm">
        <div class="p-5 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
            <div>
                <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">Bloqueios Especiais & Feriados</h3>
                <p class="text-xs opacity-60">Impeça agendamentos em datas festivas, folgas particulares ou intervalos de profissionais</p>
            </div>
            <button
                v-if="canManageBlocks"
                type="button"
                @click="$emit('open-create-block')"
                class="btn btn-primary text-xs py-2 px-3.5 self-start sm:self-auto cursor-pointer"
            >
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Novo Bloqueio</span>
            </button>
        </div>

        <div v-if="blockedSlots.length === 0" class="text-center py-12 px-4 text-slate-500">
            <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mx-auto mb-3 text-xl">
                <i class="fa-solid fa-ban"></i>
            </div>
            <h4 class="text-sm font-bold" style="color: var(--text-heading);">Nenhum bloqueio programado</h4>
            <p class="text-xs opacity-70 mt-1">Todos os dias e horários seguem a grade semanal normal.</p>
        </div>

        <div v-else class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Motivo / Título</th>
                        <th>Aplicado Para</th>
                        <th>Início do Bloqueio</th>
                        <th>Término do Bloqueio</th>
                        <th>Status</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="block in blockedSlots" :key="block.id">
                        <td>
                            <div class="font-bold text-sm" style="color: var(--text-heading);">
                                {{ block.reason || 'Bloqueio de Horário' }}
                            </div>
                        </td>
                        <td>
                            <span
                                v-if="block.team_member || block.team_member_id"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30"
                            >
                                <i class="fa-solid fa-user text-[10px]"></i>
                                <span>{{ block.team_member?.name || `Profissional #${block.team_member_id}` }}</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                            >
                                <i class="fa-solid fa-building text-[10px]"></i>
                                <span>Toda a Empresa</span>
                            </span>
                        </td>
                        <td>
                            <div class="text-xs sm:text-sm font-medium">
                                {{ formatDateTime(block.starts_at) }}
                            </div>
                        </td>
                        <td>
                            <div class="text-xs sm:text-sm font-medium">
                                {{ formatDateTime(block.ends_at) }}
                            </div>
                        </td>
                        <td>
                            <span :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold', block.is_active ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30' : 'bg-slate-200 dark:bg-slate-800 text-slate-500']">
                                {{ block.is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    v-if="canManageBlocks"
                                    type="button"
                                    @click="$emit('open-edit-block', block)"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-700 text-indigo-500 transition-all cursor-pointer"
                                    title="Editar bloqueio"
                                >
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                <button
                                    v-if="canManageBlocks"
                                    type="button"
                                    @click="$emit('open-delete-block', block)"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-rose-100 dark:hover:bg-rose-950/40 text-rose-500 transition-all cursor-pointer"
                                    title="Excluir bloqueio"
                                >
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
