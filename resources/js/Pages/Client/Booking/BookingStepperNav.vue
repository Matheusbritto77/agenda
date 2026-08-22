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
    <div class="mb-8 overflow-x-auto pb-2 scrollbar-none">
        <div class="flex items-center justify-between min-w-[340px] max-w-[720px] mx-auto relative px-4">
            <!-- Connecting Line Background -->
            <div class="absolute left-8 right-8 top-1/2 -translate-y-1/2 h-1 -z-0 rounded-full opacity-30" :style="{ backgroundColor: 'var(--text-muted, #94a3b8)' }"></div>

            <!-- Step: Professional (if applicable) -->
            <div
                v-if="showProfessionalStep"
                class="flex flex-col items-center gap-1.5 relative z-10 cursor-pointer group"
                @click="$emit('go-to-step', stepType.professional)"
            >
                <div
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm transition-all shadow-md"
                    :style="[
                        chosenProfessionalId !== undefined ? {
                            backgroundColor: 'var(--primary)',
                            color: 'var(--btn-text, #ffffff)'
                        } : {
                            backgroundColor: 'var(--surface)',
                            borderColor: 'var(--border)',
                            color: 'var(--text-muted)'
                        },
                        currentStep === stepType.professional ? {
                            boxShadow: '0 0 0 4px var(--primary-light)'
                        } : {}
                    ]"
                >
                    <i v-if="chosenProfessionalId !== undefined && currentStep > stepType.professional" class="fa-solid fa-check text-xs"></i>
                    <span v-else>1</span>
                </div>
                <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider" :style="{ color: currentStep === stepType.professional ? 'var(--primary)' : 'var(--text-muted)' }">
                    Profissional
                </span>
            </div>

            <!-- Step: Service -->
            <div
                class="flex flex-col items-center gap-1.5 relative z-10 cursor-pointer group"
                @click="$emit('go-to-step', stepType.service)"
            >
                <div
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm transition-all shadow-md"
                    :style="[
                        selectedServiceId ? {
                            backgroundColor: 'var(--primary)',
                            color: 'var(--btn-text, #ffffff)'
                        } : {
                            backgroundColor: 'var(--surface)',
                            borderColor: 'var(--border)',
                            color: 'var(--text-muted)'
                        },
                        currentStep === stepType.service ? {
                            boxShadow: '0 0 0 4px var(--primary-light)'
                        } : {}
                    ]"
                >
                    <i v-if="selectedServiceId && currentStep > stepType.service" class="fa-solid fa-check text-xs"></i>
                    <span v-else>{{ showProfessionalStep ? '2' : '1' }}</span>
                </div>
                <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider" :style="{ color: currentStep === stepType.service ? 'var(--primary)' : 'var(--text-muted)' }">
                    Serviço
                </span>
            </div>

            <!-- Step: Date -->
            <div
                class="flex flex-col items-center gap-1.5 relative z-10 cursor-pointer group"
                @click="$emit('go-to-step', stepType.date)"
            >
                <div
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm transition-all shadow-md"
                    :style="[
                        selectedDate ? {
                            backgroundColor: 'var(--primary)',
                            color: 'var(--btn-text, #ffffff)'
                        } : {
                            backgroundColor: 'var(--surface)',
                            borderColor: 'var(--border)',
                            color: 'var(--text-muted)'
                        },
                        currentStep === stepType.date ? {
                            boxShadow: '0 0 0 4px var(--primary-light)'
                        } : {}
                    ]"
                >
                    <i v-if="selectedDate && currentStep > stepType.date" class="fa-solid fa-check text-xs"></i>
                    <span v-else>{{ showProfessionalStep ? '3' : '2' }}</span>
                </div>
                <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider" :style="{ color: currentStep === stepType.date ? 'var(--primary)' : 'var(--text-muted)' }">
                    Data
                </span>
            </div>

            <!-- Step: Time -->
            <div
                class="flex flex-col items-center gap-1.5 relative z-10 cursor-pointer group"
                @click="$emit('go-to-step', stepType.time)"
            >
                <div
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm transition-all shadow-md"
                    :style="[
                        selectedTime ? {
                            backgroundColor: 'var(--primary)',
                            color: 'var(--btn-text, #ffffff)'
                        } : {
                            backgroundColor: 'var(--surface)',
                            borderColor: 'var(--border)',
                            color: 'var(--text-muted)'
                        },
                        currentStep === stepType.time ? {
                            boxShadow: '0 0 0 4px var(--primary-light)'
                        } : {}
                    ]"
                >
                    <i v-if="selectedTime && currentStep > stepType.time" class="fa-solid fa-check text-xs"></i>
                    <span v-else>{{ showProfessionalStep ? '4' : '3' }}</span>
                </div>
                <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider" :style="{ color: currentStep === stepType.time ? 'var(--primary)' : 'var(--text-muted)' }">
                    Horário
                </span>
            </div>

            <!-- Step: Confirm -->
            <div
                class="flex flex-col items-center gap-1.5 relative z-10 cursor-pointer group"
                @click="$emit('go-to-step', stepType.confirm)"
            >
                <div
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black text-xs sm:text-sm transition-all shadow-md"
                    :style="[
                        currentStep === stepType.confirm ? {
                            backgroundColor: 'var(--primary)',
                            color: 'var(--btn-text, #ffffff)',
                            boxShadow: '0 0 0 4px var(--primary-light)'
                        } : {
                            backgroundColor: 'var(--surface)',
                            borderColor: 'var(--border)',
                            color: 'var(--text-muted)'
                        }
                    ]"
                >
                    <span>{{ showProfessionalStep ? '5' : '4' }}</span>
                </div>
                <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider" :style="{ color: currentStep === stepType.confirm ? 'var(--primary)' : 'var(--text-muted)' }">
                    Confirmar
                </span>
            </div>
        </div>
    </div>
</template>
