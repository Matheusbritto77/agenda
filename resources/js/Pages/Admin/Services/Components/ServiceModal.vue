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

const quickDurations = [15, 30, 45, 60, 90, 120];
const setDuration = (mins) => {
    props.form.duration_minutes = mins;
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] w-screen h-screen flex items-center justify-center p-4 sm:p-6 liquid-glass-backdrop"
            @click="handleBackdropClick"
        >
            <div class="liquid-glass-card w-full max-w-2xl p-6 sm:p-8 space-y-6 relative shadow-2xl max-h-[90vh] overflow-y-auto" @click.stop>
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-700 to-violet-600 text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-indigo-600/30 shrink-0">
                            <i class="fa-solid fa-scissors"></i>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-xl font-black tracking-tight" style="color: var(--text-heading);">
                                {{ isEditing ? 'Editar Serviço' : 'Novo Serviço' }}
                            </h3>
                            <p class="text-xs opacity-60 mt-0.5">Preencha os dados, valor e duração do procedimento</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all cursor-pointer"
                    >
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Form (Horizontal Layout Grid) -->
                <form @submit.prevent="$emit('submit')" class="space-y-5">
                    
                    <!-- Row 1: Service Name -->
                    <div class="form-group mb-0 space-y-1.5">
                        <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                            Nome do Serviço <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.name"
                            class="form-control text-xs sm:text-sm rounded-xl font-semibold"
                            placeholder="Ex: Corte de Cabelo Degradê, Barba Terapia, Manicure Completa"
                            required
                        />
                        <span v-if="form.errors?.name" class="text-xs text-rose-500 font-bold block mt-1">
                            {{ form.errors.name }}
                        </span>
                    </div>

                    <!-- Row 2: Price & Duration (Horizontal 2 Columns) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group mb-0 space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                <i class="fa-solid fa-brazilian-real-sign text-emerald-500 mr-1 text-[11px]"></i>
                                Preço (R$) <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-stretch rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-indigo-500/30 focus-within:border-indigo-500 overflow-hidden transition-all">
                                <span class="px-3.5 py-2.5 text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/80 border-r border-slate-200 dark:border-slate-700 flex items-center justify-center select-none">
                                    R$
                                </span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    v-model="form.price"
                                    class="w-full bg-transparent border-0 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 focus:ring-0 focus:outline-none"
                                    placeholder="50.00"
                                    required
                                />
                            </div>
                            <span v-if="form.errors?.price" class="text-xs text-rose-500 font-bold block mt-1">
                                {{ form.errors.price }}
                            </span>
                        </div>

                        <div class="form-group mb-0 space-y-1.5">
                            <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                                <i class="fa-regular fa-clock text-indigo-500 mr-1 text-[11px]"></i>
                                Duração (minutos) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                min="5"
                                step="5"
                                v-model="form.duration_minutes"
                                class="form-control text-xs sm:text-sm rounded-xl font-bold"
                                placeholder="30"
                                required
                            />
                            
                            <!-- Quick duration pills -->
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <button
                                    v-for="d in quickDurations"
                                    :key="d"
                                    type="button"
                                    @click="setDuration(d)"
                                    :class="[
                                        'px-2 py-0.5 rounded-lg text-[10px] font-bold border transition-all cursor-pointer',
                                        Number(form.duration_minutes) === d
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:border-indigo-400'
                                    ]"
                                >
                                    {{ d }} min
                                </button>
                            </div>
                            <span v-if="form.errors?.duration_minutes" class="text-xs text-rose-500 font-bold block mt-1">
                                {{ form.errors.duration_minutes }}
                            </span>
                        </div>
                    </div>

                    <!-- Row 3: Description -->
                    <div class="form-group mb-0 space-y-1.5">
                        <label class="form-label text-xs font-bold uppercase tracking-wider block" style="color: var(--text-heading);">
                            Descrição do Serviço (Opcional)
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="form-control text-xs sm:text-sm rounded-xl font-normal leading-relaxed"
                            placeholder="Breve descrição dos detalhes, produtos inclusos ou recomendações para o cliente..."
                        ></textarea>
                    </div>

                    <!-- Row 4: Photo Upload -->
                    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40 flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center shrink-0 shadow-inner">
                            <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" alt="Foto do serviço" />
                            <i v-else class="fa-solid fa-scissors text-2xl text-slate-300 dark:text-slate-600"></i>
                        </div>
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-200 block">Foto Ilustrativa do Serviço</label>
                            <input type="file" ref="fileInputRef" @change="onFileSelected" accept="image/*" class="hidden" id="service_photo_input" />
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    @click="fileInputRef?.click()"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 transition-all cursor-pointer shadow-2xs inline-flex items-center gap-1.5"
                                >
                                    <i class="fa-solid fa-upload text-[10px]"></i>
                                    <span>{{ imagePreview ? 'Trocar Foto' : 'Escolher Foto' }}</span>
                                </button>
                                <span class="text-[11px] text-slate-400 truncate">PNG, JPG ou WEBP até 10MB</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="btn btn-outline py-2.5 px-5 text-xs font-bold rounded-xl cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary py-2.5 px-6 text-xs font-black rounded-xl shadow-lg shadow-indigo-600/30 inline-flex items-center gap-2 cursor-pointer"
                            :disabled="form.processing"
                        >
                            <i v-if="form.processing" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                            <i v-else class="fa-solid fa-check text-xs"></i>
                            <span>{{ form.processing ? 'Salvando...' : (isEditing ? 'Salvar Alterações' : 'Cadastrar Serviço') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
