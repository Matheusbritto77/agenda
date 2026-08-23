<script setup>
import PortalCustomizationPreview from './PortalCustomizationPreview.vue';

defineProps({
    portalCustomization: {
        type: Object,
        default: () => ({}),
    },
    customForm: {
        type: Object,
        required: true,
    },
    logoPreview: {
        type: String,
        default: null,
    },
    bannerPreview: {
        type: String,
        default: null,
    },
    colorPresets: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['apply-color-preset', 'handle-logo-upload', 'handle-banner-upload', 'save-customization']);
</script>

<template>
    <section class="space-y-6">
        <div class="rounded-3xl border border-indigo-500/25 bg-gradient-to-r from-indigo-900/30 via-slate-900/40 to-slate-950/60 p-5 sm:p-6 text-white flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-xl">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-500/20 text-cyan-300 border border-indigo-500/30">
                        Personalização do Estabelecimento
                    </span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white">Espaço da Empresa na Área do Cliente</h2>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl">
                    Personalize títulos, cores, capa, comunicado, regras e recursos para quando seus clientes acessarem a área exclusiva da sua empresa.
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a
                    :href="portalCustomization.portal_url || '/cliente'"
                    target="_blank"
                    class="btn btn-outline !text-white !border-white/20 hover:!bg-white/10 rounded-xl text-xs flex items-center gap-2"
                >
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    <span>Ver Portal do Cliente</span>
                </a>
                <button
                    type="button"
                    @click="$emit('save-customization')"
                    :disabled="customForm.processing"
                    class="btn btn-primary rounded-xl text-xs flex items-center gap-2 shadow-lg shadow-indigo-600/30"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>{{ customForm.processing ? 'Salvando...' : 'Salvar Personalização' }}</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- LEFT COLUMN: EDIT FORM -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Card 1: Visual & Cores -->
                <div class="glass-card-3d rounded-3xl p-5 sm:p-6 space-y-5">
                    <div class="flex items-center gap-3 border-b pb-3" style="border-color: var(--border);">
                        <div class="w-9 h-9 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-cyan-400 flex items-center justify-center text-base shrink-0">
                            <i class="fa-solid fa-palette"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base" style="color: var(--text-heading);">Identidade Visual & Cores</h3>
                            <p class="text-xs opacity-60">Defina a paleta e as imagens exibidas aos seus clientes no portal.</p>
                        </div>
                    </div>

                    <!-- Color Presets -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-wider opacity-70 block">Paletas Sugeridas</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="preset in colorPresets"
                                :key="preset.label"
                                type="button"
                                @click="$emit('apply-color-preset', preset)"
                                class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-indigo-500 flex items-center gap-2 text-xs font-semibold transition-all cursor-pointer bg-white/50 dark:bg-slate-900/50"
                                :class="customForm.portal_primary_color === preset.primary ? 'ring-2 ring-indigo-500' : ''"
                            >
                                <span class="w-3.5 h-3.5 rounded-full" :style="{ backgroundColor: preset.primary }"></span>
                                <span class="w-3.5 h-3.5 rounded-full -ml-1" :style="{ backgroundColor: preset.secondary }"></span>
                                <span>{{ preset.label }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="form-label text-xs">Cor Primária do Portal</label>
                            <div class="flex items-center gap-2">
                                <input
                                    type="color"
                                    v-model="customForm.portal_primary_color"
                                    class="w-10 h-10 rounded-xl border border-slate-300 dark:border-slate-700 cursor-pointer p-1 bg-transparent"
                                />
                                <input
                                    type="text"
                                    v-model="customForm.portal_primary_color"
                                    class="form-control text-xs font-mono uppercase"
                                    placeholder="#6366f1"
                                />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="form-label text-xs">Cor Secundária / Gradiente</label>
                            <div class="flex items-center gap-2">
                                <input
                                    type="color"
                                    v-model="customForm.portal_secondary_color"
                                    class="w-10 h-10 rounded-xl border border-slate-300 dark:border-slate-700 cursor-pointer p-1 bg-transparent"
                                />
                                <input
                                    type="text"
                                    v-model="customForm.portal_secondary_color"
                                    class="form-control text-xs font-mono uppercase"
                                    placeholder="#06b6d4"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Uploads: Logo & Banner -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t" style="border-color: var(--border);">
                        <div class="space-y-2">
                            <label class="form-label text-xs">Logo no Portal</label>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border flex items-center justify-center shrink-0">
                                    <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                                    <i v-else class="fa-solid fa-store opacity-40"></i>
                                </div>
                                <label class="btn btn-outline !py-2 !px-3 rounded-xl text-xs cursor-pointer">
                                    <i class="fa-solid fa-upload mr-1.5"></i>Alterar Logo
                                    <input type="file" @change="$emit('handle-logo-upload', $event)" accept="image/*" class="hidden" />
                                </label>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="form-label text-xs">Capa / Banner do Espaço</label>
                            <div class="flex items-center gap-3">
                                <div class="w-16 h-12 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border flex items-center justify-center shrink-0">
                                    <img v-if="bannerPreview" :src="bannerPreview" class="w-full h-full object-cover" />
                                    <i v-else class="fa-solid fa-image opacity-40"></i>
                                </div>
                                <label class="btn btn-outline !py-2 !px-3 rounded-xl text-xs cursor-pointer">
                                    <i class="fa-solid fa-upload mr-1.5"></i>Alterar Capa
                                    <input type="file" @change="$emit('handle-banner-upload', $event)" accept="image/*" class="hidden" />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Textos & Comunicados -->
                <div class="glass-card-3d rounded-3xl p-5 sm:p-6 space-y-5">
                    <div class="flex items-center gap-3 border-b pb-3" style="border-color: var(--border);">
                        <div class="w-9 h-9 rounded-xl bg-purple-500/15 text-purple-600 dark:text-purple-400 flex items-center justify-center text-base shrink-0">
                            <i class="fa-solid fa-signature"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base" style="color: var(--text-heading);">Textos, Boas-Vindas & Comunicados</h3>
                            <p class="text-xs opacity-60">Personalize o título, slogan e mensagens de destaque exibidas aos clientes.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="form-label text-xs">Título de Boas-Vindas no Espaço</label>
                            <input
                                type="text"
                                v-model="customForm.portal_welcome_title"
                                class="form-control text-sm font-bold"
                                placeholder="Ex: Bem-vindo ao Espaço Exclusivo Barbearia Alfa"
                            />
                        </div>

                        <div>
                            <label class="form-label text-xs">Subtítulo / Mensagem aos Clientes</label>
                            <input
                                type="text"
                                v-model="customForm.portal_welcome_subtitle"
                                class="form-control text-xs"
                                placeholder="Ex: Acompanhe seus horários, histórico e acumule benefícios de fidelidade."
                            />
                        </div>

                        <!-- Announcement Bar Config -->
                        <div class="p-4 rounded-2xl border border-amber-500/30 bg-amber-500/5 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-bullhorn text-amber-500"></i>
                                    <span class="text-xs font-bold text-amber-700 dark:text-amber-300">Comunicado / Aviso em Destaque</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="customForm.portal_announcement_enabled" class="sr-only peer" />
                                    <div class="w-10 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-amber-500"></div>
                                </label>
                            </div>
                            <input
                                type="text"
                                v-model="customForm.portal_announcement"
                                class="form-control text-xs"
                                placeholder="Ex: 🎉 Neste mês de aniversário, clientes ganham corte de cortesia após 5 visitas!"
                                :disabled="!customForm.portal_announcement_enabled"
                            />
                        </div>
                    </div>
                </div>

                <!-- Card 3: Recursos, Módulos & Suporte -->
                <div class="glass-card-3d rounded-3xl p-5 sm:p-6 space-y-5">
                    <div class="flex items-center gap-3 border-b pb-3" style="border-color: var(--border);">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-base shrink-0">
                            <i class="fa-solid fa-sliders"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base" style="color: var(--text-heading);">Módulos, Regras & Contato</h3>
                            <p class="text-xs opacity-60">Habilite ou restrinja o que os clientes podem ver e interagir no portal.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center justify-between p-3 rounded-2xl border bg-white/50 dark:bg-slate-900/50 cursor-pointer" style="border-color: var(--border);">
                            <div class="space-y-0.5">
                                <span class="text-xs font-extrabold block">Medalhas de Fidelidade</span>
                                <span class="text-[11px] opacity-60">Exibir conquistas por visitas</span>
                            </div>
                            <input type="checkbox" v-model="customForm.portal_show_loyalty_badges" class="rounded text-indigo-600 focus:ring-indigo-500" />
                        </label>

                        <label class="flex items-center justify-between p-3 rounded-2xl border bg-white/50 dark:bg-slate-900/50 cursor-pointer" style="border-color: var(--border);">
                            <div class="space-y-0.5">
                                <span class="text-xs font-extrabold block">Avaliações & Feedbacks</span>
                                <span class="text-[11px] opacity-60">Permitir avaliar atendimentos</span>
                            </div>
                            <input type="checkbox" v-model="customForm.portal_show_reviews" class="rounded text-indigo-600 focus:ring-indigo-500" />
                        </label>

                        <label class="flex items-center justify-between p-3 rounded-2xl border bg-white/50 dark:bg-slate-900/50 cursor-pointer" style="border-color: var(--border);">
                            <div class="space-y-0.5">
                                <span class="text-xs font-extrabold block">Profissionais Atendentes</span>
                                <span class="text-[11px] opacity-60">Mostrar equipe que atendeu</span>
                            </div>
                            <input type="checkbox" v-model="customForm.portal_show_professionals" class="rounded text-indigo-600 focus:ring-indigo-500" />
                        </label>

                        <label class="flex items-center justify-between p-3 rounded-2xl border bg-white/50 dark:bg-slate-900/50 cursor-pointer" style="border-color: var(--border);">
                            <div class="space-y-0.5">
                                <span class="text-xs font-extrabold block">Preços & Gastos</span>
                                <span class="text-[11px] opacity-60">Mostrar valores no histórico</span>
                            </div>
                            <input type="checkbox" v-model="customForm.portal_show_service_prices" class="rounded text-indigo-600 focus:ring-indigo-500" />
                        </label>
                    </div>

                    <div class="space-y-3 pt-2 border-t" style="border-color: var(--border);">
                        <div>
                            <label class="form-label text-xs">WhatsApp de Suporte aos Clientes no Portal</label>
                            <div class="relative">
                                <i class="fa-brands fa-whatsapp absolute left-3.5 top-1/2 -translate-y-1/2 text-emerald-500"></i>
                                <input
                                    type="text"
                                    v-model="customForm.portal_support_whatsapp"
                                    class="form-control pl-9 text-xs"
                                    placeholder="(11) 98888-7777"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="form-label text-xs">Orientações, Regras & Políticas de Atendimento</label>
                            <textarea
                                v-model="customForm.portal_custom_instructions"
                                rows="2"
                                class="form-control text-xs"
                                placeholder="Ex: Solicitamos chegar com 10 minutos de antecedência. Cancelamentos devem ser feitos com no mínimo 2 horas de aviso."
                            ></textarea>
                        </div>
                    </div>

                    <div class="pt-3 border-t flex justify-end" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="$emit('save-customization')"
                            :disabled="customForm.processing"
                            class="btn btn-primary rounded-xl text-xs font-bold"
                        >
                            <i class="fa-solid fa-floppy-disk mr-1.5"></i>
                            {{ customForm.processing ? 'Salvando...' : 'Salvar Todas as Configurações' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: ENHANCED LIVE PREVIEW -->
            <div class="lg:col-span-5">
                <PortalCustomizationPreview
                    :custom-form="customForm"
                    :portal-customization="portalCustomization"
                    :logo-preview="logoPreview"
                    :banner-preview="bannerPreview"
                />
            </div>
        </div>
    </section>
</template>
