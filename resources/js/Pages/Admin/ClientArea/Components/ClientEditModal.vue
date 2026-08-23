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
    editForm: {
        type: Object,
        required: true,
    },
});

defineEmits(['close', 'save']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show && client" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 liquid-glass-backdrop" @click.self="$emit('close')">
            <form @submit.prevent="$emit('save')" class="liquid-glass-card w-full max-w-lg p-6 space-y-5">
                <div class="flex justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black" style="color: var(--text-heading);">Editar contato do cliente</h3>
                        <p class="text-xs opacity-60 mt-1">A alteração vale para o histórico visível da empresa.</p>
                    </div>
                    <button type="button" @click="$emit('close')" class="w-8 h-8 rounded-xl hover:bg-slate-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div>
                    <label class="form-label text-xs font-bold block mb-1">Nome</label>
                    <input v-model="editForm.name" class="form-control text-xs sm:text-sm font-bold" required />
                    <p v-if="editForm.errors.name" class="text-xs text-rose-500 mt-1">{{ editForm.errors.name }}</p>
                </div>
                <div>
                    <label class="form-label text-xs font-bold block mb-1">Telefone</label>
                    <input v-model="editForm.phone" class="form-control text-xs sm:text-sm" placeholder="(11) 99999-9999" />
                    <p v-if="editForm.errors.phone" class="text-xs text-rose-500 mt-1">{{ editForm.errors.phone }}</p>
                </div>
                <div class="rounded-xl bg-slate-500/10 p-3 text-xs opacity-70">
                    <i class="fa-solid fa-lock mr-1.5"></i>
                    O e-mail <strong>{{ client.account_email }}</strong> identifica a conta global do cliente e não pode ser alterado pela empresa.
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t" style="border-color: var(--border);">
                    <button type="button" class="btn btn-outline rounded-xl text-xs" @click="$emit('close')">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-xl text-xs flex items-center gap-1.5" :disabled="editForm.processing">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>{{ editForm.processing ? 'Salvando...' : 'Salvar dados' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </Teleport>
</template>
