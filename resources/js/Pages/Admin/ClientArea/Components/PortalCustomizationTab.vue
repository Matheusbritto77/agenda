<script setup>
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

            <!-- RIGHT COLUMN: LIVE PREVIEW -->
            <div class="lg:col-span-5 space-y-4">
                <div class="sticky top-20 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider opacity-60 flex items-center gap-1.5">
                            <i class="fa-solid fa-eye text-cyan-500"></i>
                            Pré-visualização em Tempo Real
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            Ao Vivo
                        </span>
                    </div>

                    <!-- Mockup Container -->
                    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 p-3 sm:p-4 shadow-2xl space-y-3 overflow-hidden">
                        <!-- Mock Header -->
                        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 flex items-center justify-between shadow-xs">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-8 h-8 rounded-xl overflow-hidden border flex items-center justify-center shrink-0" :style="{ backgroundColor: customForm.portal_primary_color }">
                                    <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                                    <i v-else class="fa-solid fa-store text-white text-xs"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-black truncate">{{ portalCustomization.company_name }}</p>
                                    <span class="text-[9px] font-bold text-slate-400 block">Área do Cliente</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-cyan-400">
                                Cliente VIP
                            </span>
                        </div>

                        <!-- Mock Announcement -->
                        <div
                            v-if="customForm.portal_announcement_enabled && customForm.portal_announcement"
                            class="p-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300 text-[11px] font-bold flex items-center gap-2"
                        >
                            <i class="fa-solid fa-bullhorn text-xs shrink-0"></i>
                            <span class="truncate">{{ customForm.portal_announcement }}</span>
                        </div>

                        <!-- Mock Branded Hero Banner -->
                        <div
                            class="rounded-2xl p-4 text-white space-y-3 shadow-lg relative overflow-hidden"
                            :style="{
                                background: `linear-gradient(135deg, ${customForm.portal_primary_color} 0%, ${customForm.portal_secondary_color} 100%)`
                            }"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white text-slate-900 flex items-center justify-center font-black text-sm shrink-0 overflow-hidden shadow-xs">
                                    <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
                                    <i v-else class="fa-solid fa-store text-xs" :style="{ color: customForm.portal_primary_color }"></i>
                                </div>
                                <div class="min-w-0">
                                    <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase bg-black/20 text-white/90">Espaço Ativo</span>
                                    <h4 class="font-black text-sm truncate">{{ customForm.portal_welcome_title || portalCustomization.company_name }}</h4>
                                    <p class="text-[10px] text-white/80 truncate">{{ customForm.portal_welcome_subtitle || 'Acompanhe seus horários e conquistas.' }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-white text-slate-900 shadow-2xs flex items-center gap-1">
                                    <i class="fa-solid fa-calendar-plus text-[9px]" :style="{ color: customForm.portal_primary_color }"></i>
                                    Agendar
                                </span>
                                <span v-if="customForm.portal_support_whatsapp" class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-600 text-white shadow-2xs flex items-center gap-1">
                                    <i class="fa-brands fa-whatsapp text-[10px]"></i>
                                    Suporte
                                </span>
                                <span v-if="customForm.portal_show_reviews" class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-black/25 text-amber-300 flex items-center gap-1">
                                    <i class="fa-solid fa-star text-[9px]"></i>
                                    Avaliar
                                </span>
                            </div>

                            <p v-if="customForm.portal_custom_instructions" class="text-[10px] text-white/80 border-t border-white/15 pt-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-info text-[9px]"></i>
                                <span class="truncate">{{ customForm.portal_custom_instructions }}</span>
                            </p>
                        </div>

                        <!-- Mock Loyalty Badge Card -->
                        <div v-if="customForm.portal_show_loyalty_badges" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-trophy"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold">Cliente VIP Ouro</p>
                                    <span class="text-[9px] text-slate-400">5 de 5 atendimentos concluídos</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-600">Conquistada</span>
                        </div>

                        <!-- Mock Appointment Item -->
                        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 space-y-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-xs font-extrabold">Corte de Cabelo & Barba</p>
                                    <span v-if="customForm.portal_show_professionals" class="text-[10px] text-slate-400 block">Profissional: Carlos Eduardo</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-500/15 text-emerald-600">Confirmado</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] pt-1 border-t border-slate-100 dark:border-slate-800 text-slate-500">
                                <span>📅 25/08 às 14:00</span>
                                <strong v-if="customForm.portal_show_service_prices" class="text-slate-900 dark:text-white font-black">R$ 80,00</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
