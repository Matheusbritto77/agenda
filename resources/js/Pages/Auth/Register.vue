<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import PhoneInputWithCountry from '@/Components/PhoneInputWithCountry.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    country_code: 'BR',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const slugify = (text) => {
    return (text || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
};

const generatedSubdomain = computed(() => {
    const slug = slugify(form.name);
    return slug;
});

const subdomainPreview = computed(() => {
    const slug = generatedSubdomain.value;
    return slug || 'seu-estabelecimento';
});

const previewSubdomain = ref(subdomainPreview.value);
const availability = ref({
    state: 'idle',
    message: '',
    suggested: subdomainPreview.value,
});

let availabilityTimeout = null;
let availabilityRequestId = 0;

const checkSubdomainAvailability = async (subdomain) => {
    const normalized = slugify(subdomain);

    if (!normalized) {
        previewSubdomain.value = subdomainPreview.value;
        availability.value = {
            state: 'idle',
            message: '',
            suggested: subdomainPreview.value,
        };
        return;
    }

    const requestId = ++availabilityRequestId;
    availability.value = {
        state: 'checking',
        message: 'Verificando disponibilidade...',
        suggested: normalized,
    };

    try {
        const response = await fetch(`/api/subdomains/availability?subdomain=${encodeURIComponent(normalized)}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        const payload = await response.json();

        if (requestId !== availabilityRequestId) {
            return;
        }

        const suggested = payload.suggested_subdomain || normalized;

        previewSubdomain.value = suggested;
        availability.value = {
            state: payload.available ? 'available' : 'taken',
            message: payload.available
                ? 'Disponível agora'
                : `Já existe. Vamos sugerir ${suggested}`,
            suggested,
        };
    } catch {
        if (requestId !== availabilityRequestId) {
            return;
        }

        previewSubdomain.value = normalized;
        availability.value = {
            state: 'error',
            message: 'Não foi possível verificar agora.',
            suggested: normalized,
        };
    }
};

watch(
    generatedSubdomain,
    (value) => {
        clearTimeout(availabilityTimeout);

        if (!value) {
            previewSubdomain.value = 'seu-estabelecimento';
            availability.value = {
                state: 'idle',
                message: '',
                suggested: 'seu-estabelecimento',
            };
            return;
        }

        previewSubdomain.value = value;
        availabilityTimeout = setTimeout(() => {
            checkSubdomainAvailability(value);
        }, 300);
    },
    { immediate: true }
);

const submit = () => {
    form.post(route('register'), {
        onSuccess: () => {
            window.location.href = route('admin.dashboard');
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout maxWidthClass="max-w-2xl">
        <Head title="Criar Conta" />

        <div class="space-y-6">
            <!-- Header Icon & Title -->
            <div class="text-center space-y-2">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-500 text-white flex items-center justify-center mx-auto shadow-lg shadow-indigo-600/30">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles">
                        <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                        <path d="M5 3v4"/>
                        <path d="M19 17v4"/>
                        <path d="M3 5h4"/>
                        <path d="M17 19h4"/>
                    </svg>
                </div>

                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                    Crie sua Conta
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
                    Comece a receber agendamentos online em poucos minutos.
                </p>
            </div>

            <form @submit.prevent="submit" class="text-left space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Name Field -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300" for="name">
                            Nome do Responsável / Negócio
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <input
                                id="name"
                                type="text"
                                class="block w-full pl-10 pr-4 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                v-model="form.name"
                                placeholder="Ex: Barbearia Estilo Nobre"
                                required
                                autofocus
                                autocomplete="name"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>

                    <!-- Email Field -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300" for="email">
                            E-mail Profissional
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail">
                                    <rect width="20" height="16" x="2" y="4" rx="2"/>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                                </svg>
                            </div>
                            <input
                                id="email"
                                type="email"
                                class="block w-full pl-10 pr-4 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                v-model="form.email"
                                placeholder="contato@empresa.com"
                                required
                                autocomplete="username"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <!-- WhatsApp / Telefone Field with Country Selector -->
                    <div class="space-y-1.5 md:col-span-2">
                        <PhoneInputWithCountry
                            id="phone"
                            label="WhatsApp / Telefone de Notificação"
                            v-model="form.phone"
                            v-model:countryCode="form.country_code"
                            :error="form.errors.phone"
                            placeholder="(00) 00000-0000"
                        />
                    </div>

                    <!-- Real-Time Subdomain Preview Banner -->
                    <div class="p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-xs space-y-1 md:col-span-2">
                        <div class="flex items-center justify-between text-[11px] font-bold text-indigo-600 dark:text-indigo-400">
                            <span class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-globe">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                                    <path d="M2 12h20"/>
                                </svg>
                                <span>Link Exclusivo do seu Agendamento:</span>
                            </span>
                            <span
                                class="w-2 h-2 rounded-full animate-pulse"
                                :class="availability.state === 'taken'
                                    ? 'bg-rose-500'
                                    : availability.state === 'available'
                                        ? 'bg-emerald-500'
                                        : 'bg-amber-500'"
                                :title="availability.message || 'Verificando'"
                            ></span>
                        </div>
                        <div class="font-mono text-[11px] sm:text-xs font-bold text-slate-800 dark:text-slate-200 truncate">
                            https://<span class="text-indigo-600 dark:text-indigo-400 font-extrabold">{{ previewSubdomain }}</span>.{{ $page.props.appDomain || 'localhost' }}
                        </div>
                        <div class="text-[11px] font-semibold"
                             :class="availability.state === 'taken'
                                ? 'text-rose-600 dark:text-rose-400'
                                : availability.state === 'available'
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-slate-500 dark:text-slate-400'">
                            {{ availability.message || 'O sistema vai sugerir um subdomínio disponível automaticamente.' }}
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300" for="password">
                            Senha
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </div>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                v-model="form.password"
                                placeholder="Mínimo de 8 caracteres"
                                required
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                            >
                                <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-off">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                    <line x1="2" x2="22" y1="2" y2="22"/>
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300" for="password_confirmation">
                            Confirmar Senha
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                                    <path d="m9 12 2 2 4-4"/>
                                </svg>
                            </div>
                            <input
                                id="password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                class="block w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                v-model="form.password_confirmation"
                                placeholder="Repita sua senha"
                                required
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                            >
                                <svg v-if="showConfirmPassword" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-off">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                    <line x1="2" x2="22" y1="2" y2="22"/>
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-1" :message="form.errors.password_confirmation" />
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full py-3 px-4 text-xs sm:text-sm font-extrabold rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 cursor-pointer transition-all duration-200 mt-4 md:col-span-2"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <span>{{ form.processing ? 'Cadastrando...' : 'Criar Minha Conta Grátis' }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right">
                            <path d="M5 12h14"/>
                            <path d="m12 5 7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Footer Switcher Link -->
            <div class="pt-4 border-t border-slate-200/80 dark:border-slate-800 text-center text-xs text-slate-500 dark:text-slate-400">
                Já possui uma conta cadastrada?
                <a :href="route('login')" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline ml-1">
                    Fazer login &rarr;
                </a>
            </div>
        </div>
    </GuestLayout>
</template>
