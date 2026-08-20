<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ServiceCard from './Components/ServiceCard.vue';
import ServiceModal from './Components/ServiceModal.vue';
import ServiceDeleteModal from './Components/ServiceDeleteModal.vue';

const page = usePage();

const props = defineProps({
    services: {
        type: Array,
        default: () => [],
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
const editImagePreview = ref('');

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

const openCreateModal = () => {
    createForm.reset();
    createForm.duration_minutes = 30;
    createImagePreview.value = '';
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
    editImagePreview.value = service.image_url || '';
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

const handleCreateFileChange = (file) => {
    if (file) {
        createForm.image_file = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            createImagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const handleEditFileChange = (file) => {
    if (file) {
        editForm.image_file = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            editImagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
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
                <button
                    v-if="hasPermission('services.create')"
                    type="button"
                    @click="openCreateModal"
                    class="btn btn-primary self-start sm:self-auto py-2.5 px-4"
                >
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Novo Serviço</span>
                </button>
            </div>

            <!-- Services Grid / Table -->
            <ServiceCard
                :services="services"
                :can-edit="hasPermission('services.edit')"
                :can-delete="hasPermission('services.delete')"
                @open-create="openCreateModal"
                @open-edit="openEditModal"
                @open-delete="openDeleteModal"
            />
        </div>

        <!-- Create Modal -->
        <ServiceModal
            :show="showCreateModal"
            :is-editing="false"
            :form="createForm"
            :image-preview="createImagePreview"
            @close="closeCreateModal"
            @submit="submitCreate"
            @file-change="handleCreateFileChange"
        />

        <!-- Edit Modal -->
        <ServiceModal
            :show="showEditModal"
            :is-editing="true"
            :form="editForm"
            :image-preview="editImagePreview"
            @close="closeEditModal"
            @submit="submitEdit"
            @file-change="handleEditFileChange"
        />

        <!-- Delete Modal -->
        <ServiceDeleteModal
            :show="showDeleteModal"
            :service="deletingService"
            :is-deleting="deleteForm.processing"
            @close="closeDeleteModal"
            @confirm="submitDelete"
        />
    </AdminLayout>
</template>
