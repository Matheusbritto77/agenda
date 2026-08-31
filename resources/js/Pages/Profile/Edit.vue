<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PhoneInputWithCountry from '@/Components/PhoneInputWithCountry.vue';

const props = defineProps({
    user: { type: Object, required: true },
    status: { type: String, default: null },
    mustVerifyEmail: { type: Boolean, default: false },
    teamMember: { type: Object, default: null },
    accountContext: { type: Object, default: () => ({}) },
    permissionModules: { type: Array, default: () => [] },
});

const activeTab = ref('profile');
const showDeleteModal = ref(false);
const verificationSent = ref(false);
const avatarPreview = ref(props.accountContext.avatar_url || props.user.avatar_url || null);
const avatarClientError = ref('');
const maxAvatarSizeBytes = 10 * 1024 * 1024;

const profileForm = useForm({
    name: props.user.name || '',
    email: props.user.email || '',
    phone: props.user.phone || '',
    country_code: props.user.country_code || 'BR',
    avatar: null,
    avatar_url: props.accountContext.avatar_url || props.user.avatar_url || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const deleteForm = useForm({ password: '' });

const tabs = [
    { id: 'profile', label: 'Perfil', icon: 'fa-regular fa-user' },
    { id: 'security', label: 'Segurança', icon: 'fa-solid fa-key' },
    { id: 'account', label: 'Conta', icon: 'fa-solid fa-id-badge' },
    { id: 'permissions', label: 'Permissões', icon: 'fa-solid fa-user-shield' },
];

const showProfileSuccess = computed(() => props.status === 'profile-updated');
const showPasswordSuccess = computed(() => props.status === 'password-updated');

const userInitials = computed(() => {
    const name = profileForm.name || props.user.name || 'A';
    return name.substring(0, 2).toUpperCase();
});

const grantedPermissionsCount = computed(() => {
    return props.permissionModules.reduce((total, module) => total + Number(module.granted_count || 0), 0);
});

const onAvatarChange = (event) => {
    const file = event.target.files?.[0] || null;
    avatarClientError.value = '';
    profileForm.avatar = file;

    if (file && file.size > maxAvatarSizeBytes) {
        profileForm.avatar = null;
        avatarClientError.value = 'Escolha uma imagem de até 10 MB.';
        event.target.value = '';
        return;
    }

    if (file) {
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const submitProfile = () => {
    profileForm.post(route('profile.update.upload'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            verificationSent.value = false;
            profileForm.avatar = null;
        },
        errorBag: 'updateProfileInformation',
    });
};

const resendVerification = () => {
    profileForm.post(route('verification.send'), {
        preserveScroll: true,
        onSuccess: () => {
            verificationSent.value = true;
        },
    });
};

const submitPassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        errorBag: 'updatePassword',
    });
};

const openDeleteModal = () => {
    showDeleteModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteForm.reset();
    document.body.classList.remove('overflow-hidden');
};

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) closeDeleteModal();
};

const submitDelete = () => {
    deleteForm.delete(route('profile.destroy'), {
        onSuccess: () => {
            router.visit(route('login'));
        },
        errorBag: 'userDeletion',
    });
};

onMounted(() => {
    document.body.classList.remove('overflow-hidden');
});

