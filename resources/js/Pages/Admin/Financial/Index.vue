<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_revenue: 0,
            total_commissions: 0,
            net_profit: 0,
            appointments_count: 0,
            commission_rate: 0,
            my_commissions: 0,
            members_data: [],
            unassigned_revenue: 0,
        })
    },
    history: {
        type: Array,
        default: () => []
    },
    teamMember: {
        type: Object,
        default: null
    },
});

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    const userPerms = page.props.auth?.permissions || [];
    return userPerms.includes(permission);
};

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const formatPercent = (value) => {
    return Number(value || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const getInitials = (name) => {
    if (!name) return 'A';
    return name.substring(0, 2).toUpperCase();
};

const exportMyGanhosToCSV = () => {
    if (!hasPermission('reports.export')) return;
    const rows = [
        ["Data/Horario", "Cliente", "Servico", "Valor do Servico", "Comissao", "Seu Ganho"]
    ];

    props.history.forEach(row => {
        rows.push([
            `${row.date} ${row.time}`,
            row.client_name,
            row.service_name,
            `R$ ${formatCurrency(row.price)}`,
            `${formatPercent(row.rate)}%`,
            `R$ ${formatCurrency(row.earned)}`
        ]);
    });

    let csvContent = "data:text/csv;charset=utf-8,\uFEFF"
        + rows.map(e => e.map(val => `"${String(val).replace(/"/g, '""')}"`).join(",")).join("\n");

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "historico_meus_ganhos.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const exportPayoutsToCSV = () => {
    if (!hasPermission('reports.export')) return;
    const rows = [
        ["Nome do Profissional", "Cargo", "Qtd Atendimentos", "Faturamento Gerado", "Comissao Padrao", "Total Repasse"]
    ];

    props.stats.members_data.forEach(data => {
        rows.push([
            data.member?.name || '',
            data.member?.job_title || 'Profissional',
            data.count,
            `R$ ${formatCurrency(data.revenue)}`,
            `${formatPercent(data.rate)}%`,
            `R$ ${formatCurrency(data.commission)}`
        ]);
    });

    if (props.stats.unassigned_revenue > 0) {
        rows.push([
            "Sem profissional vinculado",
            "Faturamento Direto",
            "-",
            `R$ ${formatCurrency(props.stats.unassigned_revenue)}`,
            "0.00%",
            "R$ 0,00"
        ]);
    }

    let csvContent = "data:text/csv;charset=utf-8,\uFEFF"
        + rows.map(e => e.map(val => `"${String(val).replace(/"/g, '""')}"`).join(",")).join("\n");

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "relatorio_financeiro_estabelecimento.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <AdminLayout>
        <Head title="Financeiro & Faturamento - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Financeiro & Faturamento</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Painel de controle financeiro, comissões e repasses aos profissionais</p>
        </template>

        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight" style="color: var(--text-heading);">Painel de Controle Financeiro</h2>
                    <p class="text-xs sm:text-sm opacity-70">Monitore faturamento, calcule comissões de profissionais e acompanhe os repasses.</p>
                </div>
            </div>

            <template v-if="teamMember">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="card p-4 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Faturamento Gerado</span>
                            <h3 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">R$ {{ formatCurrency(stats.total_revenue) }}</h3>
                            <span class="text-[11px] opacity-70">Serviços prestados por você</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-dollar-sign"></i>
                        </div>
                    </div>

                    <div class="card p-4 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Taxa de Comissão Padrão</span>
                            <h3 class="text-2xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ formatPercent(stats.commission_rate) }}%</h3>
                            <span class="text-[11px] opacity-70">Definido no seu perfil</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-percent"></i>
                        </div>
                    </div>

                    <div class="card p-4 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Sua Comissão Acumulada</span>
                            <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">R$ {{ formatCurrency(stats.my_commissions) }}</h3>
                            <span class="text-[11px] text-emerald-500 font-semibold">Líquido a receber</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>

                    <div class="card p-4 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total de Atendimentos</span>
                            <h3 class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ stats.appointments_count }}</h3>
                            <span class="text-[11px] opacity-70">Compromissos realizados</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-0 overflow-hidden mt-6">
                    <div class="p-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
                        <h4 class="font-extrabold text-xs sm:text-sm" style="color: var(--text-heading);">Detalhamento de Ganhos - Histórico de Atendimentos</h4>
                        <button v-if="hasPermission('reports.export')" type="button" @click="exportMyGanhosToCSV" class="btn btn-outline py-1.5 px-3 text-xs flex items-center gap-1.5 rounded-xl hover:text-emerald-500 hover:border-emerald-500">
                            <i class="fa-solid fa-file-csv text-emerald-500"></i>
                            <span>Exportar CSV</span>
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th>Data/Horário</th>
                                    <th>Cliente</th>
                                    <th>Serviço</th>
                                    <th>Valor do Serviço</th>
                                    <th>% Comissão</th>
                                    <th class="text-right">Seu Ganho</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="history.length > 0">
                                    <tr v-for="(row, idx) in history" :key="idx">
                                        <td>
                                            <span class="font-bold text-xs opacity-80">{{ row.date }} às {{ row.time }}</span>
                                        </td>
                                        <td>
                                            <span class="font-semibold">{{ row.client_name }}</span>
                                        </td>
                                        <td>
                                            <span class="font-medium text-xs">{{ row.service_name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs">R$ {{ formatCurrency(row.price) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-mono">{{ formatPercent(row.rate) }}%</span>
                                        </td>
                                        <td class="text-right">
                                            <strong class="text-emerald-600 dark:text-emerald-400 font-bold">R$ {{ formatCurrency(row.earned) }}</strong>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="6" class="text-center py-10 opacity-60 text-xs">Nenhum atendimento confirmado ou concluído registrado no seu histórico.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="card p-4 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Faturamento Bruto</span>
                            <h3 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">R$ {{ formatCurrency(stats.total_revenue) }}</h3>
                            <span class="text-[11px] opacity-70">Total faturado no sistema</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-dollar-sign"></i>
                        </div>
                    </div>

                    <div class="card p-4 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Repasses Totais</span>
                            <h3 class="text-2xl font-extrabold text-rose-600 dark:text-rose-400">R$ {{ formatCurrency(stats.total_commissions) }}</h3>
                            <span class="text-[11px] text-rose-500 dark:text-rose-400 font-semibold">Comissão de profissionais</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>

                    <div class="card p-4 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Lucro Líquido</span>
                            <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">R$ {{ formatCurrency(stats.net_profit) }}</h3>
                            <span class="text-[11px] text-emerald-500 font-semibold">Faturamento retido</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>

                    <div class="card p-4 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Atendimentos</span>
                            <h3 class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ stats.appointments_count }}</h3>
                            <span class="text-[11px] opacity-70">Total acumulado</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>
                    </div>
                </div>

                <div class="card p-0 overflow-hidden mt-6">
                    <div class="p-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
                        <h4 class="font-extrabold text-xs sm:text-sm" style="color: var(--text-heading);">Tabela de Repasse por Membro</h4>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button v-if="hasPermission('reports.export')" type="button" @click="exportPayoutsToCSV" class="btn btn-outline py-1.5 px-3 text-xs flex items-center gap-1.5 rounded-xl hover:text-emerald-500 hover:border-emerald-500">
                                <i class="fa-solid fa-file-csv text-emerald-500"></i>
                                <span>Exportar CSV</span>
                            </button>
                            <button type="button" class="btn btn-outline py-1.5 px-3 text-xs flex items-center gap-1.5 rounded-xl hover:border-emerald-500 hover:text-emerald-600">
                                <i class="fa-solid fa-check-double text-indigo-500"></i>
                                <span>Pagar Todos</span>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th>Nome do Profissional</th>
                                    <th>Cargo</th>
                                    <th>Qtd. Atendimentos</th>
                                    <th>Faturamento Gerado</th>
                                    <th>Comissão Média/Padrão</th>
                                    <th class="text-right">Total Repasse</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="stats.members_data && stats.members_data.length > 0">
                                    <tr v-for="(data, idx) in stats.members_data" :key="idx">
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 border flex items-center justify-center shrink-0">
                                                    <img v-if="data.member?.avatar_url" :src="data.member.avatar_url" alt="" class="w-full h-full object-cover">
                                                    <div v-else class="w-full h-full bg-indigo-600 flex items-center justify-center text-white text-[10px] font-bold">
                                                        {{ getInitials(data.member?.name) }}
                                                    </div>
                                                </div>
                                                <span class="font-bold" style="color: var(--text-heading);">{{ data.member?.name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ data.member?.job_title ?? 'Profissional' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-medium">{{ data.count }} agendamentos</span>
                                        </td>
                                        <td>
                                            <span class="text-xs">R$ {{ formatCurrency(data.revenue) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-mono">{{ formatPercent(data.rate) }}%</span>
                                        </td>
                                        <td class="text-right">
                                            <strong class="text-rose-600 dark:text-rose-400 font-bold">R$ {{ formatCurrency(data.commission) }}</strong>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="6" class="text-center py-10 opacity-60 text-xs">Nenhum profissional cadastrado ou ativo.</td>
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
                                        <span class="text-xs">R$ {{ formatCurrency(stats.unassigned_revenue) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-xs opacity-60">0.00%</span>
                                    </td>
                                    <td class="text-right">
                                        <strong class="text-slate-500 font-bold">R$ 0,00</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card p-0 overflow-hidden mt-6">
                    <div class="p-4 border-b" style="border-color: var(--border);">
                        <h4 class="font-extrabold text-xs sm:text-sm" style="color: var(--text-heading);">Histórico Recente de Atendimentos</h4>
                    </div>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th>Data/Horário</th>
                                    <th>Cliente</th>
                                    <th>Serviço</th>
                                    <th>Profissional</th>
                                    <th>Valor do Serviço</th>
                                    <th>Comissão</th>
                                    <th class="text-right">Repasse Profissional</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="history.length > 0">
                                    <tr v-for="(row, idx) in history" :key="idx">
                                        <td>
                                            <span class="text-xs opacity-80">{{ row.date }} às {{ row.time }}</span>
                                        </td>
                                        <td>
                                            <span class="font-semibold">{{ row.client_name }}</span>
                                        </td>
                                        <td>
                                            <span class="font-medium text-xs">{{ row.service_name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ row.professional_name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs">R$ {{ formatCurrency(row.price) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-mono">{{ formatPercent(row.rate) }}%</span>
                                        </td>
                                        <td class="text-right">
                                            <strong class="text-rose-600 dark:text-rose-400 font-bold">R$ {{ formatCurrency(row.earned) }}</strong>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="7" class="text-center py-10 opacity-60 text-xs">Nenhum atendimento confirmado ou concluído recente.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </AdminLayout>
</template>
