<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    domainSettings: {
        type: Object,
        default: () => ({
            base_domain: 'localhost',
            active_domain_type: 'subdomain',
            subdomain: '',
            custom_domain: '',
            subdomain_url: '',
            custom_domain_url: '',
            public_url: '',
        })
    }
});

const page = usePage();

const form = useForm({
    subdomain: props.domainSettings.subdomain || '',
    custom_domain: props.domainSettings.custom_domain || '',
    active_domain_type: props.domainSettings.active_domain_type || 'subdomain',
});

const baseDomain = computed(() => props.domainSettings.base_domain || 'localhost');
const currentUserId = computed(() => page.props.auth?.user?.id ?? null);

const livePreviewSubdomain = ref((form.subdomain || 'suaempresa').toLowerCase().replace(/[^a-z0-9-]/g, ''));
const livePreviewUrl = computed(() => {
    const sub = livePreviewSubdomain.value || 'suaempresa';
    const scheme = window.location.protocol.replace(':', '');
    const port = window.location.port ? `:${window.location.port}` : '';
    return `${scheme}://${sub}.${baseDomain.value}${port}`;
});

const subdomainAvailability = ref({
    state: 'idle',
    message: '',
    suggested: livePreviewSubdomain.value,
});

let availabilityTimeout = null;
let availabilityRequestId = 0;

const normalizeSubdomain = (value) => (value || '')
    .toString()
    .toLowerCase()
    .trim()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '')
    .replace(/--+/g, '-')
    .replace(/^-+/, '')
    .replace(/-+$/, '');