onUnmounted(() => {
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <AdminLayout>
        <Head title="Minha Conta - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Minha Conta</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Perfil, foto, senha, dados de acesso e permissões</p>
        </template>

        <div class="space-y-6 max-w-6xl">
            <div v-if="showProfileSuccess" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span>Perfil atualizado com sucesso.</span>
            </div>

            <div v-if="showPasswordSuccess" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span>Senha alterada com sucesso.</span>
            </div>

            <section class="card p-5 sm:p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white flex items-center justify-center font-black text-xl shrink-0">
                            <img v-if="avatarPreview" :src="avatarPreview" :alt="profileForm.name" class="w-full h-full object-cover" />
                            <span v-else>{{ userInitials }}</span>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-xl font-black truncate" style="color: var(--text-heading);">{{ profileForm.name }}</h2>
                            <p class="text-sm text-slate-500 truncate">{{ profileForm.email }}</p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-[11px] font-bold">
                                    <i class="fa-solid fa-id-badge"></i>
                                    {{ accountContext.role_name || 'Administrador' }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-500/10 text-slate-500 text-[11px] font-bold">
                                    <i class="fa-solid fa-building"></i>
                                    {{ accountContext.company_name || user.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-center">
                        <div class="rounded-2xl border p-3" style="border-color: var(--border);">
                            <p class="text-xl font-black" style="color: var(--text-heading);">{{ grantedPermissionsCount }}</p>
                            <p class="text-[11px] text-slate-400 font-bold uppercase">Permissões</p>
                        </div>
                        <div class="rounded-2xl border p-3" style="border-color: var(--border);">
                            <p class="text-xl font-black" style="color: var(--text-heading);">{{ accountContext.is_owner ? 'Dono' : 'Membro' }}</p>
                            <p class="text-[11px] text-slate-400 font-bold uppercase">Tipo</p>
                        </div>
                        <div class="rounded-2xl border p-3 col-span-2 sm:col-span-1" style="border-color: var(--border);">
                            <p class="text-xl font-black" :class="accountContext.must_reset_password ? 'text-amber-600' : 'text-emerald-600'">{{ accountContext.must_reset_password ? 'Pendente' : 'Ok' }}</p>
                            <p class="text-[11px] text-slate-400 font-bold uppercase">Senha</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-2 rounded-2xl border p-1.5 bg-slate-50 dark:bg-slate-900/40" style="border-color: var(--border);">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    :class="[
                        'inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-extrabold transition-all',
                        activeTab === tab.id ? 'bg-white dark:bg-slate-800 text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-indigo-600'
                    ]"
                >
                    <i :class="tab.icon"></i>
                    <span>{{ tab.label }}</span>
                </button>
            </div>

            <section v-if="activeTab === 'profile'" class="card p-5 sm:p-7">
                <form @submit.prevent="submitProfile" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-[240px_minmax(0,1fr)] gap-6">
                        <div class="space-y-3">
                            <label class="block text-xs font-bold uppercase tracking-wider" style="color: var(--text-muted);">Foto de Perfil</label>
                            <div class="w-28 h-28 rounded-3xl overflow-hidden bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white flex items-center justify-center font-black text-3xl">
                                <img v-if="avatarPreview" :src="avatarPreview" :alt="profileForm.name" class="w-full h-full object-cover" />
                                <span v-else>{{ userInitials }}</span>
                            </div>
                            <input type="file" accept="image/*" @change="onAvatarChange" class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-indigo-600 file:text-white file:font-bold file:text-xs cursor-pointer" />
                            <p class="text-[11px] text-slate-400">PNG, JPG ou WEBP até 10 MB.</p>
                            <div v-if="avatarClientError" class="text-rose-500 text-xs font-semibold">{{ avatarClientError }}</div>
                            <div v-if="profileForm.errors.avatar" class="text-rose-500 text-xs font-semibold">{{ profileForm.errors.avatar }}</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="name">Nome Completo *</label>
                                <input id="name" v-model="profileForm.name" type="text" required autocomplete="name" class="form-control text-xs sm:text-sm rounded-xl block w-full" />
                                <div v-if="profileForm.errors.name" class="text-rose-500 text-xs font-semibold mt-1">{{ profileForm.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="email">E-mail de Acesso *</label>
                                <input id="email" v-model="profileForm.email" type="email" required autocomplete="username" class="form-control text-xs sm:text-sm rounded-xl block w-full" />
                                <div v-if="profileForm.errors.email" class="text-rose-500 text-xs font-semibold mt-1">{{ profileForm.errors.email }}</div>
                            </div>

                            <div class="md:col-span-2">
                                <PhoneInputWithCountry
                                    id="profile-phone"
                                    label="WhatsApp / Telefone para Notificações"
                                    v-model="profileForm.phone"
                                    v-model:countryCode="profileForm.country_code"
                                    :error="profileForm.errors.phone"
                                    placeholder="(00) 00000-0000"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="avatar_url">URL da Foto</label>
                                <input id="avatar_url" v-model="profileForm.avatar_url" type="text" placeholder="https://..." class="form-control text-xs sm:text-sm rounded-xl block w-full" />
                                <div v-if="profileForm.errors.avatar_url" class="text-rose-500 text-xs font-semibold mt-1">{{ profileForm.errors.avatar_url }}</div>
                            </div>

                            <div v-if="mustVerifyEmail && !user.email_verified_at" class="md:col-span-2 p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-semibold space-y-2">
                                <p><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Seu e-mail ainda não foi verificado.</p>
                                <button type="button" @click="resendVerification" :disabled="profileForm.processing" class="underline hover:text-amber-500">Reenviar e-mail de verificação</button>
                                <p v-if="verificationSent" class="text-green-600 dark:text-green-400 text-[11px] font-bold">Novo link enviado.</p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" :disabled="profileForm.processing" class="btn btn-primary py-2.5 px-5 rounded-xl text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/25 inline-flex items-center gap-2">
                        <i v-if="profileForm.processing" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-floppy-disk"></i>
                        <span>{{ profileForm.processing ? 'Salvando...' : 'Salvar Perfil' }}</span>
                    </button>
                </form>
            </section>

            <section v-if="activeTab === 'security'" class="card p-5 sm:p-7">
                <form @submit.prevent="submitPassword" class="space-y-4 max-w-2xl">
                    <div>
                        <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="current_password">Senha Atual</label>
                        <input id="current_password" v-model="passwordForm.current_password" type="password" autocomplete="current-password" class="form-control text-xs sm:text-sm rounded-xl block w-full" />
                        <div v-if="passwordForm.errors.current_password" class="text-rose-500 text-xs font-semibold mt-1">{{ passwordForm.errors.current_password }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="new_password">Nova Senha</label>
                            <input id="new_password" v-model="passwordForm.password" type="password" autocomplete="new-password" class="form-control text-xs sm:text-sm rounded-xl block w-full" />
                            <div v-if="passwordForm.errors.password" class="text-rose-500 text-xs font-semibold mt-1">{{ passwordForm.errors.password }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="password_confirmation">Confirmar Nova Senha</label>
                            <input id="password_confirmation" v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="form-control text-xs sm:text-sm rounded-xl block w-full" />
                            <div v-if="passwordForm.errors.password_confirmation" class="text-rose-500 text-xs font-semibold mt-1">{{ passwordForm.errors.password_confirmation }}</div>
                        </div>
                    </div>

                    <button type="submit" :disabled="passwordForm.processing" class="btn btn-primary py-2.5 px-5 rounded-xl text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/25 inline-flex items-center gap-2">
                        <i v-if="passwordForm.processing" class="fa-solid fa-spinner fa-spin"></i>
                        <i v-else class="fa-solid fa-key"></i>
                        <span>{{ passwordForm.processing ? 'Atualizando...' : 'Atualizar Senha' }}</span>
                    </button>
                </form>
            </section>

            <section v-if="activeTab === 'account'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="card p-5 sm:p-6 space-y-4">
                    <h2 class="text-base font-extrabold" style="color: var(--text-heading);">Dados da Conta</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Empresa</dt><dd class="font-bold text-right">{{ accountContext.company_name }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Tipo</dt><dd class="font-bold text-right">{{ accountContext.is_owner ? 'Conta principal' : 'Conta de membro' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Cargo</dt><dd class="font-bold text-right">{{ accountContext.role_name }}</dd></div>
                        <div v-if="teamMember" class="flex justify-between gap-4"><dt class="text-slate-400">Função pública</dt><dd class="font-bold text-right">{{ teamMember.job_title || '-' }}</dd></div>
                        <div v-if="teamMember?.phone" class="flex justify-between gap-4"><dt class="text-slate-400">Telefone</dt><dd class="font-bold text-right">{{ teamMember.phone }}</dd></div>
                    </dl>
                </div>

                <div class="card p-5 sm:p-6 space-y-4">
                    <h2 class="text-base font-extrabold" style="color: var(--text-heading);">Página Pública</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Subdomínio</dt><dd class="font-bold text-right">{{ teamMember?.subdomain || accountContext.subdomain || '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Domínio próprio</dt><dd class="font-bold text-right">{{ teamMember?.custom_domain || accountContext.custom_domain || '-' }}</dd></div>
                    </dl>
                    <a v-if="accountContext.public_booking_url" :href="accountContext.public_booking_url" target="_blank" rel="noopener noreferrer" class="btn btn-outline py-2.5 px-4 rounded-xl text-xs font-bold inline-flex items-center gap-2">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        <span>Abrir página pública</span>
                    </a>
                </div>
            </section>

            <section v-if="activeTab === 'permissions'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div v-for="module in permissionModules" :key="module.title" class="card p-5 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                    <i :class="module.icon"></i>
                                </div>
                                <h3 class="text-sm font-extrabold" style="color: var(--text-heading);">{{ module.title }}</h3>
                            </div>
                            <span class="text-[11px] font-black text-slate-400">{{ module.granted_count }}/{{ module.permissions.length }}</span>
                        </div>
                        <div class="space-y-2">
                            <div v-for="permission in module.permissions" :key="permission.key" class="flex items-center gap-2 text-xs">
                                <i :class="['fa-solid', permission.granted ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-slate-300 dark:text-slate-700']"></i>
                                <span :class="permission.granted ? 'font-semibold text-slate-700 dark:text-slate-200' : 'text-slate-400'">{{ permission.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-5 sm:p-6 border-rose-500/20 space-y-4">
                    <h2 class="text-base font-extrabold text-rose-600 dark:text-rose-400">Zona de Risco</h2>
                    <p class="text-xs text-slate-500 max-w-2xl">Excluir a conta remove o acesso e os dados vinculados de forma permanente. Use apenas quando tiver certeza.</p>
                    <button type="button" @click="openDeleteModal" class="py-2.5 px-5 rounded-xl font-bold text-xs sm:text-sm shadow-lg inline-flex items-center justify-center gap-2 transition-all hover:scale-[1.02] cursor-pointer bg-rose-600 text-white">
                        <i class="fa-solid fa-trash-can text-xs"></i>
                        <span>Excluir Conta Permanentemente</span>
                    </button>
                </div>
            </section>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 z-[999999] flex items-center justify-center p-4 liquid-glass-backdrop" @click="handleBackdropClick">
            <div class="w-full max-w-md p-6 space-y-4 relative rounded-3xl liquid-glass-card" @click.stop>
                <div class="flex items-center gap-3 pb-3 border-b" style="border-color: var(--border);">
                    <div class="w-10 h-10 rounded-2xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg shadow-md">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-rose-600 dark:text-rose-400">Excluir Conta?</h3>
                        <p class="text-xs opacity-60">Esta ação é irreversível</p>
                    </div>
                </div>

                <p class="text-xs sm:text-sm leading-relaxed text-slate-600 dark:text-slate-400">Digite sua senha atual para confirmar a exclusão permanente da conta.</p>

                <form @submit.prevent="submitDelete" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="delete_password">Senha de Confirmação</label>
                        <input id="delete_password" v-model="deleteForm.password" type="password" required class="form-control text-xs sm:text-sm rounded-xl block w-full" />
                        <div v-if="deleteForm.errors.password" class="text-rose-500 text-xs font-semibold mt-1">{{ deleteForm.errors.password }}</div>
                    </div>

                    <div class="pt-3 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                        <button type="button" @click="closeDeleteModal" class="btn btn-outline py-2 px-4 text-xs font-bold rounded-xl">Cancelar</button>
                        <button type="submit" :disabled="deleteForm.processing" class="py-2 px-5 text-xs font-bold rounded-xl shadow-lg inline-flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-60 bg-rose-600 text-white">
                            <i v-if="deleteForm.processing" class="fa-solid fa-spinner fa-spin text-xs"></i>
                            <i v-else class="fa-solid fa-trash-can text-xs"></i>
                            <span>{{ deleteForm.processing ? 'Excluindo...' : 'Confirmar Exclusão' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
