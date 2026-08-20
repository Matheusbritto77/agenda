<script setup>
defineProps({
    appointments: {
        type: Array,
        default: () => [],
    },
    pagination: {
        type: Object,
        default: () => ({}),
    },
});

defineEmits(['open-detail']);

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const statusClass = (status) => {
    switch (status) {
        case 'confirmed':
            return 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30';
        case 'pending':
            return 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30';
        case 'completed':
            return 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30';
        case 'cancelled':
            return 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30';
        default:
            return 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300';
    }
};

const statusLabel = (status) => {
    switch (status) {
        case 'confirmed':
            return 'Confirmado';
        case 'pending':
            return 'Pendente';
        case 'completed':
            return 'Concluído';
        case 'cancelled':
            return 'Cancelado';
        default:
            return status;
    }
};
</script>

<template>
    <div class="card p-0 overflow-hidden shadow-sm">
        <div v-if="appointments.length === 0" class="text-center py-16 px-4">
            <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="fa-regular fa-calendar-xmark"></i>
            </div>
            <h4 class="text-base font-bold" style="color: var(--text-heading);">Nenhum agendamento encontrado</h4>
            <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">Tente ajustar os filtros acima ou aguarde novas reservas de clientes.</p>
        </div>

        <div v-else class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Serviço</th>
                        <th>Data & Horário</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th class="text-right">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="app in appointments"
                        :key="app.id"
                        class="hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition-colors"
                        @click="$emit('open-detail', app)"
                    >
                        <td>
                            <div class="font-bold text-sm" style="color: var(--text-heading);">{{ app.customer_name }}</div>
                            <div class="text-xs text-slate-400">{{ app.customer_phone }}</div>
                        </td>
                        <td>
                            <div class="font-medium text-xs sm:text-sm">{{ app.service?.name || 'Serviço' }}</div>
                            <div class="text-[11px] text-slate-400">{{ app.service?.duration_minutes || 30 }} min</div>
                        </td>
                        <td>
                            <div class="font-medium text-xs sm:text-sm flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar text-[11px] text-indigo-500"></i>
                                {{ formatDate(app.appointment_date) }}
                            </div>
                            <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                <i class="fa-regular fa-clock text-[10px]"></i>
                                {{ (app.start_time || '00:00').substring(0, 5) }} às {{ (app.end_time || '00:00').substring(0, 5) }}
                            </div>
                        </td>
                        <td>
                            <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100">
                                R$ {{ formatCurrency(app.service?.price) }}
                            </span>
                        </td>
                        <td>
                            <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold', statusClass(app.status)]">
                                {{ statusLabel(app.status) }}
                            </span>
                        </td>
                        <td class="text-right">
                            <button
                                type="button"
                                @click.stop="$emit('open-detail', app)"
                                class="w-8 h-8 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all"
                                title="Ver detalhes"
                            >
                                <i class="fa-solid fa-eye text-xs"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
