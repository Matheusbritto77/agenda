<script setup>
defineProps({
    currentStep: {
        type: Number,
        required: true,
    },
    stepType: {
        type: Object,
        required: true,
    },
    showProfessionalStep: {
        type: Boolean,
        default: false,
    },
    chosenProfessionalId: {
        type: [Number, String],
        default: null,
    },
    selectedServiceId: {
        type: [Number, String],
        default: null,
    },
    selectedDate: {
        type: String,
        default: '',
    },
    selectedTime: {
        type: String,
        default: '',
    },
});

defineEmits(['go-to-step']);
</script>

<template>
    <div class="mb-8 overflow-x-auto pb-2">
        <div class="flex items-center justify-between min-w-[320px] max-w-[650px] mx-auto relative px-4">
            <!-- Connecting Line Background -->
            <div class="absolute left-8 right-8 top-1/2 -translate-y-1/2 h-1 bg-slate-200 dark:bg-slate-800 -z-0 rounded-full"></div>

            <!-- Step: Professional (if applicable) -->
            <div
                v-if="showProfessionalStep"
                class="flex flex-col items-center gap-1.5 relative z-10 cursor-pointer group"
                @click="$emit('go-to-step', stepType.professional)"
            >
                <div
                    :class="[
                        'w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm transition-all shadow-md',
                        currentStep === stepType.professional ? 'ring-4 ring-indigo-500/20 scale-110' : '',
                        chosenProfessionalId ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                    ]"
                    :style="chosenProfessionalId ? { backgroundColor: 'var(--primary)' } : {}"
                >
                    <i v-if="chosenProfessionalId && currentStep > stepType.professional" class="fa-solid fa-check text-xs"></i>
                    <span v-else>1</span>
                </div>
                <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                    Profissional
                </span>
            </div>

            <!-- Step: Service -->
            <div
                class="flex flex-col items-center gap-1.5 relative z-10 cursor-pointer group"
                @click="$emit('go-to-step', stepType.service)"
            >
                <div
                    :class="[
                        'w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm transition-all shadow-md',
                        currentStep === stepType.service ? 'ring-4 ring-indigo-500/20 scale-110' : '',
                        selectedServiceId ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                    ]"
                    :style="selectedServiceId ? { backgroundColor: 'var(--primary)' } : {}"
                >
                    <i v-if="selectedServiceId && currentStep > stepType.service" class="fa-solid fa-check text-xs"></i>
                    <span v-else>{{ showProfessionalStep ? '2' : '1' }}</span>
                </div>
                <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                    Serviço
                </span>
            </div>

            <!-- Step: DateTime -->
            <div
                class="flex flex-col items-center gap-1.5 relative z-10 cursor-pointer group"
                @click="$emit('go-to-step', stepType.datetime)"
            >
                <div
                    :class="[
                        'w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm transition-all shadow-md',
                        currentStep === stepType.datetime ? 'ring-4 ring-indigo-500/20 scale-110' : '',
                        (selectedDate && selectedTime) ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                    ]"
                    :style="(selectedDate && selectedTime) ? { backgroundColor: 'var(--primary)' } : {}"
                >
                    <i v-if="(selectedDate && selectedTime) && currentStep > stepType.datetime" class="fa-solid fa-check text-xs"></i>
                    <span v-else>{{ showProfessionalStep ? '3' : '2' }}</span>
                </div>
                <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                    Horário
                </span>
            </div>

            <!-- Step: Confirm -->
            <div
                class="flex flex-col items-center gap-1.5 relative z-10 cursor-pointer group"
                @click="$emit('go-to-step', stepType.confirm)"
            >
                <div
                    :class="[
                        'w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm transition-all shadow-md',
                        currentStep === stepType.confirm ? 'ring-4 ring-indigo-500/20 scale-110 bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                    ]"
                    :style="currentStep === stepType.confirm ? { backgroundColor: 'var(--primary)' } : {}"
                >
                    <span>{{ showProfessionalStep ? '4' : '3' }}</span>
                </div>
                <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                    Confirmar
                </span>
            </div>
        </div>
    </div>
</template>
