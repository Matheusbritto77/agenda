<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    activeCompany: {
        type: Object,
        default: null,
    },
});

const avatarInput = ref(null);
const avatarPreview = ref(props.client.avatar_url || null);
const showPasswordFields = ref(false);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const portalCustomization = computed(() => props.activeCompany?.portal_customization || props.activeCompany || {});
const portalPrimaryColor = computed(() => portalCustomization.value.primary_color || '#6366f1');
const portalSecondaryColor = computed(() => portalCustomization.value.secondary_color || '#06b6d4');

const clientInitials = computed(() => {
    const name = props.client.name || 'Cliente';
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase() || 'C';
});

const form = useForm({
    name: props.client.name || '',
    email: props.client.email || '',
    phone: props.client.phone || '',
    avatar: null,
    remove_avatar: false,
    current_password: '',
    password: '',
    password_confirmation: '',
});

const handleAvatarChange = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    form.avatar = file;
    form.remove_avatar = false;

    const reader = new FileReader();
    reader.onload = (e) => {
        avatarPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const triggerAvatarUpload = () => {
    if (avatarInput.value) {
        avatarInput.value.click();
    }
};

const removeAvatar = () => {
    form.avatar = null;
    form.remove_avatar = true;
    avatarPreview.value = null;
    if (avatarInput.value) {
        avatarInput.value.value = '';
    }
};

const submitProfile = () => {
    form.post(route('client.profile.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.current_password = '';
            form.password = '';
            form.password_confirmation = '';
            showPasswordFields.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header Introduction Card -->
        <section class="rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white/90 dark:bg-slate-900/90 p-6 sm:p-8 shadow-xl shadow-indigo-500/5 backdrop-blur-xl transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div
                        class="w-14 h-14 rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg shrink-0"
                        :style="{ background: `linear-gradient(135deg, ${portalPrimaryColor} 0%, ${portalSecondaryColor} 100%)` }"
                    >
                        <i class="fa-solid fa-id-card-clip"></i>
                    </div>
                    <div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 border border-indigo-500/20">
                            Minha Conta
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1">
                            Meu Perfil & Preferências
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Atualize seus dados pessoais, foto de perfil, telefone e senha de acesso.
                        </p>
                    </div>
                </div>

                <div v-if="client.created_at" class="px-3.5 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700/70 self-start sm:self-auto text-left sm:text-right">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Cliente desde</span>
                    <strong class="text-xs font-black text-slate-700 dark:text-slate-200">{{ client.created_at }}</strong>
                </div>
            </div>
        </section>

        <!-- Main Profile Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Avatar & Account Insights -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Avatar Card -->
                <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 p-6 shadow-sm hover:shadow-md transition-all text-center space-y-5">
                    <div class="relative w-28 h-28 sm:w-32 sm:h-32 mx-auto">
                        <div
                            class="w-full h-full rounded-3xl overflow-hidden ring-4 ring-slate-100 dark:ring-slate-800 shadow-2xl flex items-center justify-center bg-gradient-to-tr from-indigo-600 to-cyan-500 text-white font-black text-3xl sm:text-4xl transition-transform hover:scale-102"
                        >
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                :alt="form.name || 'Foto do Cliente'"
                                class="w-full h-full object-cover"
                            />
                            <span v-else>{{ clientInitials }}</span>
                        </div>

                        <!-- Upload floating badge -->
                        <button
                            type="button"
                            @click="triggerAvatarUpload"
                            class="absolute -bottom-2 -right-2 w-10 h-10 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg flex items-center justify-center text-sm transition-transform active:scale-90 cursor-pointer border-2 border-white dark:border-slate-900"
                            title="Alterar foto"
                        >
                            <i class="fa-solid fa-camera"></i>
                        </button>
                    </div>

                    <input
                        ref="avatarInput"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/jpg"
                        class="hidden"
                        @change="handleAvatarChange"
                    />

                    <div class="space-y-1.5">
                        <h3 class="text-base font-black text-slate-900 dark:text-white truncate">
                            {{ form.name || 'Cliente' }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                            {{ form.email || 'cliente@exemplo.com' }}
                        </p>
                    </div>

                    <div class="flex items-center justify-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="triggerAvatarUpload"
                            class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-500 bg-slate-50 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-indigo-600 transition-all cursor-pointer flex items-center gap-1.5"
                        >
                            <i class="fa-solid fa-arrow-up-from-bracket text-xs"></i>
                            <span>{{ avatarPreview ? 'Trocar Foto' : 'Enviar Foto' }}</span>
                        </button>

                        <button
                            v-if="avatarPreview"
                            type="button"
                            @click="removeAvatar"
                            class="px-3 py-1.5 rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/50 dark:bg-rose-950/20 hover:bg-rose-100 dark:hover:bg-rose-900/40 text-xs font-bold text-rose-600 dark:text-rose-400 transition-all cursor-pointer flex items-center gap-1"
                            title="Remover foto atual"
                        >
                            <i class="fa-solid fa-trash text-xs"></i>
                            <span>Remover</span>
                        </button>
                    </div>

                    <div v-if="form.errors.avatar" class="text-xs font-bold text-rose-500">
                        {{ form.errors.avatar }}
                    </div>
                </div>

                <!-- Account Stats Summary -->
                <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-4">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-indigo-500"></i>
                        <span>Resumo de Atividades</span>
                    </h4>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                <i class="fa-regular fa-calendar-check text-indigo-500"></i>
                                Agendamentos Realizados
                            </span>
                            <strong class="font-black text-slate-900 dark:text-white">{{ summary.appointments || 0 }}</strong>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                <i class="fa-solid fa-check-double text-emerald-500"></i>
                                Atendimentos Concluídos
                            </span>
                            <strong class="font-black text-emerald-600 dark:text-emerald-400">{{ summary.completed || 0 }}</strong>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                <i class="fa-solid fa-store text-purple-500"></i>
                                Empresas Frequentadas
                            </span>
                            <strong class="font-black text-purple-600 dark:text-purple-400">{{ summary.companies || 0 }}</strong>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                <i class="fa-solid fa-star text-amber-400"></i>
                                Avaliações Enviadas
                            </span>
                            <strong class="font-black text-amber-500">{{ summary.reviews || 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Profile Edit Form & Security -->
            <div class="lg:col-span-8 space-y-6">
                <form @submit.prevent="submitProfile" class="space-y-6">
                    <!-- Personal Info Card -->
                    <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-sm space-y-5">
                        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 flex items-center justify-center text-lg shrink-0">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Dados Pessoais</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Suas informações de identificação nos estabelecimentos parceiros.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Name -->
                            <div class="space-y-1.5 sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                    Nome Completo <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-regular fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                    <input
                                        type="text"
                                        v-model="form.name"
                                        required
                                        placeholder="Seu nome completo"
                                        class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 pl-10 pr-4 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all shadow-xs"
                                    />
                                </div>
                                <span v-if="form.errors.name" class="text-xs font-bold text-rose-500 block">{{ form.errors.name }}</span>
                            </div>

                            <!-- Email -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                    E-mail de Acesso <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                    <input
                                        type="email"
                                        v-model="form.email"
                                        required
                                        placeholder="seuemail@exemplo.com"
                                        class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 pl-10 pr-4 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all shadow-xs"
                                    />
                                </div>
                                <span v-if="form.errors.email" class="text-xs font-bold text-rose-500 block">{{ form.errors.email }}</span>
                            </div>

                            <!-- Phone / WhatsApp -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                    WhatsApp / Telefone
                                </label>
                                <div class="relative">
                                    <i class="fa-brands fa-whatsapp absolute left-3.5 top-1/2 -translate-y-1/2 text-emerald-500 text-sm"></i>
                                    <input
                                        type="tel"
                                        v-model="form.phone"
                                        placeholder="(11) 98888-7777"
                                        class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 pl-10 pr-4 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all shadow-xs"
                                    />
                                </div>
                                <span v-if="form.errors.phone" class="text-xs font-bold text-rose-500 block">{{ form.errors.phone }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Security & Password Card -->
                    <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg shrink-0">
                                    <i class="fa-solid fa-lock"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Segurança & Senha</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Altere sua senha de acesso à área exclusiva.</p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="showPasswordFields = !showPasswordFields"
                                class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-indigo-500 text-xs font-bold text-slate-700 dark:text-slate-300 transition-all cursor-pointer flex items-center gap-1.5"
                            >
                                <i :class="showPasswordFields ? 'fa-solid fa-chevron-up' : 'fa-solid fa-key'"></i>
                                <span>{{ showPasswordFields ? 'Ocultar Senha' : 'Trocar Senha' }}</span>
                            </button>
                        </div>

                        <!-- Toggleable Password Inputs -->
                        <div v-if="showPasswordFields" class="space-y-4 pt-2">
                            <!-- Current Password -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                    Senha Atual
                                </label>
                                <div class="relative">
                                    <input
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        v-model="form.current_password"
                                        placeholder="Digite sua senha atual"
                                        class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all pr-10 shadow-xs"
                                    />
                                    <button
                                        type="button"
                                        @click="showCurrentPassword = !showCurrentPassword"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                    >
                                        <i :class="showCurrentPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
                                    </button>
                                </div>
                                <span v-if="form.errors.current_password" class="text-xs font-bold text-rose-500 block">{{ form.errors.current_password }}</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- New Password -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                        Nova Senha
                                    </label>
                                    <div class="relative">
                                        <input
                                            :type="showNewPassword ? 'text' : 'password'"
                                            v-model="form.password"
                                            placeholder="Mínimo 8 caracteres"
                                            class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all pr-10 shadow-xs"
                                        />
                                        <button
                                            type="button"
                                            @click="showNewPassword = !showNewPassword"
                                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                        >
                                            <i :class="showNewPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
                                        </button>
                                    </div>
                                    <span v-if="form.errors.password" class="text-xs font-bold text-rose-500 block">{{ form.errors.password }}</span>
                                </div>

                                <!-- Confirm New Password -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                                        Confirmar Nova Senha
                                    </label>
                                    <div class="relative">
                                        <input
                                            :type="showConfirmPassword ? 'text' : 'password'"
                                            v-model="form.password_confirmation"
                                            placeholder="Repita a nova senha"
                                            class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all pr-10 shadow-xs"
                                        />
                                        <button
                                            type="button"
                                            @click="showConfirmPassword = !showConfirmPassword"
                                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                        >
                                            <i :class="showConfirmPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-xs text-slate-400 flex items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-emerald-500"></i>
                            <span>Sua senha está segura. Clique em "Trocar Senha" apenas se desejar alterá-la.</span>
                        </div>
                    </div>

                    <!-- Submit Actions Button -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl text-xs sm:text-sm font-black text-white shadow-xl shadow-indigo-600/30 transition-all hover:scale-102 active:scale-98 cursor-pointer disabled:opacity-50"
                            :style="{ background: `linear-gradient(135deg, ${portalPrimaryColor} 0%, ${portalSecondaryColor} 100%)` }"
                        >
                            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-floppy-disk"></i>
                            <span>{{ form.processing ? 'Salvando Alterações...' : 'Salvar Dados do Perfil' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