const checkSubdomainAvailability = async (value) => {
    const normalized = normalizeSubdomain(value);

    if (!normalized) {
        livePreviewSubdomain.value = 'suaempresa';
        subdomainAvailability.value = {
            state: 'idle',
            message: '',
            suggested: 'suaempresa',
        };
        return;
    }

    const requestId = ++availabilityRequestId;
    subdomainAvailability.value = {
        state: 'checking',
        message: 'Verificando disponibilidade...',
        suggested: normalized,
    };

    try {
        const params = new URLSearchParams({
            subdomain: normalized,
        });

        if (currentUserId.value) {
            params.set('ignore_user_id', String(currentUserId.value));
        }

        const response = await fetch(`/api/subdomains/availability?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        const payload = await response.json();

        if (requestId !== availabilityRequestId) {
            return;
        }

        const suggested = payload.suggested_subdomain || normalized;
        livePreviewSubdomain.value = suggested;
        subdomainAvailability.value = {
            state: payload.available ? 'available' : 'taken',
            message: payload.available ? 'Disponível' : `Já existe. Sugestão: ${suggested}`,
            suggested,
        };
    } catch {
        if (requestId !== availabilityRequestId) {
            return;
        }

        livePreviewSubdomain.value = normalized;
        subdomainAvailability.value = {
            state: 'error',
            message: 'Não foi possível verificar agora.',
            suggested: normalized,
        };
    }
};

watch(
    () => form.subdomain,
    (value) => {
        clearTimeout(availabilityTimeout);

        const normalized = normalizeSubdomain(value);
        livePreviewSubdomain.value = normalized || 'suaempresa';

        availabilityTimeout = setTimeout(() => {
            checkSubdomainAvailability(normalized);
        }, 300);
    },
    { immediate: true }
);

const selectDomainType = (type) => {
    form.active_domain_type = type;
};

const submit = () => {
    form.post(route('admin.domain.update'), {
        preserveScroll: true,
    });
};

const successMessage = computed(() => page.props.flash?.success);
</script>

<template>
    <AdminLayout>
        <Head title="Guia de Domínio" />

        <div class="space-y-6 max-w-5xl mx-auto">

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">Modo de Endereço Ativo</h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Selecione e ative o modo de direcionamento que o seu cliente usará para agendar.</p>
                </div>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                    <i class="fa-solid fa-shield-check text-xs"></i>
                    <span>Certificado SSL Incluso</span>
                </div>
            </div>

            <!-- Success Message -->
            <div
                v-if="successMessage"
                class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-semibold flex items-center gap-2"
            >
                <i class="fa-solid fa-circle-check text-sm"></i>
                <span>{{ successMessage }}</span>
            </div>

            <!-- Form Container -->
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Selector Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Card A: Subdomínio Grátis -->
                    <div
                        class="rounded-2xl p-5 sm:p-6 space-y-4 transition-all border-2 cursor-pointer relative bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl"
                        :class="form.active_domain_type === 'subdomain'
                            ? 'border-indigo-600 ring-2 ring-indigo-500/30 shadow-xl'
                            : 'border-slate-200 dark:border-slate-800 opacity-75 hover:opacity-100'"
                        @click="selectDomainType('subdomain')"
                    >
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    name="active_domain_type"
                                    value="subdomain"
                                    :checked="form.active_domain_type === 'subdomain'"
                                    @change="selectDomainType('subdomain')"
                                    class="w-5 h-5 accent-indigo-600 cursor-pointer"
                                >
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shrink-0">
                                    <i class="fa-solid fa-link"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-base sm:text-lg text-slate-900 dark:text-slate-100">Subdomínio Grátis</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">`.{{ baseDomain }}` instantâneo</p>
                                </div>
                            </div>
                            <span
                                class="px-2.5 py-1 rounded-full text-[11px] font-extrabold tracking-wider uppercase"
                                :class="form.active_domain_type === 'subdomain'
                                    ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                                    : 'bg-slate-200 dark:bg-slate-800 text-slate-500'"
                            >
                                {{ form.active_domain_type === 'subdomain' ? 'Ativo' : 'Clique p/ Ativar' }}
                            </span>
                        </div>

                        <div class="space-y-4" @click.stop>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="subdomain">Seu Subdomínio *</label>
                                <div class="flex items-center">
                                    <input
                                        type="text"
                                        id="subdomain"
                                        v-model="form.subdomain"
                                        class="block w-full py-2.5 px-3 text-xs sm:text-sm rounded-l-xl rounded-r-none border border-r-0 bg-slate-100/70 dark:bg-slate-800/70 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                        :class="subdomainAvailability.state === 'taken'
                                            ? 'border-rose-400 dark:border-rose-500'
                                            : subdomainAvailability.state === 'available'
                                                ? 'border-emerald-400 dark:border-emerald-500'
                                                : 'border-slate-300 dark:border-slate-700'"
                                        placeholder="suaempresa"
                                    >
                                    <span class="px-3 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-bold border border-slate-300 dark:border-slate-700 rounded-r-xl shrink-0">.{{ baseDomain }}</span>
                                </div>
                                <span class="text-[11px] opacity-70 mt-1 block">Apenas letras, números e hífens.</span>
                                <p
                                    class="mt-1 text-[11px] font-semibold"
                                    :class="subdomainAvailability.state === 'taken'
                                        ? 'text-rose-600 dark:text-rose-400'
                                        : subdomainAvailability.state === 'available'
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-slate-500 dark:text-slate-400'"
                                >
                                    {{ subdomainAvailability.message || 'Digite para verificar a disponibilidade em tempo real.' }}
                                </p>
                                <p v-if="form.errors.subdomain" class="text-rose-500 text-xs font-medium mt-1">{{ form.errors.subdomain }}</p>
                            </div>

                            <!-- Live Preview -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Preview ao Vivo do Link</label>
                                <div class="p-3 bg-slate-100 dark:bg-slate-900/80 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <i class="fa-solid fa-globe text-indigo-500 text-sm shrink-0"></i>
                                        <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 truncate">{{ livePreviewUrl }}</span>
                                    </div>
                                    <a :href="livePreviewUrl" target="_blank" class="text-xs font-bold text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 p-1 shrink-0" title="Testar Link">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card B: Domínio Próprio -->
                    <div
                        class="rounded-2xl p-5 sm:p-6 space-y-4 transition-all border-2 cursor-pointer relative bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl"
                        :class="form.active_domain_type === 'custom'
                            ? 'border-indigo-600 ring-2 ring-indigo-500/30 shadow-xl'
                            : 'border-slate-200 dark:border-slate-800 opacity-75 hover:opacity-100'"
                        @click="selectDomainType('custom')"
                    >
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    name="active_domain_type"
                                    value="custom"
                                    :checked="form.active_domain_type === 'custom'"
                                    @change="selectDomainType('custom')"
                                    class="w-5 h-5 accent-indigo-600 cursor-pointer"
                                >
                                <div class="w-10 h-10 rounded-xl bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border border-cyan-500/30 flex items-center justify-center text-lg shrink-0">
                                    <i class="fa-solid fa-globe"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-base sm:text-lg text-slate-900 dark:text-slate-100">Domínio Próprio</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Seu próprio site registrado</p>
                                </div>
                            </div>
                            <span
                                class="px-2.5 py-1 rounded-full text-[11px] font-extrabold tracking-wider uppercase"
                                :class="form.active_domain_type === 'custom'
                                    ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                                    : 'bg-slate-200 dark:bg-slate-800 text-slate-500'"
                            >
                                {{ form.active_domain_type === 'custom' ? 'Ativo' : 'Clique p/ Ativar' }}
                            </span>
                        </div>

                        <div class="space-y-4" @click.stop>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5" for="custom_domain">Domínio Personalizado</label>
                                <input
                                    type="text"
                                    id="custom_domain"
                                    v-model="form.custom_domain"
                                    class="block w-full py-2.5 px-3 text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-100/70 dark:bg-slate-800/70 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all"
                                    placeholder="ex: agenda.meusite.com.br"
                                >
                                <span class="text-[11px] opacity-70 mt-1 block">Sem http:// ou https://</span>
                                <p v-if="form.errors.custom_domain" class="text-rose-500 text-xs font-medium mt-1">{{ form.errors.custom_domain }}</p>
                            </div>

                            <div class="p-3.5 bg-slate-100 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-800 space-y-1.5 text-xs opacity-90">
                                <strong class="font-bold block text-slate-900 dark:text-slate-100">Apontamento DNS Exigido:</strong>
                                <div class="space-y-0.5 font-mono text-[11px]">
                                    <p><span class="text-indigo-500 font-bold">Tipo:</span> CNAME</p>
                                    <p><span class="text-indigo-500 font-bold">Host:</span> agenda (ou subdomínio)</p>
                                    <p><span class="text-indigo-500 font-bold">Destino:</span> cname.agendae.app</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Error for active_domain_type -->
                <p v-if="form.errors.active_domain_type" class="text-rose-500 text-xs font-medium">{{ form.errors.active_domain_type }}</p>

                <!-- Submit Bar -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 py-3 px-6 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-indigo-600 via-indigo-500 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 shadow-lg shadow-indigo-600/30 hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i v-if="!form.processing" class="fa-solid fa-floppy-disk text-xs"></i>
                        <i v-else class="fa-solid fa-spinner fa-spin text-xs"></i>
                        <span>Salvar Escolha de Domínio</span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
