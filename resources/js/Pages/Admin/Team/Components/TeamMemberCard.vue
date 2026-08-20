<script setup>
defineProps({
    teamMembers: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Array,
        default: () => [],
    },
    canManage: {
        type: Boolean,
        default: false,
    },
    canDelete: {
        type: Boolean,
        default: false,
    },
    appDomain: {
        type: String,
        default: 'agendae.app',
    },
});

defineEmits(['open-create', 'open-edit', 'open-delete', 'open-reset', 'toggle-status']);

const getInitials = (name) => (name || 'A').substring(0, 2).toUpperCase();
const formatCurrency = (val) => Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <div class="space-y-4">
        <div v-if="teamMembers.length === 0" class="card text-center py-16 px-4 text-slate-500">
            <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="fa-solid fa-users-slash"></i>
            </div>
            <h4 class="text-base font-bold" style="color: var(--text-heading);">Nenhum membro cadastrado na equipe</h4>
            <p class="text-xs opacity-70 mt-1 max-w-sm mx-auto">
                Adicione profissionais para permitir que clientes agendem diretamente com cada um deles.
            </p>
            <div v-if="canManage" class="mt-4">
                <button type="button" @click="$emit('open-create')" class="btn btn-primary text-xs py-2 px-4">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Adicionar Primeiro Profissional</span>
                </button>
            </div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div
                v-for="member in teamMembers"
                :key="member.id"
                class="card p-5 space-y-4 flex flex-col justify-between relative group hover:border-indigo-500/40 transition-all shadow-sm"
            >
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl overflow-hidden bg-gradient-to-tr from-brand-600 to-indigo-600 text-white flex items-center justify-center font-bold text-base shrink-0 shadow-md">
                                <img v-if="member.avatar_url" :src="member.avatar_url" :alt="member.name" class="w-full h-full object-cover" />
                                <span v-else>{{ getInitials(member.name) }}</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-sm truncate" style="color: var(--text-heading);">{{ member.name }}</h4>
                                <span class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold block truncate">
                                    {{ member.job_title || member.role_title || 'Profissional' }}
                                </span>
                            </div>
                        </div>

                        <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0', member.is_active ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-slate-200 dark:bg-slate-800 text-slate-500']">
                            {{ member.is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs text-slate-500 dark:text-slate-400 pt-1">
                        <div class="flex items-center gap-2 truncate">
                            <i class="fa-regular fa-envelope text-[11px] w-4 text-slate-400"></i>
                            <span class="truncate">{{ member.email }}</span>
                        </div>
                        <div v-if="member.phone" class="flex items-center gap-2 truncate">
                            <i class="fa-solid fa-phone text-[11px] w-4 text-slate-400"></i>
                            <span>{{ member.phone }}</span>
                        </div>
                        <div v-if="member.commission_rate > 0" class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-bold">
                            <i class="fa-solid fa-percent text-[11px] w-4"></i>
                            <span>Comissão Padrão: {{ member.commission_rate }}%</span>
                        </div>
                    </div>

                    <!-- Subdomain / Public Link -->
                    <div v-if="member.subdomain || member.custom_domain" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                        <div class="truncate mr-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase block">Link Pessoal</span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400 truncate block">
                                {{ member.custom_domain || `${member.subdomain}.${appDomain}` }}
                            </span>
                        </div>
                        <a
                            :href="member.custom_domain ? `https://${member.custom_domain}` : `http://${member.subdomain}.${appDomain}`"
                            target="_blank"
                            class="p-1.5 rounded-lg opacity-60 hover:opacity-100 hover:text-indigo-600 text-slate-500 transition-colors shrink-0"
                            title="Abrir página pública"
                        >
                            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                        </a>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="pt-3 border-t flex items-center justify-between gap-1 text-xs" style="border-color: var(--border);">
                    <button
                        type="button"
                        @click="$emit('toggle-status', member)"
                        :class="['px-2.5 py-1 rounded-lg text-xs font-semibold transition-all', member.is_active ? 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/20' : 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/20']"
                    >
                        {{ member.is_active ? 'Desativar' : 'Ativar' }}
                    </button>

                    <div class="flex items-center gap-1">
                        <button
                            v-if="canManage"
                            type="button"
                            @click="$emit('open-reset', member)"
                            class="w-7 h-7 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all text-amber-500"
                            title="Resetar senha"
                        >
                            <i class="fa-solid fa-key text-[11px]"></i>
                        </button>
                        <button
                            v-if="canManage"
                            type="button"
                            @click="$emit('open-edit', member)"
                            class="w-7 h-7 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all text-indigo-500"
                            title="Editar profissional"
                        >
                            <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                        </button>
                        <button
                            v-if="canDelete"
                            type="button"
                            @click="$emit('open-delete', member)"
                            class="w-7 h-7 rounded-lg flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-rose-100 dark:hover:bg-rose-950/30 text-rose-500 transition-all"
                            title="Excluir profissional"
                        >
                            <i class="fa-solid fa-trash text-[11px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
