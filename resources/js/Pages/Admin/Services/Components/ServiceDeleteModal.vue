<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    service: {
        type: Object,
        default: null,
    },
    isDeleting: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show && service"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div class="liquid-glass-card w-full max-w-md p-6 sm:p-7 space-y-5 text-center relative" @click.stop>
                <div class="w-14 h-14 rounded-2xl bg-rose-500/10 text-rose-500 border border-rose-500/20 flex items-center justify-center mx-auto text-2xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div class="space-y-1">
                    <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">Excluir Serviço</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Tem certeza de que deseja remover <strong>"{{ service.name }}"</strong>? Esta ação não pode ser desfeita.
                    </p>
                </div>

                <div class="pt-3 border-t flex items-center justify-center gap-2" style="border-color: var(--border);">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="btn btn-outline py-2 px-4 text-xs font-bold rounded-xl"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="$emit('confirm')"
                        :disabled="isDeleting"
                        class="py-2 px-5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-md transition-all cursor-pointer disabled:opacity-50"
                    >
                        {{ isDeleting ? 'Excluindo...' : 'Sim, Excluir' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
