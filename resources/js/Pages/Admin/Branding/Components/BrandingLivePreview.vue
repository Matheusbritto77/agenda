<script setup>
defineProps({
    form: {
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
});
</script>

<template>
    <div class="space-y-3 sticky top-24">
        <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                <i class="fa-solid fa-mobile-screen mr-1.5 text-indigo-500"></i>
                Pré-visualização da Página
            </h4>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 font-bold border border-emerald-500/20">
                Tempo Real
            </span>
        </div>

        <!-- Simulated Public Page Screen -->
        <div
            class="border rounded-3xl overflow-hidden shadow-2xl transition-all duration-300 min-h-[560px] flex flex-col relative"
            :style="{
                backgroundColor: form.background_color,
                color: form.text_color
            }"
        >
            <!-- Simulated Header -->
            <header
                class="h-16 border-b flex items-center justify-between px-4 transition-all duration-300 shadow-sm shrink-0"
                :style="{
                    backgroundColor: form.top_menu_color,
                    borderColor: 'rgba(0,0,0,0.06)'
                }"
            >
                <!-- Logo Area in Mockup -->
                <div class="flex items-center gap-2">
                    <template v-if="logoPreview">
                        <div class="h-9 max-w-[130px] flex items-center">
                            <img :src="logoPreview" class="h-full w-auto object-contain" alt="Logo preview" />
                        </div>
                        <span v-if="form.business_name" class="font-extrabold text-xs tracking-tight truncate max-w-[100px]" :style="{ color: form.text_color }">
                            {{ form.business_name }}
                        </span>
                    </template>
                    <template v-else>
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-500 flex items-center justify-center text-white text-xs font-bold shadow-md">
                            <i class="fa-solid fa-calendar-check text-xs"></i>
                        </div>
                        <span class="font-black text-sm tracking-tight bg-gradient-to-r from-indigo-600 to-cyan-600 bg-clip-text text-transparent">Agendae</span>
                    </template>
                </div>

                <!-- Header actions in Mockup -->
                <div class="flex items-center gap-1.5">
                    <div v-if="form.instagram_handle" class="w-7 h-7 rounded-lg bg-white/80 border border-slate-200/80 flex items-center justify-center text-pink-500 shadow-xs">
                        <i class="fa-brands fa-instagram text-[11px]"></i>
                    </div>
                    <div v-if="form.whatsapp_number" class="w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shadow-xs">
                        <i class="fa-brands fa-whatsapp text-[11px]"></i>
                    </div>
                </div>
            </header>

            <!-- Simulated Body Content -->
            <div class="flex-1 p-4 space-y-3.5 overflow-y-auto">
                
                <!-- Cover Banner in Mockup -->
                <div
                    v-if="bannerPreview"
                    class="h-24 w-full rounded-2xl overflow-hidden shadow-sm relative border border-slate-200/40"
                >
                    <img :src="bannerPreview" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent flex items-end p-2.5 text-white">
                        <p class="font-extrabold text-xs drop-shadow">{{ form.business_name || 'Agendamento Online' }}</p>
                    </div>
                </div>

                <!-- Title / Hero if no banner -->
                <div
                    v-else
                    class="p-3.5 rounded-2xl border border-slate-200/50 shadow-xs space-y-1"
                    :style="{ backgroundColor: form.card_bg_color }"
                >
                    <h5 class="font-extrabold text-xs" :style="{ color: form.text_color }">
                        {{ form.business_name || 'Agendamento Online' }}
                    </h5>
                    <p class="text-[10px] opacity-75 leading-tight" :style="{ color: form.text_color }">
                        {{ form.tagline || 'Selecione o serviço e horário de sua preferência.' }}
                    </p>
                </div>

                <!-- Stepper Mockup -->
                <div
                    class="p-3.5 rounded-2xl border border-slate-200/60 shadow-xs space-y-2"
                    :style="{
                        backgroundColor: form.card_bg_color,
                        borderColor: form.primary_color + '35'
                    }"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[9px] font-black uppercase tracking-wider opacity-65" :style="{ color: form.text_color }">
                            Perfil da empresa
                        </span>
                        <span class="text-[8px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 font-bold">
                            Aberto agora
                        </span>
                    </div>
                    <p class="text-xs font-black leading-tight" :style="{ color: form.text_color }">
                        {{ form.business_name || 'Agendamento Online' }}
                    </p>
                    <p class="text-[10px] opacity-70 leading-tight line-clamp-2" :style="{ color: form.text_color }">
                        {{ form.company_profile_description || form.tagline || 'Confira os servicos, horarios e profissionais disponiveis antes de agendar.' }}
                    </p>
                    <div class="flex items-center gap-1.5 pt-1">
                        <span v-if="form.company_profile_show_hours" class="text-[8px] px-1.5 py-1 rounded-lg bg-slate-100 text-slate-600 font-bold">Horarios</span>
                        <span v-if="form.company_profile_show_services" class="text-[8px] px-1.5 py-1 rounded-lg bg-slate-100 text-slate-600 font-bold">Servicos</span>
                        <span v-if="form.company_profile_show_professionals" class="text-[8px] px-1.5 py-1 rounded-lg bg-slate-100 text-slate-600 font-bold">Equipe</span>
                    </div>
                    <div
                        class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-xl font-bold text-[10px] text-white shadow-md"
                        :style="{ backgroundColor: form.primary_color }"
                    >
                        <span>{{ form.company_profile_cta_label || 'Agendar agora' }}</span>
                        <i class="fa-solid fa-arrow-right text-[8px]"></i>
                    </div>
                </div>

                <!-- Stepper Mockup -->
                <div class="grid grid-cols-3 gap-1.5">
                    <div
                        class="h-9 rounded-xl border border-slate-200/50 flex items-center justify-center gap-1 shadow-xs p-1"
                        :style="{ backgroundColor: form.card_bg_color }"
                    >
                        <div class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-bold text-white shadow-xs" :style="{ backgroundColor: form.primary_color }">1</div>
                        <span class="text-[9px] font-bold truncate" :style="{ color: form.text_color }">Serviço</span>
                    </div>
                    <div
                        class="h-9 rounded-xl border border-slate-200/50 flex items-center justify-center gap-1 opacity-70 p-1"
                        :style="{ backgroundColor: form.card_bg_color }"
                    >
                        <div class="w-4 h-4 rounded-full bg-slate-200 flex items-center justify-center text-[8px] font-bold text-slate-500">2</div>
                        <span class="text-[9px] font-medium truncate" :style="{ color: form.text_color }">Horário</span>
                    </div>
                    <div
                        class="h-9 rounded-xl border border-slate-200/50 flex items-center justify-center gap-1 opacity-70 p-1"
                        :style="{ backgroundColor: form.card_bg_color }"
                    >
                        <div class="w-4 h-4 rounded-full bg-slate-200 flex items-center justify-center text-[8px] font-bold text-slate-500">3</div>
                        <span class="text-[9px] font-medium truncate" :style="{ color: form.text_color }">Confirmar</span>
                    </div>
                </div>

                <!-- Service Card Example Mockup -->
                <div
                    class="p-3 rounded-2xl border border-slate-200/60 shadow-xs flex items-center justify-between gap-2"
                    :style="{
                        backgroundColor: form.card_bg_color,
                        borderColor: form.primary_color + '40'
                    }"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-xs shadow-xs" :style="{ backgroundColor: form.primary_color }">
                            <i class="fa-solid fa-scissors"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold" :style="{ color: form.text_color }">Corte & Barba Especial</p>
                            <span class="text-[10px] opacity-70"><i class="fa-regular fa-clock mr-1"></i>45 min</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-black" :style="{ color: form.primary_color }">R$ 75,00</span>
                    </div>
                </div>

                <!-- Next Button Mockup -->
                <div class="pt-2 flex justify-end">
                    <div
                        class="py-2 px-5 rounded-xl font-bold text-xs text-white shadow-md flex items-center gap-1.5"
                        :style="{ backgroundColor: form.primary_color }"
                    >
                        <span>Continuar</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </div>
                </div>
            </div>

            <!-- Floating WhatsApp in Mockup -->
            <div
                v-if="form.whatsapp_number && form.whatsapp_button_enabled"
                class="absolute bottom-12 right-3 w-9 h-9 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow-lg shadow-emerald-500/40"
            >
                <i class="fa-brands fa-whatsapp text-base"></i>
            </div>

            <!-- Footer in Mockup -->
            <footer class="h-9 border-t flex items-center justify-center text-[8px] opacity-70 px-2 shrink-0" :style="{ borderColor: 'rgba(0,0,0,0.06)' }">
                <span class="truncate">{{ form.footer_text || ('© ' + new Date().getFullYear() + ' ' + (form.business_name || 'Agendae') + '. Todos os direitos reservados.') }}</span>
            </footer>
        </div>
    </div>
</template>
