<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    status: {
        type: String,
        default: null
    },
    mustVerifyEmail: {
        type: Boolean,
        default: false
    }
});

const page = usePage();
const showDeleteModal = ref(false);
const verificationSent = ref(false);

const profileForm = useForm({
    name: props.user.name || '',
    email: props.user.email || ''
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: ''
});

const deleteForm = useForm({
    password: ''
});

const showProfileSuccess = computed(() => props.status === 'profile-updated');
const showPasswordSuccess = computed(() => props.status === 'password-updated');

const submitProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            verificationSent.value = false;
        },
        errorBag: 'updateProfileInformation'
    });
};

const resendVerification = () => {
    profileForm.post(route('verification.send'), {
        preserveScroll: true,
        onSuccess: () => {
            verificationSent.value = true;
        }
    });
};

const submitPassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        errorBag: 'updatePassword'
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
        errorBag: 'userDeletion'
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Configurações de Perfil - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Minha Conta & Perfil</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Gerencie seus dados pessoais, senha e preferências de acesso</p>
        </template>

        <div class="space-y-6 max-w-4xl">

            <div v-if="showProfileSuccess" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span>Suas informações de perfil foram atualizadas com sucesso!</span>
            </div>

            <div v-if="showPasswordSuccess" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span>Sua senha de acesso foi alterada com sucesso!</span>
            </div>

            <div class="card p-6 sm:p-8">
                <div class="max-w-xl">
                    <section class="space-y-6">
                        <header class="space-y-1">
                            <h2 class="text-base font-extrabold" style="color: var(--text-heading);">
                                Informações do Perfil
                            </h2>
                            <p class="text-xs opacity-70">
                                Atualize as informações cadastrais e o endereço de e-mail da sua conta de acesso.
                            </p>
                        </header>

                        <form @submit.prevent="submitProfile" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="name">Nome Completo *</label>
                                <input
                                    type="text"
                                    id="name"
                                    v-model="profileForm.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                    class="form-control text-xs sm:text-sm rounded-xl block w-full"
                                >
                                <div v-if="profileForm.errors.name" class="text-rose-500 text-xs font-semibold mt-1 block">
                                    {{ profileForm.errors.name }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="email">E-mail de Acesso *</label>
                                <input
                                    type="email"
                                    id="email"
                                    v-model="profileForm.email"
                                    required
                                    autocomplete="username"
                                    class="form-control text-xs sm:text-sm rounded-xl block w-full"
                                >
                                <div v-if="profileForm.errors.email" class="text-rose-500 text-xs font-semibold mt-1 block">
                                    {{ profileForm.errors.email }}
                                </div>

                                <div v-if="mustVerifyEmail && !user.email_verified_at" class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-semibold mt-3 space-y-2">
                                    <p class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <span>Seu endereço de e-mail não foi verificado.</span>
                                    </p>

                                    <button
                                        type="button"
                                        @click="resendVerification"
                                        :disabled="profileForm.processing"
                                        class="text-[11px] underline hover:text-amber-500 transition-colors"
                                    >
                                        Clique aqui para reenviar o e-mail de verificação.
                                    </button>

                                    <p v-if="verificationSent" class="text-green-600 dark:text-green-400 text-[11px] font-bold">
                                        Um novo link de verificação foi enviado para o seu endereço de e-mail!
                                    </p>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="profileForm.processing"
                                    class="btn btn-primary py-2.5 px-5 rounded-xl text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/25 flex items-center gap-2"
                                >
                                    <i v-if="profileForm.processing" class="fa-solid fa-spinner fa-spin"></i>
                                    <i v-else class="fa-solid fa-floppy-disk"></i>
                                    <span>{{ profileForm.processing ? 'Salvando...' : 'Salvar Alterações' }}</span>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>

            <div class="card p-6 sm:p-8">
                <div class="max-w-xl">
                    <section class="space-y-6">
                        <header class="space-y-1">
                            <h2 class="text-base font-extrabold" style="color: var(--text-heading);">
                                Alterar Senha
                            </h2>
                            <p class="text-xs opacity-70">
                                Certifique-se de usar uma senha forte e segura para proteger sua conta contra acessos não autorizados.
                            </p>
                        </header>

                        <form @submit.prevent="submitPassword" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="current_password">Senha Atual</label>
                                <input
                                    type="password"
                                    id="current_password"
                                    v-model="passwordForm.current_password"
                                    autocomplete="current-password"
                                    class="form-control text-xs sm:text-sm rounded-xl block w-full"
                                >
                                <div v-if="passwordForm.errors.current_password" class="text-rose-500 text-xs font-semibold mt-1 block">
                                    {{ passwordForm.errors.current_password }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="new_password">Nova Senha</label>
                                <input
                                    type="password"
                                    id="new_password"
                                    v-model="passwordForm.password"
                                    autocomplete="new-password"
                                    class="form-control text-xs sm:text-sm rounded-xl block w-full"
                                >
                                <div v-if="passwordForm.errors.password" class="text-rose-500 text-xs font-semibold mt-1 block">
                                    {{ passwordForm.errors.password }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="password_confirmation">Confirmar Nova Senha</label>
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    v-model="passwordForm.password_confirmation"
                                    autocomplete="new-password"
                                    class="form-control text-xs sm:text-sm rounded-xl block w-full"
                                >
                                <div v-if="passwordForm.errors.password_confirmation" class="text-rose-500 text-xs font-semibold mt-1 block">
                                    {{ passwordForm.errors.password_confirmation }}
                                </div>
                            </div>

                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="btn btn-primary py-2.5 px-5 rounded-xl text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/25 flex items-center gap-2"
                                >
                                    <i v-if="passwordForm.processing" class="fa-solid fa-spinner fa-spin"></i>
                                    <i v-else class="fa-solid fa-key"></i>
                                    <span>{{ passwordForm.processing ? 'Atualizando...' : 'Atualizar Senha' }}</span>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>

            <div class="card p-6 sm:p-8" style="border-color: rgba(239, 68, 68, 0.2);">
                <div class="max-w-xl">
                    <section class="space-y-6">
                        <header class="space-y-1">
                            <h2 class="text-base font-extrabold text-rose-600 dark:text-rose-400">
                                Excluir Conta Permanentemente
                            </h2>
                            <p class="text-xs opacity-70">
                                Uma vez que sua conta for excluída, todos os recursos e dados vinculados a ela serão permanentemente removidos. Por favor, certifique-se de baixar qualquer informação necessária antes de prosseguir.
                            </p>
                        </header>

                        <div class="pt-2">
                            <button
                                type="button"
                                @click="openDeleteModal"
                                class="py-2.5 px-5 rounded-xl font-bold text-xs sm:text-sm shadow-lg inline-flex items-center justify-center gap-2 transition-all hover:scale-[1.02] cursor-pointer"
                                style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #fff; box-shadow: 0 6px 18px rgba(220, 38, 38, 0.25);"
                            >
                                <i class="fa-solid fa-trash-can text-xs"></i>
                                <span>Excluir Conta Permanentemente</span>
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-[999999] flex items-center justify-center p-4 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div
                class="w-full max-w-md p-6 space-y-4 relative rounded-3xl liquid-glass-card"
                style="animation: liquidModalIn 0.28s ease;"
                @click.stop
            >
                <div class="flex items-center gap-3 pb-3 border-b" style="border-color: var(--border);">
                    <div class="w-10 h-10 rounded-2xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg shadow-md">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-rose-600 dark:text-rose-400">Excluir Conta?</h3>
                        <p class="text-xs opacity-60">Esta ação é irreversível</p>
                    </div>
                </div>

                <p class="text-xs sm:text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                    Tem certeza de que deseja permanentemente remover sua conta? Insira sua senha para confirmar a propriedade e finalizar o processo.
                </p>

                <form @submit.prevent="submitDelete" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);" for="delete_password">Sua Senha de Confirmação</label>
                        <input
                            type="password"
                            id="delete_password"
                            v-model="deleteForm.password"
                            placeholder="Digite sua senha atual"
                            required
                            class="form-control text-xs sm:text-sm rounded-xl block w-full"
                        >
                        <div v-if="deleteForm.errors.password" class="text-rose-500 text-xs font-semibold mt-1 block">
                            {{ deleteForm.errors.password }}
                        </div>
                    </div>

                    <div class="pt-3 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            class="btn btn-outline py-2 px-4 text-xs font-bold rounded-xl"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="deleteForm.processing"
                            class="py-2 px-5 text-xs font-bold rounded-xl shadow-lg inline-flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-60"
                            style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #fff; box-shadow: 0 6px 18px rgba(220, 38, 38, 0.25);"
                        >
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

<style>
@keyframes liquidModalIn {
    0% {
        opacity: 0;
        transform: scale(0.95) translateY(12px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>
