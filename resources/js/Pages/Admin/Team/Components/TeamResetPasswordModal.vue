<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    member: {
        type: Object,
        default: null,
    },
    isResetting: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close', 'confirm']);

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};
</script>

<template>
    <div
        v-if="show && member"
        class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
        @click="handleBackdropClick"
    >
        <div class="liquid-glass-card w-full max-w-md p-6 sm:p-7 space-y-5 text-center relative" @click.stop>
            <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20 flex items-center justify-center mx-auto text-2xl">
                <i class="fa-solid fa-key"></i>
            </div>

            <div class="space-y-1">
                <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">Resetar Senha</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Deseja gerar uma nova senha temporária para <strong>{{ member.name }}</strong> ({{ member.email }})? O link será enviado para o e-mail cadastrado.
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
                    :disabled="isResetting"
                    class="btn btn-primary py-2 px-5 text-xs font-bold rounded-xl shadow-md"
                >
                    {{ isResetting ? 'Enviando...' : 'Confirmar Reset' }}
                </button>
            </div>
        </div>
    </div>
</template>
