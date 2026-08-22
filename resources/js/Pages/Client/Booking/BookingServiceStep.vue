<script setup>
defineProps({
    services: {
        type: Array,
        default: () => [],
    },
    selectedServiceId: {
        type: [Number, String],
        default: null,
    },
    searchQuery: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: 'Escolha o Serviço',
    },
    subtitle: {
        type: String,
        default: 'Selecione os procedimentos desejados',
    },
    searchEnabled: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['select-service', 'update:searchQuery']);

const formatCurrency = (val) => Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

        <!-- Search filter (if enabled) -->
        <div v-if="searchEnabled" class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none opacity-50" :style="{ color: 'var(--text-muted, #64748b)' }">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </div>
            <input
                type="text"
                :value="searchQuery"
                @input="$emit('update:searchQuery', $event.target.value)"
                class="form-control form-control-search text-xs sm:text-sm rounded-2xl"
                style="padding-left: 2.75rem !important;"
                placeholder="Buscar serviço por nome ou descrição..."
            />
        </div>

        <div v-if="services.length === 0" class="card text-center py-12 px-4 opacity-70">
            <i class="fa-solid fa-scissors text-2xl mb-2 block" :style="{ color: 'var(--primary)' }"></i>
            <p class="text-xs font-bold">Nenhum serviço disponível no momento.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <div
                v-for="svc in services"
                :key="svc.id"
                @click="$emit('select-service', svc)"
                :class="[
                    'card p-4 flex items-center justify-between gap-3 cursor-pointer transition-all hover:scale-102 shadow-sm',
                    selectedServiceId === svc.id ? 'border-2' : ''
                ]"
                :style="selectedServiceId === svc.id ? {
                    borderColor: 'var(--primary)',
                    backgroundColor: 'var(--primary-light)',
                    boxShadow: '0 0 0 2px var(--primary-light)'
                } : {}"
            >
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-12 h-12 rounded-2xl overflow-hidden flex items-center justify-center shrink-0" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
                        <img v-if="svc.image_url" :src="svc.image_url" :alt="svc.name" class="w-full h-full object-cover" />
                        <i v-else class="fa-solid fa-scissors text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-extrabold text-sm truncate" :style="{ color: 'var(--text-heading)' }">{{ svc.name }}</h4>
                        <p v-if="svc.description" class="text-xs opacity-75 line-clamp-1 mt-0.5" :style="{ color: 'var(--text-muted)' }">{{ svc.description }}</p>
                        <span class="inline-flex items-center gap-1 text-[11px] opacity-70 mt-1" :style="{ color: 'var(--text-muted)' }">
                            <i class="fa-regular fa-clock text-[10px]"></i>
                            {{ svc.duration_minutes || 30 }} min
                        </span>
                    </div>
                </div>

                <div class="text-right shrink-0">
                    <span class="text-sm font-black block" :style="{ color: 'var(--primary)' }">
                        R$ {{ formatCurrency(svc.price) }}
                    </span>
                    <i :class="['fa-solid text-xs mt-1 block', selectedServiceId === svc.id ? 'fa-circle-check' : 'fa-circle opacity-30']" :style="selectedServiceId === svc.id ? { color: 'var(--primary)' } : {}"></i>
                </div>
            </div>
        </div>
    </div>
</template>
