<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showPassword = ref(false);
const form = useForm({ email: '', password: '', remember: true });

const submit = () => form.post(route('client.login.store'), {
    onFinish: () => form.reset('password'),
});
</script>

<template>
    <GuestLayout>
        <Head title="Área do Cliente - Agendae" />
        <div class="space-y-6">
            <!-- Header Icon & Title -->
            <div class="text-center space-y-2">
                <div class="w-14 h-14 rounded-3xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-500 text-white flex items-center justify-center mx-auto shadow-xl shadow-indigo-500/25">
                    <i class="fa-solid fa-heart text-2xl"></i>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                    Sua Área do Cliente
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Acompanhe seus agendamentos, avalie atendimentos e acesse as empresas parceiras.
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <!-- Email Field -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        E-mail Cadastrado
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-envelope text-sm"></i>
                        </div>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="seu.email@exemplo.com"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-xs"
                            :class="{ 'border-rose-500': form.errors.email }"
                        >
                    </div>
                    <InputError class="mt-1" :message="form.errors.email" />
                </div>

                <!-- Password Field -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Senha
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="Sua senha de acesso"
                            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-xs"
                            :class="{ 'border-rose-500': form.errors.password }"
                        >
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-indigo-600 transition-colors cursor-pointer"
                        >
                            <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-400 select-none">
                        <input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 accent-indigo-600">
                        <span>Lembrar meu acesso</span>
                    </label>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 px-4 rounded-xl font-black text-xs sm:text-sm text-white bg-gradient-to-r from-indigo-600 via-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 shadow-lg shadow-indigo-600/30 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                >
                    <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>{{ form.processing ? 'Entrando…' : 'Acessar Minha Área' }}</span>
                </button>
            </form>

            <div class="rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-4 text-center space-y-1">
                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">
                    <i class="fa-solid fa-circle-info text-indigo-500 mr-1"></i>
                    Primeiro acesso?
                </p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Sua senha é gerada e enviada automaticamente por e-mail no seu primeiro agendamento confirmado.
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
