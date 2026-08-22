<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const colorPresets = [
    {
        name: 'Indigo Moderno',
        desc: 'Padrão profissional',
        top_menu_color: '#ffffff',
        background_color: '#f8fafc',
        primary_color: '#6366f1',
        secondary_color: '#4f46e5',
        card_bg_color: '#ffffff',
        text_color: '#0f172a',
        button_text_color: '#ffffff',
    },
    {
        name: 'Dark Luxury & Gold',
        desc: 'Elegante & Premium',
        top_menu_color: '#111827',
        background_color: '#030712',
        primary_color: '#eab308',
        secondary_color: '#ca8a04',
        card_bg_color: '#111827',
        text_color: '#f9fafb',
        button_text_color: '#000000',
    },
    {
        name: 'Esmeralda Saúde',
        desc: 'Fresco & Confiável',
        top_menu_color: '#ffffff',
        background_color: '#f0fdf4',
        primary_color: '#10b981',
        secondary_color: '#059669',
        card_bg_color: '#ffffff',
        text_color: '#064e3b',
        button_text_color: '#ffffff',
    },
    {
        name: 'Sunset Rose',
        desc: 'Vibrante & Estética',
        top_menu_color: '#ffffff',
        background_color: '#fff1f2',
        primary_color: '#f43f5e',
        secondary_color: '#e11d48',
        card_bg_color: '#ffffff',
        text_color: '#1e293b',
        button_text_color: '#ffffff',
    },
    {
        name: 'Minimal Dark Slate',
        desc: 'Sóbrio & Tecnológico',
        top_menu_color: '#1e293b',
        background_color: '#0f172a',
        primary_color: '#38bdf8',
        secondary_color: '#0284c7',
        card_bg_color: '#1e293b',
        text_color: '#f8fafc',
        button_text_color: '#0f172a',
    },
    {
        name: 'Royal Violet',
        desc: 'Sofisticado & Criativo',
        top_menu_color: '#ffffff',
        background_color: '#faf5ff',
        primary_color: '#8b5cf6',
        secondary_color: '#7c3aed',
        card_bg_color: '#ffffff',
        text_color: '#2e1065',
        button_text_color: '#ffffff',
    },
];

const applyPreset = (preset) => {
    props.form.top_menu_color = preset.top_menu_color;
    props.form.background_color = preset.background_color;
    props.form.primary_color = preset.primary_color;
    props.form.secondary_color = preset.secondary_color;
    props.form.card_bg_color = preset.card_bg_color;
    props.form.text_color = preset.text_color;
    props.form.button_text_color = preset.button_text_color;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Paletas Predefinidas -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="form-label text-xs font-bold block mb-0">Paletas Prontas (1 Clique)</label>
                <span class="text-[10px] text-slate-400">Clique para aplicar a combinação</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                <button
                    v-for="preset in colorPresets"
                    :key="preset.name"
                    type="button"
                    @click="applyPreset(preset)"
                    class="p-2.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-indigo-500/50 dark:hover:border-indigo-500/50 transition-all text-left group cursor-pointer shadow-2xs hover:shadow-sm"
                >
                    <div class="flex items-center gap-1.5 mb-2">
                        <div class="w-4 h-4 rounded-full border border-black/10 shadow-2xs" :style="{ backgroundColor: preset.primary_color }"></div>
                        <div class="w-4 h-4 rounded-full border border-black/10 shadow-2xs" :style="{ backgroundColor: preset.background_color }"></div>
                        <div class="w-4 h-4 rounded-full border border-black/10 shadow-2xs" :style="{ backgroundColor: preset.top_menu_color }"></div>
                    </div>
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate group-hover:text-indigo-600 transition-colors">
                        {{ preset.name }}
                    </p>
                    <span class="text-[10px] text-slate-400 block truncate">{{ preset.desc }}</span>
                </button>
            </div>
        </div>

        <!-- Color Pickers Customization Grid -->
        <div class="space-y-3">
            <label class="form-label text-xs font-bold block mb-0">Personalização Individual de Cores</label>

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

                <!-- Primary Color -->
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
                <div class="form-group mb-0">
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

                <!-- Button Text Color -->
                <div class="form-group mb-0">
                    <label class="form-label text-xs font-bold block" for="button_text_color">Cor do Texto dentro dos Botões</label>
                    <div class="flex gap-2">
                        <input
                            type="color"
                            id="button_text_color_picker"
                            v-model="form.button_text_color"
                            class="w-10 h-10 border rounded-xl cursor-pointer shrink-0"
                        />
                        <input
                            type="text"
                            id="button_text_color"
                            v-model="form.button_text_color"
                            class="form-control text-xs sm:text-sm rounded-xl uppercase"
                            maxlength="7"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Border Radius Option -->
        <div class="space-y-3 pt-2 border-t" style="border-color: var(--border);">
            <label class="form-label text-xs font-bold block mb-0">Estilo de Arredondamento dos Elementos</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button
                    type="button"
                    @click="form.border_radius = 'rounded-lg'"
                    :class="[
                        'p-2.5 text-xs font-bold border rounded-lg transition-all cursor-pointer',
                        form.border_radius === 'rounded-lg'
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 shadow-xs'
                            : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'
                    ]"
                >
                    Suave
                </button>
                <button
                    type="button"
                    @click="form.border_radius = 'rounded-2xl'"
                    :class="[
                        'p-2.5 text-xs font-bold border rounded-2xl transition-all cursor-pointer',
                        form.border_radius === 'rounded-2xl'
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 shadow-xs'
                            : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'
                    ]"
                >
                    Moderno (Padrão)
                </button>
                <button
                    type="button"
                    @click="form.border_radius = 'rounded-3xl'"
                    :class="[
                        'p-2.5 text-xs font-bold border rounded-3xl transition-all cursor-pointer',
                        form.border_radius === 'rounded-3xl'
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 shadow-xs'
                            : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'
                    ]"
                >
                    Curvado
                </button>
                <button
                    type="button"
                    @click="form.border_radius = 'rounded-full'"
                    :class="[
                        'p-2.5 text-xs font-bold border rounded-full transition-all cursor-pointer',
                        form.border_radius === 'rounded-full'
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 shadow-xs'
                            : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'
                    ]"
                >
                    Pílula
                </button>
            </div>
        </div>
    </div>
</template>
