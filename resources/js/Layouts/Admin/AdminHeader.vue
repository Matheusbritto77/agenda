<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    isDarkMode: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['toggle-sidebar', 'toggle-theme', 'open-booking-modal']);

const page = usePage();

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    const userPerms = page.props.auth?.permissions || [];
    return userPerms.includes(permission);
};

const todayDate = computed(() => {
    const now = new Date();
    return now.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
});

const todayTime = computed(() => {
    const now = new Date();
    return now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
});
</script>

<template>
    <header class="h-20 border-b backdrop-blur-md sticky top-0 z-30 px-4 sm:px-8 flex items-center justify-between gap-4 transition-colors duration-300" style="background-color: var(--header-bg); border-color: var(--border);">
        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
            <button type="button" @click="$emit('toggle-sidebar')" class="md:hidden p-2 rounded-xl opacity-70 hover:opacity-100 transition-opacity shrink-0" aria-label="Abrir menu lateral">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
            <div class="min-w-0">
                <slot name="header">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Painel Administrativo</h1>
                    </div>
                    <p class="text-xs opacity-60 hidden sm:block truncate">Gestão simplificada de agendamentos e horários</p>
                </slot>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            <button
                type="button"
                @click="$emit('toggle-theme')"
                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border flex items-center justify-center cursor-pointer transition-all hover:scale-105"
                style="border-color: var(--border); background-color: var(--surface);"
                title="Alternar Modo Claro / Escuro"
                aria-label="Alternar Tema"
            >
                <i v-if="isDarkMode" class="fa-solid fa-sun text-amber-400 text-sm"></i>
                <i v-else class="fa-solid fa-moon text-indigo-500 text-sm"></i>
            </button>

            <button
                v-if="hasPermission('appointments.create')"
                type="button"
                @click="$emit('open-booking-modal')"
                class="inline-flex items-center gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-all shadow-md shadow-indigo-600/25 hover:shadow-indigo-600/40 cursor-pointer"
            >
                <i class="fa-solid fa-plus text-xs"></i>
                <span class="hidden sm:inline">+ Agendamento Manual</span>
                <span class="sm:hidden">+ Manual</span>
            </button>

            <div class="h-8 w-px opacity-20 bg-slate-400 dark:bg-slate-700 hidden md:block"></div>

            <div class="text-right hidden md:block">
                <span class="text-xs opacity-60 block" style="color: var(--text);">{{ todayDate }}</span>
                <span class="text-xs font-semibold" style="color: var(--text);"><i class="fa-regular fa-clock mr-1 text-indigo-600 dark:text-indigo-400"></i>{{ todayTime }}</span>
            </div>
        </div>
    </header>
</template>
