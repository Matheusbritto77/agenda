<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    branding: {
        type: Object,
        default: null,
    },
});

const defaultColors = {
    top_menu_color: '#ffffff',
    background_color: '#f1f5f9',
    primary_color: '#6366f1',
};

const form = useForm({
    top_menu_color: props.branding?.top_menu_color || defaultColors.top_menu_color,
    background_color: props.branding?.background_color || defaultColors.background_color,
    primary_color: props.branding?.primary_color || defaultColors.primary_color,
    logo_file: null,
    delete_logo: false,
});

const saveSuccess = ref(false);
const logoPreview = ref(props.branding?.logo_url || null);
const fileInput = ref(null);

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo_file = file;
        form.delete_logo = false;
        
        const reader = new FileReader();
        reader.onload = (event) => {
            logoPreview.value = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeLogo = () => {
    form.logo_file = null;
    logoPreview.value = null;
    form.delete_logo = true;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const resetToDefault = () => {
    form.top_menu_color = defaultColors.top_menu_color;
    form.background_color = defaultColors.background_color;
    form.primary_color = defaultColors.primary_color;
};

const submit = () => {
    form.post(route('admin.branding.update'), {
        preserveScroll: true,
        onSuccess: () => {
            saveSuccess.value = true;
            setTimeout(() => {
                saveSuccess.value = false;
            }, 3000);
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Identidade Visual & Cores - Agendae" />

        <template #header>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-base sm:text-xl font-extrabold truncate" style="color: var(--text-heading);">Identidade Visual</h1>
            </div>
            <p class="text-xs opacity-60 hidden sm:block truncate">Personalize as cores e a logo da sua página pública de agendamento</p>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">
            <div v-if="saveSuccess" class="p-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-xs sm:text-sm font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>Identidade visual salva com sucesso!</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Customization Form -->
                <div class="lg:col-span-6 space-y-6">
                    <div class="card p-6 space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b" style="border-color: var(--border);">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg">
                                <i class="fa-solid fa-palette"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-base" style="color: var(--text-heading);">Cores & Logo</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Escolha sua paleta de cores institucional</p>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <!-- Logo Upload -->
                            <div class="space-y-2">
                                <label class="form-label text-xs font-bold block">Logotipo do Estabelecimento</label>
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-20 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white flex items-center justify-center overflow-hidden shadow-inner shrink-0">
                                        <img v-if="logoPreview" :src="logoPreview" class="object-contain w-full h-full p-1" alt="Logo preview" />
                                        <i v-else class="fa-solid fa-image text-2xl text-slate-300"></i>
                                    </div>
                                    <div class="space-y-2">
                                        <input
                                            type="file"
                                            ref="fileInput"
                                            @change="onFileChange"
                                            accept="image/*"
                                            class="hidden"
                                            id="logo_file_input"
                                        />
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                @click="$refs.fileInput.click()"
                                                class="px-4 py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all cursor-pointer"
                                            >
                                                Escolher imagem
                                            </button>
                                            <button
                                                v-if="logoPreview"
                                                type="button"
                                                @click="removeLogo"
                                                class="px-4 py-2 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-900/30 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-950/40 transition-all cursor-pointer"
                                            >
                                                Remover
                                            </button>
                                        </div>
                                        <p class="text-[10px] text-slate-400">Recomendado: Imagem PNG com fundo transparente. Máx: 2MB.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Color Pickers -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div class="form-group">
                                    <label class="form-label text-xs font-bold block" for="top_menu_color">Menu Superior</label>
                                    <div class="flex gap-2">
                                        <input
                                            type="color"
                                            id="top_menu_color_picker"
                                            v-model="form.top_menu_color"
                                            class="w-10 h-10 border rounded-xl cursor-pointer shrink-0"
                                        />
                                        <input
                                            type="text"
                                            id="top_menu_color"
                                            v-model="form.top_menu_color"
                                            class="form-control text-xs sm:text-sm rounded-xl uppercase"
                                            maxlength="7"
                                        />
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label text-xs font-bold block" for="background_color">Cor de Fundo</label>
                                    <div class="flex gap-2">
                                        <input
                                            type="color"
                                            id="background_color_picker"
                                            v-model="form.background_color"
                                            class="w-10 h-10 border rounded-xl cursor-pointer shrink-0"
                                        />
                                        <input
                                            type="text"
                                            id="background_color"
                                            v-model="form.background_color"
                                            class="form-control text-xs sm:text-sm rounded-xl uppercase"
                                            maxlength="7"
                                        />
                                    </div>
                                </div>

                                <div class="form-group sm:col-span-2">
                                    <label class="form-label text-xs font-bold block" for="primary_color">Cor dos Botões & Destaques (Cor Primária)</label>
                                    <div class="flex gap-2">
                                        <input
                                            type="color"
                                            id="primary_color_picker"
                                            v-model="form.primary_color"
                                            class="w-10 h-10 border rounded-xl cursor-pointer shrink-0"
                                        />
                                        <input
                                            type="text"
                                            id="primary_color"
                                            v-model="form.primary_color"
                                            class="form-control text-xs sm:text-sm rounded-xl uppercase"
                                            maxlength="7"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t flex items-center justify-between gap-3" style="border-color: var(--border);">
                                <button
                                    type="button"
                                    @click="resetToDefault"
                                    class="px-4 py-2.5 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all cursor-pointer"
                                >
                                    Cores Originais
                                </button>

                                <button
                                    type="submit"
                                    class="btn btn-primary py-2.5 px-5 text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30"
                                    :disabled="form.processing"
                                >
                                    <i class="fa-solid fa-floppy-disk text-xs mr-1"></i>
                                    <span>{{ form.processing ? 'Salvando...' : 'Salvar Identidade' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Live Preview Mockup -->
                <div class="lg:col-span-6 space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Pré-visualização em Tempo Real</h4>
                    <div
                        class="border rounded-2xl overflow-hidden shadow-lg transition-all duration-300 min-h-[420px] flex flex-col bg-slate-100 relative"
                        :style="{ backgroundColor: form.background_color }"
                    >
                        <!-- Header Mockup -->
                        <header
                            class="h-14 border-b flex items-center justify-between px-4 transition-all duration-300 bg-white"
                            :style="{ backgroundColor: form.top_menu_color, borderColor: 'rgba(0,0,0,0.06)' }"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg overflow-hidden flex items-center justify-center bg-slate-100 shrink-0">
                                    <img v-if="logoPreview" :src="logoPreview" class="object-contain w-full h-full p-0.5" />
                                    <div v-else class="w-full h-full bg-gradient-to-tr from-indigo-500 to-cyan-500 flex items-center justify-center text-white text-[10px] font-bold">A</div>
                                </div>
                                <span class="font-extrabold text-sm text-slate-900 dark:text-slate-900">Agendae</span>
                            </div>
                            <div class="w-7 h-7 rounded-lg bg-slate-100 border flex items-center justify-center">
                                <i class="fa-solid fa-moon text-[10px] text-slate-400"></i>
                            </div>
                        </header>

                        <!-- Main Page Body Mockup -->
                        <div class="flex-1 p-4 space-y-4">
                            <!-- Banner/Hero mockup -->
                            <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm space-y-2">
                                <div class="w-24 h-3 bg-slate-200 rounded-full"></div>
                                <div class="w-40 h-4 bg-slate-300 rounded-full"></div>
                                <div class="w-full h-2 bg-slate-100 rounded-full"></div>
                            </div>

                            <!-- Stepper mockup -->
                            <div class="grid grid-cols-3 gap-2">
                                <div class="h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center gap-1.5 shadow-sm p-1">
                                    <div class="w-5 h-5 rounded-lg flex items-center justify-center text-[9px] font-bold text-white" :style="{ backgroundColor: form.primary_color }">1</div>
                                    <div class="w-10 h-2 bg-slate-200 rounded-full hidden sm:block"></div>
                                </div>
                                <div class="h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center gap-1.5 shadow-sm p-1">
                                    <div class="w-5 h-5 rounded-lg bg-slate-200 flex items-center justify-center text-[9px] font-bold text-slate-500">2</div>
                                    <div class="w-10 h-2 bg-slate-100 rounded-full hidden sm:block"></div>
                                </div>
                                <div class="h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center gap-1.5 shadow-sm p-1">
                                    <div class="w-5 h-5 rounded-lg bg-slate-200 flex items-center justify-center text-[9px] font-bold text-slate-500">3</div>
                                    <div class="w-10 h-2 bg-slate-100 rounded-full hidden sm:block"></div>
                                </div>
                            </div>

                            <!-- Button Mockup -->
                            <div class="pt-4 flex justify-end">
                                <div
                                    class="py-2.5 px-6 rounded-xl font-bold text-xs text-white shadow-md transition-all duration-300 animate-pulse"
                                    :style="{ backgroundColor: form.primary_color }"
                                >
                                    Avançar
                                </div>
                            </div>
                        </div>

                        <!-- Footer Mockup -->
                        <footer class="h-10 bg-white/60 border-t border-slate-200/50 flex items-center justify-center text-[9px] text-slate-400">
                            &copy; 2026 Agendae. Todos os direitos reservados.
                        </footer>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>
