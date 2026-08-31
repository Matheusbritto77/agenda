<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    countryCode: {
        type: String,
        default: 'BR',
    },
    label: {
        type: String,
        default: 'WhatsApp / Telefone',
    },
    error: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: '(00) 00000-0000',
    },
    id: {
        type: String,
        default: 'phone-input',
    },
});

const emit = defineEmits(['update:modelValue', 'update:countryCode']);

const countries = [
    { code: 'BR', name: 'Brasil', ddi: '+55', flag: '🇧🇷', mask: '(##) #####-####' },
    { code: 'US', name: 'Estados Unidos', ddi: '+1', flag: '🇺🇸', mask: '(###) ###-####' },
    { code: 'PT', name: 'Portugal', ddi: '+351', flag: '🇵🇹', mask: '### ### ###' },
    { code: 'ES', name: 'Espanha', ddi: '+34', flag: '🇪🇸', mask: '### ## ## ##' },
    { code: 'AR', name: 'Argentina', ddi: '+54', flag: '🇦🇷', mask: '## ####-####' },
    { code: 'UY', name: 'Uruguai', ddi: '+598', flag: '🇺🇾', mask: '# ### ####' },
    { code: 'PY', name: 'Paraguai', ddi: '+595', flag: '🇵🇾', mask: '### ### ###' },
    { code: 'CL', name: 'Chile', ddi: '+56', flag: '🇨🇱', mask: '# #### ####' },
    { code: 'FR', name: 'França', ddi: '+33', flag: '🇫🇷', mask: '# ## ## ## ##' },
    { code: 'IT', name: 'Itália', ddi: '+39', flag: '🇮🇹', mask: '### #######' },
    { code: 'DE', name: 'Alemanha', ddi: '+49', flag: '🇩🇪', mask: '#### ########' },
    { code: 'GB', name: 'Reino Unido', ddi: '+44', flag: '🇬🇧', mask: '##### ######' },
];

const selectedCountry = ref(props.countryCode || 'BR');
const rawPhone = ref(props.modelValue || '');

const currentCountryObj = computed(() => {
    return countries.find((c) => c.code === selectedCountry.value) || countries[0];
});

const formatPhone = (value, country) => {
    if (!value) return '';
    const digits = value.replace(/\D/g, '');

    if (country === 'BR') {
        if (digits.length <= 2) {
            return digits.length ? `(${digits}` : '';
        }
        if (digits.length <= 6) {
            return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
        }
        if (digits.length <= 10) {
            return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
        }
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7, 11)}`;
    }

    return digits;
};

const onInput = (e) => {
    const val = e.target.value;
    const formatted = formatPhone(val, selectedCountry.value);
    rawPhone.value = formatted;
    emit('update:modelValue', formatted);
};

const onCountryChange = (e) => {
    selectedCountry.value = e.target.value;
    emit('update:countryCode', selectedCountry.value);
    const formatted = formatPhone(rawPhone.value, selectedCountry.value);
    rawPhone.value = formatted;
    emit('update:modelValue', formatted);
};

watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal !== rawPhone.value) {
            rawPhone.value = formatPhone(newVal, selectedCountry.value);
        }
    }
);

watch(
    () => props.countryCode,
    (newVal) => {
        if (newVal && newVal !== selectedCountry.value) {
            selectedCountry.value = newVal;
        }
    }
);
</script>

<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="id" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>

        <div class="relative flex rounded-xl border shadow-sm transition-all focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500"
             :class="error ? 'border-rose-300 dark:border-rose-700 bg-rose-50/30' : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900'">
            <!-- Country Selector -->
            <div class="relative flex items-center border-r border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 rounded-l-xl px-2.5">
                <span class="text-base mr-1.5">{{ currentCountryObj.flag }}</span>
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ currentCountryObj.ddi }}</span>
                <select
                    :value="selectedCountry"
                    @change="onCountryChange"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer text-xs"
                    title="Selecione o país"
                >
                    <option v-for="c in countries" :key="c.code" :value="c.code">
                        {{ c.flag }} {{ c.name }} ({{ c.ddi }})
                    </option>
                </select>
                <i class="fa-solid fa-chevron-down text-[9px] ml-1 text-slate-400 pointer-events-none"></i>
            </div>

            <!-- Phone Input -->
            <input
                :id="id"
                type="tel"
                :value="rawPhone"
                @input="onInput"
                :placeholder="placeholder"
                :required="required"
                class="block w-full rounded-r-xl border-0 bg-transparent py-2.5 px-3 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-0 focus:outline-none"
            />
        </div>

        <p v-if="error" class="text-xs font-medium text-rose-500">{{ error }}</p>
    </div>
</template>
