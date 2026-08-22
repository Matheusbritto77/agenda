<script setup>
defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-5">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/50 p-4 space-y-1">
            <div class="flex items-center gap-2 text-slate-900 dark:text-white">
                <i class="fa-solid fa-building-user text-indigo-500"></i>
                <h2 class="text-sm font-black">Etapa do perfil da empresa</h2>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">
                Esses campos aparecem antes do cliente escolher profissional ou serviço quando o link publico for da empresa.
            </p>
        </div>

        <div class="form-group mb-0">
            <label class="form-label text-xs font-bold block" for="company_profile_description">Sobre a empresa</label>
            <textarea
                id="company_profile_description"
                v-model="form.company_profile_description"
                rows="5"
                class="form-control text-xs sm:text-sm rounded-xl"
                placeholder="Ex: Somos um espaco especializado em atendimento masculino, com profissionais experientes e horario marcado."
            ></textarea>
            <div class="flex items-center justify-between gap-3 mt-1">
                <p class="text-[11px] text-slate-400">Texto exibido abaixo do nome da empresa na capa do perfil.</p>
                <span class="text-[10px] text-slate-400 shrink-0">
                    {{ (form.company_profile_description || '').length }}/1200
                </span>
            </div>
        </div>

        <div class="form-group mb-0">
            <label class="form-label text-xs font-bold block" for="company_profile_cta_label">
                <i class="fa-solid fa-calendar-check text-indigo-500 mr-1.5"></i>
                Texto do botão principal
            </label>
            <input
                type="text"
                id="company_profile_cta_label"
                v-model="form.company_profile_cta_label"
                maxlength="40"
                class="form-control text-xs sm:text-sm rounded-xl"
                placeholder="Agendar agora"
            />
            <p class="text-[11px] text-slate-400 mt-1">Use um comando curto, por exemplo: Agendar agora, Ver horários ou Começar agendamento.</p>
        </div>

        <div class="space-y-3">
            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Blocos exibidos nessa etapa</p>

            <label class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-between gap-3 cursor-pointer">
                <span>
                    <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Resumo de horarios</span>
                    <span class="block text-[11px] text-slate-400">Mostra aberto/fechado agora e a semana de funcionamento.</span>
                </span>
                <input
                    type="checkbox"
                    v-model="form.company_profile_show_hours"
                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
            </label>

            <label class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-between gap-3 cursor-pointer">
                <span>
                    <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Servicos em destaque</span>
                    <span class="block text-[11px] text-slate-400">Lista os primeiros servicos ativos antes de iniciar o agendamento.</span>
                </span>
                <input
                    type="checkbox"
                    v-model="form.company_profile_show_services"
                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
            </label>

            <label class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-between gap-3 cursor-pointer">
                <span>
                    <span class="block text-xs font-bold text-slate-800 dark:text-slate-200">Resumo de profissionais</span>
                    <span class="block text-[11px] text-slate-400">Mostra se o cliente vai escolher profissional na proxima etapa.</span>
                </span>
                <input
                    type="checkbox"
                    v-model="form.company_profile_show_professionals"
                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                />
            </label>
        </div>
    </div>
</template>
