<script setup>
defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Top Menu Color -->
            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="top_menu_color">Menu Superior / Cabeçalho</label>
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

            <!-- Page Background Color -->
            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="background_color">Fundo da Página</label>
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

            <!-- Primary Color (Buttons & Highlights) -->
            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="primary_color">Cor Primária (Botões & Destaques)</label>
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

            <!-- Card Surface Color -->
            <div class="form-group mb-0">
                <label class="form-label text-xs font-bold block" for="card_bg_color">Cor dos Cards / Superfícies</label>
                <div class="flex gap-2">
                    <input
                        type="color"
                        id="card_bg_color_picker"
                        v-model="form.card_bg_color"
                        class="w-10 h-10 border rounded-xl cursor-pointer shrink-0"
                    />
                    <input
                        type="text"
                        id="card_bg_color"
                        v-model="form.card_bg_color"
                        class="form-control text-xs sm:text-sm rounded-xl uppercase"
                        maxlength="7"
                    />
                </div>
            </div>

            <!-- Text Color -->
            <div class="form-group mb-0 sm:col-span-2">
                <label class="form-label text-xs font-bold block" for="text_color">Cor Principal dos Textos & Títulos</label>
                <div class="flex gap-2">
                    <input
                        type="color"
                        id="text_color_picker"
                        v-model="form.text_color"
                        class="w-10 h-10 border rounded-xl cursor-pointer shrink-0"
                    />
                    <input
                        type="text"
                        id="text_color"
                        v-model="form.text_color"
                        class="form-control text-xs sm:text-sm rounded-xl uppercase"
                        maxlength="7"
                    />
                </div>
            </div>
        </div>

        <!-- Border Radius Option -->
        <div class="form-group mb-0 pt-2">
            <label class="form-label text-xs font-bold block">Estilo de Arredondamento dos Elementos</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button
                    type="button"
                    @click="form.border_radius = 'rounded-lg'"
                    :class="[
                        'p-2.5 text-xs font-bold border rounded-lg transition-all',
                        form.border_radius === 'rounded-lg'
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400'
                            : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'
                    ]"
                >
                    Suave
                </button>
                <button
                    type="button"
                    @click="form.border_radius = 'rounded-2xl'"
                    :class="[
                        'p-2.5 text-xs font-bold border rounded-2xl transition-all',
                        form.border_radius === 'rounded-2xl'
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400'
                            : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'
                    ]"
                >
                    Moderno (Padrão)
                </button>
                <button
                    type="button"
                    @click="form.border_radius = 'rounded-3xl'"
                    :class="[
                        'p-2.5 text-xs font-bold border rounded-3xl transition-all',
                        form.border_radius === 'rounded-3xl'
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400'
                            : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'
                    ]"
                >
                    Curvado
                </button>
                <button
                    type="button"
                    @click="form.border_radius = 'rounded-full'"
                    :class="[
                        'p-2.5 text-xs font-bold border rounded-full transition-all',
                        form.border_radius === 'rounded-full'
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400'
                            : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'
                    ]"
                >
                    Pílula
                </button>
            </div>
        </div>
    </div>
</template>
