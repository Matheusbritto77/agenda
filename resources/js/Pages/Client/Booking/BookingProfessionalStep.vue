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
    title: {
        type: String,
        default: 'Escolha o Profissional',
    },
    subtitle: {
        type: String,
        default: 'Selecione quem irá lhe atender',
    },
    allowAny: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['select-professional', 'update:searchQuery']);

const getInitials = (name) => (name || 'A').substring(0, 2).toUpperCase();
</script>

<template>
    <div class="space-y-4">
        <!-- Step Header -->
        <div class="space-y-1">
            <h3 class="text-base sm:text-lg font-black" :style="{ color: 'var(--text-heading, #0f172a)' }">
                {{ title }}
            </h3>
            <p v-if="subtitle" class="text-xs opacity-75" :style="{ color: 'var(--text-muted, #64748b)' }">
                {{ subtitle }}
            </p>
        </div>

        <!-- Search filter -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none opacity-50" :style="{ color: 'var(--text-muted, #64748b)' }">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </div>
            <input
                type="text"
                :value="searchQuery"
                @input="$emit('update:searchQuery', $event.target.value)"
                class="form-control form-control-search text-xs sm:text-sm rounded-2xl"
                style="padding-left: 2.75rem !important;"
                placeholder="Buscar profissional por nome ou especialidade..."
            />
        </div>

        <div v-if="professionals.length === 0" class="card text-center py-12 px-4 opacity-70">
            <i class="fa-solid fa-user-slash text-2xl mb-2 block"></i>
            <p class="text-xs font-bold">Nenhum profissional encontrado.</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            <!-- Qualquer Profissional Option -->
            <div
                v-if="allowAny"
                @click="$emit('select-professional', null)"
                :class="[
                    'card p-4 flex items-center gap-3.5 cursor-pointer transition-all hover:scale-102 shadow-sm',
                    chosenProfessionalId === null ? 'border-2' : ''
                ]"
                :style="chosenProfessionalId === null ? {
                    borderColor: 'var(--primary)',
                    backgroundColor: 'var(--primary-light)',
                    boxShadow: '0 0 0 2px var(--primary-light)'
                } : {}"
            >
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-bold text-base shrink-0 shadow-sm" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
                    <i class="fa-solid fa-user-group"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="font-extrabold text-sm truncate" :style="{ color: 'var(--text-heading)' }">Qualquer Profissional</h4>
                    <p class="text-xs opacity-75 truncate" :style="{ color: 'var(--text-muted)' }">Primeiro horário disponível</p>
                </div>
                <i :class="['fa-solid text-sm', chosenProfessionalId === null ? 'fa-circle-check' : 'fa-circle opacity-30']" :style="chosenProfessionalId === null ? { color: 'var(--primary)' } : {}"></i>
            </div>

            <!-- Professional Cards -->
            <div
                v-for="pro in professionals"
                :key="pro.id"
                @click="$emit('select-professional', pro)"
                :class="[
                    'card p-4 flex items-center gap-3.5 cursor-pointer transition-all hover:scale-102 shadow-sm',
                    chosenProfessionalId === pro.id ? 'border-2' : ''
                ]"
                :style="chosenProfessionalId === pro.id ? {
                    borderColor: 'var(--primary)',
                    backgroundColor: 'var(--primary-light)',
                    boxShadow: '0 0 0 2px var(--primary-light)'
                } : {}"
            >
                <div class="w-12 h-12 rounded-2xl overflow-hidden flex items-center justify-center font-bold text-sm shrink-0 shadow-md" :style="{ background: 'var(--primary-gradient)', color: 'var(--btn-text, #ffffff)' }">
                    <img v-if="pro.avatar_url" :src="pro.avatar_url" :alt="pro.name" class="w-full h-full object-cover" />
                    <span v-else>{{ getInitials(pro.name) }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="font-extrabold text-sm truncate" :style="{ color: 'var(--text-heading)' }">{{ pro.name }}</h4>
                    <p class="text-xs font-semibold truncate" :style="{ color: 'var(--primary)' }">{{ pro.job_title || 'Especialista' }}</p>
                </div>
                <i :class="['fa-solid text-sm', chosenProfessionalId === pro.id ? 'fa-circle-check' : 'fa-circle opacity-30']" :style="chosenProfessionalId === pro.id ? { color: 'var(--primary)' } : {}"></i>
            </div>
        </div>
    </div>
</template>
