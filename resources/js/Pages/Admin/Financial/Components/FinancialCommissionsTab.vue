<script setup>
defineProps({
    stats: {
        type: Object,
        required: true,
    },
    hasPermission: {
        type: Function,
        required: true,
    },
    getInitials: {
        type: Function,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
    formatPercent: {
        type: Function,
        required: true,
    },
});

defineEmits(['export-appointments']);
</script>

<template>
    <div class="space-y-4">
        <div class="card p-0 overflow-hidden shadow-sm">
            <div class="p-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
                <div>
                    <h4 class="font-black text-xs sm:text-sm" style="color: var(--text-heading);">Tabela Consolidada de Repasse por Profissional</h4>
                    <p class="text-[11px] text-slate-400 mt-0.5">Valores calculados automaticamente com base nas taxas de comissão e atendimentos do período</p>
                </div>
                <button
                    v-if="hasPermission('reports.export')"
                    type="button"
                    @click="$emit('export-appointments')"
                    class="btn btn-outline py-1.5 px-3 text-xs flex items-center gap-1.5 rounded-xl hover:text-emerald-500 hover:border-emerald-500"
                >
                    <i class="fa-solid fa-file-csv text-emerald-500"></i>
                    <span>Exportar CSV</span>
                </button>
            </div>
            <div class="table-responsive">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th>Profissional</th>
                            <th>Cargo</th>
                            <th>Atendimentos</th>
                            <th>Faturamento Gerado</th>
                            <th>Comissão Padrão</th>
                            <th class="text-right">Total a Repassar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="stats.members_data && stats.members_data.length > 0">
                            <tr v-for="(data, idx) in stats.members_data" :key="idx">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 border flex items-center justify-center shrink-0">
                                            <img v-if="data.member?.avatar_url" :src="data.member.avatar_url" alt="" class="w-full h-full object-cover">
                                            <div v-else class="w-full h-full bg-indigo-600 flex items-center justify-center text-white text-[11px] font-bold">
                                                {{ getInitials(data.member?.name) }}
                                            </div>
                                        </div>
                                        <span class="font-bold text-xs sm:text-sm" style="color: var(--text-heading);">{{ data.member?.name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ data.member?.job_title ?? 'Profissional' }}</span>
                                </td>
                                <td>
                                    <span class="text-xs font-medium">{{ data.count }} serviços</span>
                                </td>
                                <td>
                                    <span class="text-xs font-mono">R$ {{ formatCurrency(data.revenue) }}</span>
                                </td>
                                <td>
                                    <span class="text-xs font-mono font-bold">{{ formatPercent(data.rate) }}%</span>
                                </td>
                                <td class="text-right">
                                    <strong class="text-rose-600 dark:text-rose-400 font-mono font-black text-sm">R$ {{ formatCurrency(data.commission) }}</strong>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="6" class="text-center py-10 opacity-60 text-xs">Nenhum profissional com atendimento no período.</td>
                        </tr>
                        <tr v-if="stats.unassigned_revenue > 0" class="bg-slate-50/50 dark:bg-slate-900/30">
                            <td>
                                <span class="font-bold italic text-slate-500">Sem profissional vinculado</span>
                            </td>
                            <td>
                                <span class="text-xs italic opacity-60">Faturamento Direto</span>
                            </td>
                            <td>
                                <span class="text-xs opacity-60">-</span>
                            </td>
                            <td>
                                <span class="text-xs font-mono">R$ {{ formatCurrency(stats.unassigned_revenue) }}</span>
                            </td>
                            <td>
                                <span class="text-xs opacity-60">0.00%</span>
                            </td>
                            <td class="text-right">
                                <strong class="text-slate-500 font-mono font-bold">R$ 0,00</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
