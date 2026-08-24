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
const cnameTarget = computed(() => props.domainSettings.cname_target || props.domainSettings.base_domain || window.location.host);
const currentUserId = computed(() => page.props.auth?.user?.id ?? null);

const cleanCustomDomain = computed(() => {
    return (form.custom_domain || '')
        .trim()
        .toLowerCase()
        .replace(/^https?:\/\//i, '')
        .split('/')[0]
        .split(':')[0];
});

const dnsHost = computed(() => {
    const raw = cleanCustomDomain.value;
    if (!raw) return 'agenda';
    const parts = raw.split('.');
    if (parts.length > 2) {
        if (parts.length === 4 && (parts[2] === 'com' || parts[2] === 'net' || parts[2] === 'org') && parts[3] === 'br') {
            return parts[0];
        }
        if (parts.length === 3 && parts[1] === 'com' && parts[2] === 'br') {
            return '@';
        }
        return parts[0];
    }
    return '@';
});

const dnsType = computed(() => {
    const raw = cleanCustomDomain.value;
    if (!raw) return 'CNAME';
    const parts = raw.split('.');
    if (parts.length > 2) {
        if (parts.length === 3 && parts[1] === 'com' && parts[2] === 'br') {
            return 'CNAME (ou A)';
        }
        return 'CNAME';
    }
    return 'CNAME (ou A)';
});

const customDomainPreviewUrl = computed(() => {
    if (!cleanCustomDomain.value) return null;
    const scheme = window.location.protocol.replace(':', '');
    return `${scheme}://${cleanCustomDomain.value}`;
});

const copiedKey = ref(null);
const copyToClipboard = (text, key) => {
    navigator.clipboard.writeText(text);
    copiedKey.value = key;
    setTimeout(() => {
        copiedKey.value = null;
    }, 2000);
};

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
                    <i class="fa-solid fa-shield-halved text-xs"></i>
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
                                    placeholder="ex: agenda.meusite.com.br ou agendamentos.salao.com"
                                >
                                <span class="text-[11px] opacity-70 mt-1 block">Sem http:// ou https:// (Ex: agenda.meusite.com.br)</span>
                                <p v-if="form.errors.custom_domain" class="text-rose-500 text-xs font-medium mt-1">{{ form.errors.custom_domain }}</p>
                            </div>

                            <!-- Dynamic DNS Record Setup Box -->
                            <div class="p-4 bg-slate-100 dark:bg-slate-900/90 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2.5 text-xs shadow-xs">
                                <div class="flex items-center justify-between border-b border-slate-200/70 dark:border-slate-800 pb-2">
                                    <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-slate-100">
                                        <i class="fa-solid fa-server text-cyan-500"></i>
                                        <span>Apontamento DNS no seu Provedor</span>
                                    </div>
                                    <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                                        Registro.br / Cloudflare / cPanel
                                    </span>
                                </div>

                                <div class="space-y-2 font-mono text-[11px]">
                                    <!-- Record Type -->
                                    <div class="flex items-center justify-between p-2 rounded-xl bg-white dark:bg-slate-950/80 border border-slate-200/60 dark:border-slate-800/60">
                                        <div>
                                            <span class="text-slate-400 text-[10px] font-sans font-bold block">TIPO</span>
                                            <span class="font-black text-indigo-600 dark:text-cyan-400">{{ dnsType }}</span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="copyToClipboard('CNAME', 'type')"
                                            class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-800 hover:border-indigo-500 text-[10px] font-bold font-sans text-slate-600 dark:text-slate-300 transition-all cursor-pointer flex items-center gap-1"
                                            title="Copiar Tipo"
                                        >
                                            <i :class="copiedKey === 'type' ? 'fa-solid fa-check text-emerald-500' : 'fa-regular fa-copy'"></i>
                                            <span>{{ copiedKey === 'type' ? 'Copiado' : 'Copiar' }}</span>
                                        </button>
                                    </div>

                                    <!-- Record Host -->
                                    <div class="flex items-center justify-between p-2 rounded-xl bg-white dark:bg-slate-950/80 border border-slate-200/60 dark:border-slate-800/60">
                                        <div class="min-w-0">
                                            <span class="text-slate-400 text-[10px] font-sans font-bold block">NOME / HOST</span>
                                            <span class="font-black text-slate-900 dark:text-white truncate block">{{ dnsHost }}</span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="copyToClipboard(dnsHost, 'host')"
                                            class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-800 hover:border-indigo-500 text-[10px] font-bold font-sans text-slate-600 dark:text-slate-300 transition-all cursor-pointer flex items-center gap-1 shrink-0 ml-2"
                                            title="Copiar Host"
                                        >
                                            <i :class="copiedKey === 'host' ? 'fa-solid fa-check text-emerald-500' : 'fa-regular fa-copy'"></i>
                                            <span>{{ copiedKey === 'host' ? 'Copiado' : 'Copiar' }}</span>
                                        </button>
                                    </div>

                                    <!-- Record Target -->
                                    <div class="flex items-center justify-between p-2 rounded-xl bg-white dark:bg-slate-950/80 border border-slate-200/60 dark:border-slate-800/60">
                                        <div class="min-w-0">
                                            <span class="text-slate-400 text-[10px] font-sans font-bold block">DESTINO / VALOR</span>
                                            <span class="font-black text-emerald-600 dark:text-emerald-400 truncate block">{{ cnameTarget }}</span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="copyToClipboard(cnameTarget, 'target')"
                                            class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-800 hover:border-indigo-500 text-[10px] font-bold font-sans text-slate-600 dark:text-slate-300 transition-all cursor-pointer flex items-center gap-1 shrink-0 ml-2"
                                            title="Copiar Destino"
                                        >
                                            <i :class="copiedKey === 'target' ? 'fa-solid fa-check text-emerald-500' : 'fa-regular fa-copy'"></i>
                                            <span>{{ copiedKey === 'target' ? 'Copiado' : 'Copiar' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Custom Domain Preview -->
                            <div v-if="customDomainPreviewUrl">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Link do Seu Site</label>
                                <div class="p-3 bg-slate-100 dark:bg-slate-900/80 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <i class="fa-solid fa-globe text-cyan-500 text-sm shrink-0"></i>
                                        <span class="text-xs font-mono font-bold text-cyan-600 dark:text-cyan-400 truncate">{{ customDomainPreviewUrl }}</span>
                                    </div>
                                    <a :href="customDomainPreviewUrl" target="_blank" class="text-xs font-bold text-slate-500 hover:text-cyan-600 dark:hover:text-cyan-400 p-1 shrink-0" title="Testar Domínio">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
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
                        class="inline-flex items-center gap-2 py-3 px-6 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-indigo-600 via-indigo-500 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 shadow-lg shadow-indigo-600/30 hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                    >
                        <i v-if="!form.processing" class="fa-solid fa-floppy-disk text-xs"></i>
                        <i v-else class="fa-solid fa-spinner fa-spin text-xs"></i>
                        <span>Salvar Escolha de Domínio</span>
                    </button>
                </div>
            </form>

            <!-- Practical Guide / How it works Card -->
            <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white/90 dark:bg-slate-900/90 p-6 sm:p-8 space-y-6 shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-cyan-400 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-circle-question"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base sm:text-lg text-slate-900 dark:text-white">Como Funciona na Prática?</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Entenda o passo a passo para colocar seu domínio próprio no ar com segurança.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800/60 space-y-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-black text-xs flex items-center justify-center">1</span>
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm">Defina o Endereço</h4>
                        <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                            Digite acima o domínio ou subdomínio que deseja usar para seus clientes agendarem (ex: <code class="text-indigo-600 dark:text-cyan-400 font-mono">agenda.meusite.com.br</code>).
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800/60 space-y-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-black text-xs flex items-center justify-center">2</span>
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm">Crie o Apontamento DNS</h4>
                        <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                            No painel onde registrou seu domínio (Registro.br, Cloudflare, GoDaddy, Hostinger, cPanel, etc.), adicione um registro <strong>CNAME</strong> com o <strong>Host</strong> e <strong>Destino</strong> copiados acima.
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800/60 space-y-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-black text-xs flex items-center justify-center">3</span>
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm">Pronto e Conectado!</h4>
                        <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                            Após a propagação do DNS (geralmente de 5 a 30 minutos), qualquer cliente que acessar o seu link verá seu catálogo com SSL e identidade visual exclusiva.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
