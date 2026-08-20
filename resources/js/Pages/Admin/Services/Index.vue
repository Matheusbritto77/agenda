<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();

const props = defineProps({
    services: {
        type: Array,
        default: () => []
    },
});

const hasPermission = (permission) => {
    if (page.props.auth?.role === 'admin') return true;
    const userPerms = page.props.auth?.permissions || [];
    return userPerms.includes(permission);
};

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingService = ref(null);
const deletingService = ref(null);

const createImagePreview = ref('');
const createImagePreviewVisible = ref(false);
const editImagePreview = ref('');
const editImagePreviewVisible = ref(false);

const createForm = useForm({
    name: '',
    description: '',
    price: '',
    duration_minutes: 30,
    image_file: null,
    image_url: '',
});

const editForm = useForm({
    id: null,
    name: '',
    description: '',
    price: '',
    duration_minutes: 30,
    image_file: null,
    image_url: '',
    _method: 'PUT',
});

const deleteForm = useForm({});

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const openCreateModal = () => {
    createForm.reset();
    createForm.duration_minutes = 30;
    createImagePreview.value = '';
    createImagePreviewVisible.value = false;
    showCreateModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openEditModal = (service) => {
    editingService.value = service;
    editForm.reset();
    editForm.id = service.id;
    editForm.name = service.name || '';
    editForm.description = service.description || '';
    editForm.price = service.price || '';
    editForm.duration_minutes = service.duration_minutes || 30;
    editForm.image_url = service.image_url || '';
    editForm.image_file = null;

    if (service.image_url) {
        editImagePreview.value = service.image_url;
        editImagePreviewVisible.value = true;
    } else {
        editImagePreview.value = '';
        editImagePreviewVisible.value = false;
    }

    showEditModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const openDeleteModal = (service) => {
    deletingService.value = service;
    showDeleteModal.value = true;
    document.body.classList.add('overflow-hidden');
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    document.body.classList.remove('overflow-hidden');
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingService.value = null;
    document.body.classList.remove('overflow-hidden');
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletingService.value = null;
    document.body.classList.remove('overflow-hidden');
};

const handleBackdropClick = (event, closeFn) => {
    if (event.target === event.currentTarget) {
        closeFn();
    }
};

const handleCreateFileChange = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            createImagePreview.value = e.target.result;
            createImagePreviewVisible.value = true;
        };
        reader.readAsDataURL(file);
    }
};

const handleCreateUrlPreview = () => {
    if (createForm.image_file) return;
    if (createForm.image_url && createForm.image_url.trim() !== '') {
        createImagePreview.value = createForm.image_url;
        createImagePreviewVisible.value = true;
    } else {
        createImagePreview.value = '';
        createImagePreviewVisible.value = false;
    }
};

const handleEditFileChange = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            editImagePreview.value = e.target.result;
            editImagePreviewVisible.value = true;
        };
        reader.readAsDataURL(file);
    }
};

const handleEditUrlPreview = () => {
    if (editForm.image_file) return;
    if (editForm.image_url && editForm.image_url.trim() !== '') {
        editImagePreview.value = editForm.image_url;
        editImagePreviewVisible.value = true;
    } else {
        editImagePreview.value = '';
        editImagePreviewVisible.value = false;
    }
};

