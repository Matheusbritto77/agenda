<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();

const props = defineProps({
    service: {
        type: Object,
        required: true,
    },
});

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    const userPerms = page.props.auth?.permissions || [];
    return userPerms.includes(permission);
};

const imagePreview = ref('');
const imagePreviewVisible = ref(false);

const form = useForm({
    id: props.service.id,
    name: props.service.name || '',
    description: props.service.description || '',
    price: props.service.price || '',
    duration_minutes: props.service.duration_minutes || 30,
    image_file: null,
    image_url: props.service.image_url || '',
    _method: 'PUT',
});

onMounted(() => {
    if (props.service.image_url) {
        imagePreview.value = props.service.image_url;
        imagePreviewVisible.value = true;
    }
});

const handleFileChange = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        form.image_file = file;
        form.image_url = '';
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
            imagePreviewVisible.value = true;
        };
        reader.readAsDataURL(file);
    }
};

const handleUrlPreview = () => {
    if (form.image_url && form.image_url.trim() !== '') {
        form.image_file = null;
        imagePreview.value = form.image_url;
        imagePreviewVisible.value = true;
    } else {
        imagePreview.value = '';
        imagePreviewVisible.value = false;
    }
};

const submit = () => {
    form.post(route('admin.services.update', props.service.id), {
        forceFormData: true,
        onSuccess: () => {
            router.visit(route('admin.services.index'));
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Editar Serviço - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Editar Serviço</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Atualize as informações, fotos e preços do serviço</p>
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
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold" style="color: var(--text-heading);">Editar Serviço</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Modificando: <span class="font-semibold" style="color: var(--text-heading);">{{ service.name }}</span></p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="form-group">
                        <label class="form-label" for="name">Nome do Serviço *</label>
                        <input
                            type="text"
                            id="name"
                            v-model="form.name"
                            class="form-control"
                            required
                        >
                        <div v-if="form.errors.name" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ form.errors.name }}</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Imagem do Serviço</label>

                        <div class="flex items-center gap-4 mb-3 p-3 bg-slate-100 dark:bg-slate-900/80 rounded-xl border border-slate-200 dark:border-slate-800 transition-colors">
                            <div class="w-16 h-16 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 flex items-center justify-center overflow-hidden shrink-0">
                                <img v-if="imagePreviewVisible" :src="imagePreview" alt="Pré-visualização" class="w-full h-full object-cover">
                                <i v-else class="fa-regular fa-image text-2xl text-slate-400 dark:text-slate-500"></i>
                            </div>
                            <div class="text-xs opacity-80 min-w-0">
                                <strong class="block" style="color: var(--text-heading);">Imagem Atual / Nova Imagem</strong>
                                <span class="text-[11px] block opacity-70">Selecione um novo arquivo para substituir a imagem existente.</span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <input
                                type="file"
                                @change="handleFileChange"
                                class="form-control text-xs file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer"
                                accept="image/*"
                            >
                            <div v-if="form.errors.image_file" class="text-rose-500 dark:text-rose-400 text-xs block">{{ form.errors.image_file }}</div>

                            <div>
                                <span class="text-xs opacity-70 block mb-1">Ou informe uma URL direta da imagem:</span>
                                <input
                                    type="url"
                                    v-model="form.image_url"
                                    @input="handleUrlPreview"
                                    class="form-control"
                                    placeholder="https://exemplo.com/foto.jpg"
                                >
                                <div v-if="form.errors.image_url" class="text-rose-500 dark:text-rose-400 text-xs block mt-1">{{ form.errors.image_url }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Descrição</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="form-control"
                            rows="3"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-rose-500 dark:text-rose-400 text-xs block">{{ form.errors.description }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label" for="price">Valor (R$) *</label>
                            <input
                                type="number"
                                step="0.01"
                                id="price"
                                v-model="form.price"
                                class="form-control"
                                required
                                :disabled="!hasPermission('services.prices')"
                            >
                            <div v-if="form.errors.price" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ form.errors.price }}</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="duration_minutes">Duração (Minutos) *</label>
                            <input
                                type="number"
                                id="duration_minutes"
                                v-model="form.duration_minutes"
                                class="form-control"
                                required
                            >
                            <div v-if="form.errors.duration_minutes" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ form.errors.duration_minutes }}</div>
                        </div>
                    </div>

                    <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                        <Link :href="route('admin.services.index')" class="btn btn-outline">Cancelar</Link>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>{{ form.processing ? 'Atualizando...' : 'Atualizar Serviço' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
