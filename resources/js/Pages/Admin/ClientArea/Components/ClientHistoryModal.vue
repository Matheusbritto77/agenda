<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    client: {
        type: Object,
        default: null,
    },
    statusClass: {
        type: Function,
        required: true,
    },
    statusLabel: {
        type: Function,
        required: true,
    },
    currency: {
        type: Function,
        required: true,
    },
});

defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show && client" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="$emit('close')">
            <div class="liquid-glass-card w-full max-w-3xl max-h-[90vh] overflow-y-auto p-5 sm:p-7 space-y-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-black" style="color: var(--text-heading);">Histórico de {{ client.name }}</h3>
                        <p class="text-xs opacity-60">{{ client.appointments_count }} atendimentos registrados</p>
                    </div>
                    <button type="button" @click="$emit('close')" class="w-9 h-9 rounded-xl hover:bg-slate-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="space-y-3">
                    <div v-for="item in client.history" :key="item.id" class="rounded-2xl border p-4" style="border-color: var(--border); background: var(--surface-subtle);">
                        <div class="flex flex-wrap justify-between gap-3">
                            <div>
                                <p class="font-extrabold">{{ item.service }}</p>
                                <p class="text-xs opacity-60">{{ item.professional }} · {{ item.date }}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 rounded-lg text-[10px] font-bold" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
                                <p class="font-black mt-1">{{ currency(item.price) }}</p>
                            </div>
                        </div>
                        <div v-if="item.review" class="mt-3 pt-3 border-t text-xs" style="border-color: var(--border);">
                            <span class="text-amber-400 mr-2">
                                <i v-for="star in 5" :key="star" class="fa-star" :class="star <= item.review.rating ? 'fa-solid' : 'fa-regular'"></i>
                            </span>
                            <span class="italic opacity-70">{{ item.review.comment || 'Sem comentário' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