const submitCreate = () => {
    const formData = new FormData();
    formData.append('name', createForm.name);
    formData.append('description', createForm.description);
    formData.append('price', createForm.price);
    formData.append('duration_minutes', createForm.duration_minutes);
    formData.append('image_url', createForm.image_url);
    if (createForm.image_file) {
        formData.append('image_file', createForm.image_file);
    }

    createForm.post(route('admin.services.store'), {
        data: formData,
        forceFormData: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

const submitEdit = () => {
    editForm.post(route('admin.services.update', editForm.id), {
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const submitDelete = () => {
    if (!deletingService.value) return;
    deleteForm.delete(route('admin.services.destroy', deletingService.value.id), {
        onSuccess: () => {
            closeDeleteModal();
        },
    });
};

onMounted(() => {
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            showCreateModal.value = false;
            showEditModal.value = false;
            showDeleteModal.value = false;
            document.body.classList.remove('overflow-hidden');
        }
    });
});
</script>

<template>
    <AdminLayout>
        <Head title="Gerenciar Serviços - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Catálogo de Serviços</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Serviços oferecidos, duração e tabela de preços</p>
        </template>

        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight" style="color: var(--text-heading);">Serviços e Valores</h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Configure os serviços disponíveis para agendamento online.</p>
                </div>
                <button v-if="hasPermission('services.create')" type="button" @click="openCreateModal" class="btn btn-primary self-start sm:self-auto py-2.5 px-4">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Novo Serviço</span>
                </button>
            </div>

            <div class="card overflow-hidden p-0">
                <template v-if="services.length === 0">
                    <div class="text-center py-12 px-4 text-slate-500 dark:text-slate-400">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 flex items-center justify-center mx-auto mb-3 text-2xl">
                            <i class="fa-solid fa-scissors"></i>
                        </div>
                        <h4 class="text-base font-bold" style="color: var(--text-heading);">Nenhum serviço cadastrado ainda</h4>
                        <p class="text-xs opacity-70 mt-1 max-w-sm mx-auto">
                            Cadastre os serviços do seu estabelecimento para que os clientes possam agendar online.
                        </p>
                        <div class="mt-4">
                            <button type="button" @click="openCreateModal" class="btn btn-primary text-xs py-2 px-4">
                                <i class="fa-solid fa-plus text-xs"></i>
                                <span>Adicionar Primeiro Serviço</span>
                            </button>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="hidden md:block table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="w-16">Imagem</th>
                                    <th>Nome do Serviço</th>
                                    <th>Descrição</th>
                                    <th>Duração</th>
                                    <th>Valor</th>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="service in services" :key="service.id">
                                    <td>
                                        <div class="w-12 h-12 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center shrink-0">
                                            <img v-if="service.image_url" :src="service.image_url" :alt="service.name" class="w-full h-full object-cover" loading="lazy">
                                            <i v-else class="fa-solid fa-scissors text-indigo-500 dark:text-indigo-400 text-sm"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-bold block text-sm" style="color: var(--text-heading);">{{ service.name }}</span>
                                        <span class="text-[11px] opacity-60">ID #{{ service.id }}</span>
                                    </td>
                                    <td>
                                        <p class="text-xs opacity-80 max-w-xs line-clamp-2" :title="service.description">
                                            {{ service.description || 'Sem descrição informada.' }}
                                        </p>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold bg-slate-100 dark:bg-slate-800/80 px-2.5 py-1 rounded-full border border-slate-200 dark:border-slate-700">
                                            <i class="fa-regular fa-clock text-indigo-500 dark:text-indigo-400 text-[11px]"></i>
                                            {{ service.duration_minutes }} min
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-indigo-600 dark:text-indigo-300 font-extrabold text-sm whitespace-nowrap">
                                            R$ {{ formatCurrency(service.price) }}
                                        </strong>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2">
                                            <button
                                                v-if="hasPermission('services.edit')"
                                                type="button"
                                                class="btn btn-outline py-1.5 px-3 text-xs"
                                                @click="openEditModal(service)"
                                                title="Editar Serviço"
                                            >
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                <span>Editar</span>
                                            </button>

                                            <button
                                                v-if="hasPermission('services.delete')"
                                                type="button"
                                                class="btn btn-danger py-1.5 px-3 text-xs"
                                                @click="openDeleteModal(service)"
                                                title="Excluir Serviço"
                                            >
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                                <span>Excluir</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="md:hidden divide-y border-t" style="border-color: var(--border);">
                        <div v-for="service in services" :key="service.id" class="p-4 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center shrink-0">
                                    <img v-if="service.image_url" :src="service.image_url" :alt="service.name" class="w-full h-full object-cover">
                                    <i v-else class="fa-solid fa-scissors text-indigo-500 text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-sm truncate" style="color: var(--text-heading);">{{ service.name }}</h3>
                                    <p class="text-xs opacity-70 line-clamp-1">{{ service.description || 'Sem descrição' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800/60 text-xs">
                                <span class="inline-flex items-center gap-1 font-semibold opacity-80">
                                    <i class="fa-regular fa-clock text-indigo-500"></i>
                                    {{ service.duration_minutes }} min
                                </span>
                                <strong class="text-indigo-600 dark:text-indigo-400 font-extrabold text-sm">
                                    R$ {{ formatCurrency(service.price) }}
                                </strong>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2">
                                <button
                                    v-if="hasPermission('services.edit')"
                                    type="button"
                                    class="btn btn-outline py-1.5 px-3 text-xs flex-1 text-center justify-center"
                                    @click="openEditModal(service)"
                                >
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    <span>Editar</span>
                                </button>

                                <button
                                    v-if="hasPermission('services.delete')"
                                    type="button"
                                    class="btn btn-danger py-1.5 px-3 text-xs flex-1 text-center justify-center"
                                    @click="openDeleteModal(service)"
                                >
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                    <span>Excluir</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <Teleport to="body">
                <div
                    v-if="showCreateModal"
                    class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4"
                    @click="handleBackdropClick($event, closeCreateModal)"
                >
                    <div class="liquid-glass-card w-full max-w-xl p-6 sm:p-7 space-y-5 relative" @click.stop>
                        <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shadow-md shadow-indigo-500/20">
                                    <i class="fa-solid fa-scissors"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-extrabold" style="color: var(--text-heading);">Novo Serviço</h3>
                                    <p class="text-xs opacity-60">Cadastre uma nova opção de atendimento no catálogo</p>
                                </div>
                            </div>
                            <button type="button" @click="closeCreateModal" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitCreate" class="space-y-4">
                            <div class="form-group mb-0">
                                <label class="form-label text-xs" for="create_name">Nome do Serviço *</label>
                                <input
                                    type="text"
                                    id="create_name"
                                    v-model="createForm.name"
                                    class="form-control text-xs sm:text-sm rounded-xl"
                                    placeholder="Ex: Corte Degradê Premium"
                                    required
                                >
                                <div v-if="createForm.errors.name" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ createForm.errors.name }}</div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label text-xs">Imagem do Serviço (Arquivo ou URL)</label>

                                <div class="flex items-center gap-3 mb-2 p-2.5 bg-slate-100 dark:bg-slate-900/80 rounded-xl border border-slate-200 dark:border-slate-800">
                                    <div class="w-12 h-12 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="createImagePreviewVisible" :src="createImagePreview" alt="Preview" class="w-full h-full object-cover">
                                        <i v-else class="fa-regular fa-image text-xl text-slate-400"></i>
                                    </div>
                                    <div class="text-xs opacity-75 min-w-0">
                                        <span class="block font-semibold" style="color: var(--text-heading);">Pré-visualização</span>
                                        <span class="text-[11px] block opacity-70 truncate">Foto exibida no agendamento do cliente</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <input
                                        type="file"
                                        @change="handleCreateFileChange"
                                        class="form-control text-xs file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer"
                                        accept="image/*"
                                    >
                                    <input
                                        type="url"
                                        v-model="createForm.image_url"
                                        @input="handleCreateUrlPreview"
                                        class="form-control text-xs"
                                        placeholder="Ou URL https://..."
                                    >
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label text-xs" for="create_description">Descrição do Atendimento</label>
                                <textarea
                                    id="create_description"
                                    v-model="createForm.description"
                                    class="form-control text-xs sm:text-sm rounded-xl"
                                    rows="2"
                                    placeholder="Descreva os detalhes, produtos inclusos ou recomendações..."
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="form-group mb-0">
                                    <label class="form-label text-xs" for="create_price">Valor (R$) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        id="create_price"
                                        v-model="createForm.price"
                                        class="form-control text-xs sm:text-sm rounded-xl"
                                        placeholder="45.00"
                                        required
                                        :disabled="!hasPermission('services.prices')"
                                    >
                                    <div v-if="createForm.errors.price" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ createForm.errors.price }}</div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label text-xs" for="create_duration">Duração (Minutos) *</label>
                                    <input
                                        type="number"
                                        id="create_duration"
                                        v-model="createForm.duration_minutes"
                                        class="form-control text-xs sm:text-sm rounded-xl"
                                        placeholder="30"
                                        required
                                    >
                                    <div v-if="createForm.errors.duration_minutes" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ createForm.errors.duration_minutes }}</div>
                                </div>
                            </div>

                            <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                                <button type="button" @click="closeCreateModal" class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl">Cancelar</button>
                                <button type="submit" class="btn btn-primary py-2.5 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30" :disabled="createForm.processing">
                                    <i class="fa-solid fa-check text-xs"></i>
                                    <span>{{ createForm.processing ? 'Cadastrando...' : 'Cadastrar Serviço' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <Teleport to="body">
                <div
                    v-if="showEditModal && editingService"
                    class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4"
                    @click="handleBackdropClick($event, closeEditModal)"
                >
                    <div class="liquid-glass-card w-full max-w-xl p-6 sm:p-7 space-y-5 relative" @click.stop>
                        <div class="flex items-center justify-between pb-4 border-b" style="border-color: var(--border);">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg shadow-md shadow-indigo-500/20">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-extrabold" style="color: var(--text-heading);">Editar: {{ editingService.name }}</h3>
                                    <p class="text-xs opacity-60">Atualize informações, preços ou duração</p>
                                </div>
                            </div>
                            <button type="button" @click="closeEditModal" class="w-9 h-9 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitEdit" class="space-y-4">
                            <div class="form-group mb-0">
                                <label class="form-label text-xs" for="edit_name">Nome do Serviço *</label>
                                <input
                                    type="text"
                                    id="edit_name"
                                    v-model="editForm.name"
                                    class="form-control text-xs sm:text-sm rounded-xl"
                                    required
                                >
                                <div v-if="editForm.errors.name" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ editForm.errors.name }}</div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label text-xs">Imagem do Serviço</label>

                                <div class="flex items-center gap-3 mb-2 p-2.5 bg-slate-100 dark:bg-slate-900/80 rounded-xl border border-slate-200 dark:border-slate-800">
                                    <div class="w-12 h-12 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="editImagePreviewVisible" :src="editImagePreview" alt="Preview" class="w-full h-full object-cover">
                                        <i v-else class="fa-regular fa-image text-xl text-slate-400"></i>
                                    </div>
                                    <div class="text-xs opacity-75 min-w-0">
                                        <span class="block font-semibold" style="color: var(--text-heading);">Imagem Atual</span>
                                        <span class="text-[11px] block opacity-70 truncate">Selecione nova foto ou insira nova URL</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <input
                                        type="file"
                                        @change="handleEditFileChange"
                                        class="form-control text-xs file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer"
                                        accept="image/*"
                                    >
                                    <input
                                        type="url"
                                        v-model="editForm.image_url"
                                        @input="handleEditUrlPreview"
                                        class="form-control text-xs"
                                        placeholder="Ou URL https://..."
                                    >
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label text-xs" for="edit_description">Descrição</label>
                                <textarea
                                    id="edit_description"
                                    v-model="editForm.description"
                                    class="form-control text-xs sm:text-sm rounded-xl"
                                    rows="2"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="form-group mb-0">
                                    <label class="form-label text-xs" for="edit_price">Valor (R$) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        id="edit_price"
                                        v-model="editForm.price"
                                        class="form-control text-xs sm:text-sm rounded-xl"
                                        required
                                        :disabled="!hasPermission('services.prices')"
                                    >
                                    <div v-if="editForm.errors.price" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ editForm.errors.price }}</div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label text-xs" for="edit_duration">Duração (Minutos) *</label>
                                    <input
                                        type="number"
                                        id="edit_duration"
                                        v-model="editForm.duration_minutes"
                                        class="form-control text-xs sm:text-sm rounded-xl"
                                        required
                                    >
                                    <div v-if="editForm.errors.duration_minutes" class="text-rose-500 dark:text-rose-400 text-xs mt-1 block">{{ editForm.errors.duration_minutes }}</div>
                                </div>
                            </div>

                            <div class="pt-4 border-t flex items-center justify-end gap-3" style="border-color: var(--border);">
                                <button type="button" @click="closeEditModal" class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl">Cancelar</button>
                                <button type="submit" class="btn btn-primary py-2.5 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30" :disabled="editForm.processing">
                                    <i class="fa-solid fa-check text-xs"></i>
                                    <span>{{ editForm.processing ? 'Atualizando...' : 'Atualizar Serviço' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <Teleport to="body">
                <div
                    v-if="showDeleteModal && deletingService"
                    class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4"
                    @click="handleBackdropClick($event, closeDeleteModal)"
                >
                    <div class="liquid-glass-card w-full max-w-md p-6 space-y-4 relative" @click.stop>
                        <div class="flex items-center gap-3 pb-3 border-b" style="border-color: var(--border);">
                            <div class="w-10 h-10 rounded-2xl bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center justify-center text-lg">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-rose-600 dark:text-rose-400">Excluir Serviço</h3>
                                <p class="text-xs opacity-60">Esta ação não pode ser desfeita</p>
                            </div>
                        </div>

                        <p class="text-xs sm:text-sm" style="color: var(--text);">
                            Tem certeza de que deseja remover permanentemente o serviço <strong class="font-bold text-indigo-600 dark:text-indigo-400">{{ deletingService.name }}</strong>?
                        </p>

                        <form @submit.prevent="submitDelete" class="pt-3 border-t flex items-center justify-end gap-2.5" style="border-color: var(--border);">
                            <button type="button" @click="closeDeleteModal" class="btn btn-outline py-2 px-3.5 text-xs font-bold rounded-xl">Cancelar</button>
                            <button type="submit" class="btn btn-danger py-2 px-4 text-xs font-bold rounded-xl" :disabled="deleteForm.processing">
                                <i class="fa-solid fa-trash-can text-xs mr-1"></i>
                                <span>{{ deleteForm.processing ? 'Excluindo...' : 'Sim, Excluir' }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </AdminLayout>
</template>
