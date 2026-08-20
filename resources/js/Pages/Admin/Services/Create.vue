<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const imagePreview = ref('');
const imagePreviewVisible = ref(false);

const form = useForm({
    name: '',
    description: '',
    price: '',
    duration_minutes: 30,
    image_file: null,
    image_url: '',
});

const handleFileChange = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        form.image_file = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
            imagePreviewVisible.value = true;
        };
        reader.readAsDataURL(file);
    }
};

const handleUrlPreview = () => {
    if (form.image_file) return;
    if (form.image_url && form.image_url.trim() !== '') {
        imagePreview.value = form.image_url;
        imagePreviewVisible.value = true;
    } else {
        imagePreview.value = '';
        imagePreviewVisible.value = false;
    }
};

const submit = () => {
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('description', form.description);
    formData.append('price', form.price);
    formData.append('duration_minutes', form.duration_minutes);
    formData.append('image_url', form.image_url);
    if (form.image_file) {
        formData.append('image_file', form.image_file);
    }

    form.post(route('admin.services.store'), {
        data: formData,
        forceFormData: true,
        onSuccess: () => {
            router.visit(route('admin.services.index'));
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Novo Serviço - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Cadastrar Serviço</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Adicione uma nova opção de atendimento ao catálogo</p>
        </template>

        <div class="max-w-2xl mx-auto space-y-4">
            <div>
                <Link :href="route('admin.services.index')" class="btn btn-outline text-xs py-2 px-3">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Voltar para Lista</span>
                </Link>
            </div>

            <div class="card">
                <div class="flex items-center gap-3 pb-4 mb-6 border-b" style="border-color: var(--border);">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-scissors"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold" style="color: var(--text-heading);">Novo Serviço</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Preencha as informações do serviço e valor cobrado.</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                        <div class="form-group md:col-span-2 lg:col-span-2">
                            <label class="form-label text-xs sm:text-sm" for="name">Nome do Serviço *</label>
                            <input
                                type="text"
                                id="name"
                                v-model="form.name"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="Ex: Corte Masculino Degradê"
                                required
                            >
                            <div v-if="form.errors.name" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ form.errors.name }}</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label text-xs sm:text-sm" for="price">Valor (R$) *</label>
                            <input
                                type="number"
                                step="0.01"
                                id="price"
                                v-model="form.price"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="45.00"
                                required
                            >
                            <div v-if="form.errors.price" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ form.errors.price }}</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label text-xs sm:text-sm" for="duration_minutes">Duração (Minutos) *</label>
                            <input
                                type="number"
                                id="duration_minutes"
                                v-model="form.duration_minutes"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                placeholder="30"
                                required
                            >
                            <div v-if="form.errors.duration_minutes" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ form.errors.duration_minutes }}</div>
                        </div>

                        <div class="form-group md:col-span-2 lg:col-span-2">
                            <label class="form-label text-xs sm:text-sm" for="description">Descrição</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="form-control text-xs sm:text-sm rounded-xl"
                                rows="3"
                                placeholder="Descreva os detalhes, produtos inclusos ou recomendações para o cliente..."
                            ></textarea>
                            <div v-if="form.errors.description" class="text-rose-500 dark:text-rose-400 text-xs block">{{ form.errors.description }}</div>
                        </div>

                        <div class="form-group md:col-span-2 lg:col-span-3">
                            <label class="form-label text-xs sm:text-sm">Imagem do Serviço</label>

                            <div class="flex items-center gap-3 sm:gap-4 mb-3 p-3 bg-slate-100 dark:bg-slate-900/80 rounded-xl border border-slate-200 dark:border-slate-800 transition-colors">
                                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 flex items-center justify-center overflow-hidden shrink-0">
                                    <img v-if="imagePreviewVisible" :src="imagePreview" alt="Pré-visualização" class="w-full h-full object-cover">
                                    <i v-else class="fa-regular fa-image text-xl sm:text-2xl text-slate-400 dark:text-slate-500"></i>
                                </div>
                                <div class="text-xs opacity-80 min-w-0">
                                    <strong class="block truncate" style="color: var(--text-heading);">Pré-visualização da Imagem</strong>
                                    <span class="text-[11px] block opacity-70 truncate">Faça o upload do arquivo ou insira uma URL abaixo.</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                                <div>
                                    <span class="text-xs opacity-70 block mb-1">Upload do Arquivo:</span>
                                    <input
                                        type="file"
                                        @change="handleFileChange"
                                        class="form-control text-xs rounded-xl file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer w-full"
                                        accept="image/*"
                                    >
                                    <div v-if="form.errors.image_file" class="text-rose-500 dark:text-rose-400 text-xs block mt-1">{{ form.errors.image_file }}</div>
                                </div>

                                <div>
                                    <span class="text-xs opacity-70 block mb-1">Ou informe a URL direta:</span>
                                    <input
                                        type="url"
                                        v-model="form.image_url"
                                        @input="handleUrlPreview"
                                        class="form-control text-xs sm:text-sm rounded-xl w-full"
                                        placeholder="https://exemplo.com/foto.jpg"
                                    >
                                    <div v-if="form.errors.image_url" class="text-rose-500 dark:text-rose-400 text-xs block mt-1">{{ form.errors.image_url }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                        <Link :href="route('admin.services.index')" class="btn btn-outline">Cancelar</Link>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>{{ form.processing ? 'Salvando...' : 'Salvar Serviço' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
