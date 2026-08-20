<script setup>
defineProps({
    professionals: {
        type: Array,
        default: () => [],
    },
    chosenProfessionalId: {
        type: [Number, String],
        default: null,
    },
    searchQuery: {
        type: String,
        default: '',
    },
});

defineEmits(['select-professional', 'update:searchQuery']);

const getInitials = (name) => (name || 'A').substring(0, 2).toUpperCase();
</script>

<template>
    <div class="space-y-4">
        <!-- Search filter -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </div>
            <input
                type="text"
                :value="searchQuery"
                @input="$emit('update:searchQuery', $event.target.value)"
                class="form-control pl-9 text-xs sm:text-sm rounded-2xl"
                placeholder="Buscar profissional por nome ou especialidade..."
            />
        </div>

        <div v-if="professionals.length === 0" class="card text-center py-12 px-4 text-slate-400">
            <i class="fa-solid fa-user-slash text-2xl mb-2"></i>
            <p class="text-xs font-bold">Nenhum profissional encontrado.</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            <div
                v-for="pro in professionals"
                :key="pro.id"
                @click="$emit('select-professional', pro)"
                :class="[
                    'card p-4 rounded-2xl border flex items-center gap-3.5 cursor-pointer transition-all hover:scale-102 shadow-sm',
                    chosenProfessionalId === pro.id ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/30 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800'
                ]"
                :style="chosenProfessionalId === pro.id ? { borderColor: 'var(--primary)' } : {}"
            >
                <div class="w-12 h-12 rounded-2xl overflow-hidden bg-gradient-to-tr from-brand-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md">
                    <img v-if="pro.avatar_url" :src="pro.avatar_url" :alt="pro.name" class="w-full h-full object-cover" />
                    <span v-else>{{ getInitials(pro.name) }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="font-extrabold text-sm truncate" style="color: var(--text-heading);">{{ pro.name }}</h4>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold truncate">{{ pro.job_title || 'Especialista' }}</p>
                </div>
                <i :class="['fa-solid text-sm', chosenProfessionalId === pro.id ? 'fa-circle-check text-indigo-600 dark:text-indigo-400' : 'fa-circle text-slate-300 dark:text-slate-700']"></i>
            </div>
        </div>
    </div>
</template>
