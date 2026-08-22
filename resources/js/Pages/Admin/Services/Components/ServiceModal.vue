<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    form: {
        type: Object,
        required: true,
    },
    imagePreview: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'submit', 'file-change', 'url-preview']);

const fileInputRef = ref(null);

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};

const onFileSelected = (e) => {
    const file = e.target.files?.[0];
    emit('file-change', file);
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div class="liquid-glass-card w-full max-w-lg p-6 sm:p-7 space-y-5 relative" @click.stop>
                <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-700 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-indigo-600/30">
                            <i class="fa-solid fa-scissors"></i>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">
                                {{ isEditing ? 'Editar Serviço' : 'Novo Serviço' }}
                            </h3>
                            <p class="text-xs opacity-60">Preencha os detalhes do procedimento</p>
                        </div>
                    </div>
                    <button type="button" @click="$emit('close')" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="$emit('submit')" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group mb-0 sm:col-span-2">
                            <label class="form-label text-xs">Nome do Serviço *</label>
                            <input type="text" v-model="form.name" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Ex: Corte de Cabelo Degradê" required />
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Preço (R$) *</label>
                            <input type="number" step="0.01" min="0" v-model="form.price" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Ex: 50.00" required />
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label text-xs">Duração (minutos) *</label>
                            <input type="number" min="5" step="5" v-model="form.duration_minutes" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Ex: 30" required />
                        </div>
                        <div class="form-group mb-0 sm:col-span-2">
                            <label class="form-label text-xs">Descrição do Serviço (Opcional)</label>
                            <textarea v-model="form.description" rows="2" class="form-control text-xs sm:text-sm rounded-xl" placeholder="Breve descrição dos detalhes ou produtos inclusos no serviço..."></textarea>
                        </div>

                        <!-- Image Upload -->
                        <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex items-center gap-3 sm:col-span-2">
                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-white dark:bg-slate-800 border flex items-center justify-center shrink-0">
                                <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                                <i v-else class="fa-solid fa-scissors text-slate-400"></i>
                            </div>
                            <div class="space-y-1">
                                <input type="file" ref="fileInputRef" @change="onFileSelected" accept="image/*" class="hidden" />
                                <button type="button" @click="fileInputRef?.click()" class="px-3 py-1.5 rounded-lg text-xs font-bold border bg-white dark:bg-slate-800 hover:bg-slate-50 transition-all cursor-pointer">
                                    <i class="fa-solid fa-upload mr-1 text-[10px]"></i>
                                    Escolher foto
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t flex items-center justify-end gap-2" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="btn btn-outline py-2 px-4 text-xs font-bold rounded-xl"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary py-2 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30"
                            :disabled="form.processing"
                        >
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>{{ form.processing ? 'Salvando...' : (isEditing ? 'Salvar Alterações' : 'Cadastrar Serviço') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
