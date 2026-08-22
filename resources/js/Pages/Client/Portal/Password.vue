<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showPass = ref(false);
const form = useForm({ current_password: '', password: '', password_confirmation: '' });

const submit = () => form.put(route('client.password.update'), {
    onFinish: () => form.reset(),
});
</script>

<template>
    <GuestLayout>
        <Head title="Definir Senha Pessoal - Agendae" />
        <div class="space-y-6">
            <!-- Header Icon & Title -->
            <div class="text-center space-y-2">
                <div class="w-14 h-14 rounded-3xl bg-gradient-to-tr from-amber-500 to-indigo-600 text-white flex items-center justify-center mx-auto shadow-xl shadow-indigo-500/25">
                    <i class="fa-solid fa-shield-halved text-2xl"></i>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                    Proteja sua Conta
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Para sua segurança, defina uma nova senha definitiva para acessar sua área do cliente.
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <!-- Temporary password -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Senha Temporária (recebida por e-mail)
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-key text-sm"></i>
                        </div>
                        <input
                            v-model="form.current_password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Sua senha temporária"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-xs"
                            :class="{ 'border-rose-500': form.errors.current_password }"
                        >
                    </div>
                    <InputError class="mt-1" :message="form.errors.current_password" />
                </div>

                <!-- New password -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Criar Nova Senha
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input
                            v-model="form.password"
                            :type="showPass ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            placeholder="Mínimo 8 caracteres"
                            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-xs"
                            :class="{ 'border-rose-500': form.errors.password }"
                        >
                        <button
                            type="button"
                            @click="showPass = !showPass"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-indigo-600 cursor-pointer"
                        >
                            <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <!-- Confirm password -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Confirmar Nova Senha
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-circle-check text-sm"></i>
                        </div>
                        <input
                            v-model="form.password_confirmation"
                            :type="showPass ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            placeholder="Repita a nova senha"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-xs"
                        >
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 px-4 rounded-xl font-black text-xs sm:text-sm text-white bg-gradient-to-r from-indigo-600 via-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 shadow-lg shadow-indigo-600/30 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 mt-2"
                >
                    <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-check"></i>
                    <span>{{ form.processing ? 'Atualizando…' : 'Salvar Nova Senha & Acessar' }}</span>
                </button>
            </form>
        </div>
    </GuestLayout>
</template>
