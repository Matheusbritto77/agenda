<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    form: {
        type: Object,
        required: true,
    },
    roles: {
        type: Object,
        default: () => ({}),
    },
    services: {
        type: Array,
        default: () => [],
    },
    avatarPreview: {
        type: String,
        default: '',
    },
    appDomain: {
        type: String,
        default: 'agendae.app',
    },
});

const emit = defineEmits(['close', 'submit', 'file-change', 'url-preview']);

const activeTab = ref('basic');
const fileInputRef = ref(null);

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};

const onFileSelected = (e) => {
    const file = e.target.files?.[0];
    emit('file-change', file);
};

const toggleService = (svcId) => {
    const idStr = String(svcId);
    const index = props.form.services.findIndex(id => String(id) === idStr);
    if (index > -1) {
        props.form.services.splice(index, 1);
    } else {
        props.form.services.push(svcId);
    }
};

const isServiceSelected = (svcId) => {
    return props.form.services.some(id => String(id) === String(svcId));
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div class="liquid-glass-card w-full max-w-2xl p-6 sm:p-7 space-y-5 relative max-h-[90vh] flex flex-col" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b shrink-0" style="border-color: var(--border);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-indigo-600/30">
                            <i class="fa-solid fa-user-gear"></i>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">
                                {{ isEditing ? 'Editar Profissional' : 'Novo Membro da Equipe' }}
                            </h3>
                            <p class="text-xs opacity-60">Configuração de dados, serviços atribuídos e comissões</p>
                        </div>
                    </div>
                    <button type="button" @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Sub-Tabs -->
                <div class="flex items-center gap-2 border-b pb-3 shrink-0" style="border-color: var(--border);">
                    <button
                        type="button"
                        @click="activeTab = 'basic'"
                        :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition-all', activeTab === 'basic' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800']"
                    >
                        Dados Gerais
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'services'"
                        :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition-all', activeTab === 'services' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800']"
                    >
                        Serviços Vinculados ({{ form.services?.length || 0 }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'commission'"
                        :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition-all', activeTab === 'commission' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800']"
                    >
                        Comissões
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'domain'"
                        :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition-all', activeTab === 'domain' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800']"
                    >
                        Link / Subdomínio
                    </button>
                </div>

                <form @submit.prevent="$emit('submit')" class="space-y-4 overflow-y-auto flex-1 pr-1 custom-scrollbar">
                    <!-- Tab: Basic Info -->
                    <div v-show="activeTab === 'basic'" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="form-group mb-0 sm:col-span-2">
                                <label class="form-label text-xs">Nome Completo *</label>
                                <input type="text" v-model="form.name" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Ex: Lucas Ferreira" required />
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label text-xs">Cargo / Especialidade</label>
                                <input type="text" v-model="form.job_title" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Ex: Barbeiro Master, Manicure" />
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label text-xs">Role de Acesso *</label>
                                <select v-model="form.role_id" class="form-control text-xs sm:text-sm rounded-xl" required>
                                    <option
                                        v-for="(role, key) in roles"
                                        :key="key"
                                        :value="key"
                                    >
                                        {{ role.name }}
                                    </option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Define o nível de acesso desse membro no painel e sincroniza com o usuário vinculado.
                                </p>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label text-xs">Telefone / WhatsApp</label>
                                <input type="text" v-model="form.phone" class="form-control text-xs sm:text-sm rounded-xl" placeholder="(11) 99999-8888" />
                            </div>
                            <div class="form-group mb-0 sm:col-span-2">
                                <label class="form-label text-xs">E-mail de Login *</label>
                                <input type="email" v-model="form.email" class="form-control text-xs sm:text-sm rounded-xl" placeholder="lucas@exemplo.com" required />
                            </div>
                            <div v-if="!isEditing" class="form-group mb-0 sm:col-span-2">
                                <label class="form-label text-xs">Senha Inicial *</label>
                                <input type="password" v-model="form.password" class="form-control text-xs sm:text-sm rounded-xl" placeholder="••••••••" required />
                            </div>
                            <div class="form-group mb-0 sm:col-span-2">
                                <label class="form-label text-xs">Biografia / Descrição Curta</label>
                                <textarea v-model="form.bio" rows="2" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Especialista em cortes modernos com mais de 5 anos de experiência..."></textarea>
                            </div>
                        </div>

                        <!-- Avatar -->
                        <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-white dark:bg-slate-800 border flex items-center justify-center shrink-0">
                                <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                                <i v-else class="fa-solid fa-user text-slate-400"></i>
                            </div>
                            <div class="space-y-1">
                                <input type="file" ref="fileInputRef" @change="onFileSelected" accept="image/*" class="hidden" />
                                <button type="button" @click="fileInputRef?.click()" class="px-3 py-1.5 rounded-lg text-xs font-bold border bg-white dark:bg-slate-800 hover:bg-slate-50 transition-all cursor-pointer">
                                    <i class="fa-solid fa-upload mr-1 text-[10px]"></i>
                                    Foto de Perfil
                                </button>
                                <p class="text-[10px] text-slate-400">PNG, JPG ou WEBP até 10MB</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Services Assigned -->
                    <div v-show="activeTab === 'services'" class="space-y-3">
                        <p class="text-xs text-slate-500">Marque quais procedimentos este profissional pode executar:</p>
                        <div v-if="services.length === 0" class="text-center py-8 text-xs text-slate-400">
                            Nenhum serviço cadastrado ainda.
                        </div>
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="svc in services"
                                :key="svc.id"
                                @click="toggleService(svc.id)"
                                :class="[
                                    'p-3 rounded-xl border flex items-center justify-between cursor-pointer transition-all',
                                    isServiceSelected(svc.id) ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                                ]"
                            >
                                <div>
                                    <p class="text-xs font-bold">{{ svc.name }}</p>
                                    <span class="text-[11px] opacity-70">R$ {{ Number(svc.price || 0).toFixed(2) }} ({{ svc.duration_minutes }} min)</span>
                                </div>
                                <i :class="['fa-solid', isServiceSelected(svc.id) ? 'fa-circle-check text-indigo-600 dark:text-indigo-400 text-sm' : 'fa-circle text-slate-300 text-sm']"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Commission -->
                    <div v-show="activeTab === 'commission'" class="space-y-4">
                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Comissão Padrão Geral (%)</label>
                            <input
                                type="number"
                                step="0.1"
                                min="0"
                                max="100"
                                v-model="form.commission_rate"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="Ex: 50.0"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">Porcentagem padrão que o profissional recebe sobre o valor de cada serviço executado.</p>
                        </div>
                    </div>

                    <!-- Tab: Subdomain / Domain -->
                    <div v-show="activeTab === 'domain'" class="space-y-4">
                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Subdomínio Pessoal (slug)</label>
                            <div class="flex items-center gap-1">
                                <input
                                    type="text"
                                    v-model="form.subdomain"
                                    class="form-control text-xs sm:text-sm rounded-xl"
                                    placeholder="lucas"
                                />
                                <span class="text-xs font-bold text-slate-400">.{{ appDomain }}</span>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Domínio Personalizado Próprio (Opcional)</label>
                            <input
                                type="text"
                                v-model="form.custom_domain"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="ex: lucasbarber.com.br"
                            />
                        </div>
                    </div>

                    <div class="pt-4 border-t flex items-center justify-end gap-2 shrink-0" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="btn btn-outline py-2 px-4 text-xs font-bold rounded-xl"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary py-2 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30"
                            :disabled="form.processing"
                        >
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>{{ form.processing ? 'Salvando...' : 'Salvar Dados' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
