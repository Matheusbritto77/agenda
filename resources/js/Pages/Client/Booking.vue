<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import BookingStepperNav from './Booking/BookingStepperNav.vue';
import BookingProfessionalStep from './Booking/BookingProfessionalStep.vue';
import BookingServiceStep from './Booking/BookingServiceStep.vue';
import BookingDateTimeStep from './Booking/BookingDateTimeStep.vue';
import BookingConfirmStep from './Booking/BookingConfirmStep.vue';
import BookingPixModal from './Booking/BookingPixModal.vue';

const page = usePage();

const props = defineProps({
    services: {
        type: Array,
        default: () => [],
    },
    blockedSlots: {
        type: Array,
        default: () => [],
    },
    teamMembers: {
        type: Array,
        default: () => [],
    },
    selectedProfessional: {
        type: Object,
        default: null,
    },
    hasTeam: {
        type: Boolean,
        default: false,
    },
    isOwnerPage: {
        type: Boolean,
        default: false,
    },
    bookingSuccess: {
        type: Object,
        default: null,
    },
    paymentEnabled: {
        type: Boolean,
        default: false,
    },
    paymentGateway: {
        type: String,
        default: 'mercadopago',
    },
    branding: {
        type: Object,
        default: null,
    },
    company: {
        type: Object,
        default: null,
    },
    tenant: {
        type: Object,
        default: null,
    },
});

const businessDisplayName = computed(() => {
    return props.branding?.settings?.business_name || props.company?.name || 'Agendae';
});

const pageTitle = computed(() => {
    return `Agendamento - ${businessDisplayName.value}`;
});

const showProfessionalStep = computed(() => {
    return props.hasTeam && !props.selectedProfessional;
});

const stepType = {
    professional: 1,
    service: 2,
    datetime: 3,
    confirm: 4,
};

const currentStep = ref(showProfessionalStep.value ? stepType.professional : stepType.service);
const chosenProfessionalId = ref(props.selectedProfessional?.id || null);
const selectedServiceId = ref(null);
const selectedService = ref(null);
const professionalSearchQuery = ref('');
const serviceSearchQuery = ref('');

const currentDate = ref(new Date());
const selectedDate = ref('');
const selectedTime = ref('');
const availableSlots = ref([]);
const slotsLoading = ref(false);

const isPaying = ref(false);
const paymentLoading = ref(false);
const paymentDetails = ref(null);
const paymentStatus = ref('pending');
let paymentPollInterval = null;

const bookingForm = useForm({
    professional_id: props.selectedProfessional?.id || null,
    service_id: '',
    appointment_date: '',
    appointment_time: '',
    client_name: '',
    client_email: '',
    client_phone: '',
    notes: '',
    payment_method: 'in_person',
});

const filteredProfessionals = computed(() => {
    if (!professionalSearchQuery.value) return props.teamMembers;
    const q = professionalSearchQuery.value.toLowerCase();
    return props.teamMembers.filter(p =>
        (p.name && p.name.toLowerCase().includes(q)) ||
        (p.job_title && p.job_title.toLowerCase().includes(q))
    );
});

const filteredServices = computed(() => {
    let list = props.services;
    if (chosenProfessionalId.value && props.hasTeam) {
        const pro = props.teamMembers.find(m => m.id === chosenProfessionalId.value) || props.selectedProfessional;
        if (pro && pro.services && pro.services.length > 0) {
            list = list.filter(s => pro.services.includes(s.id) || pro.services.includes(String(s.id)));
        }
    }
    if (serviceSearchQuery.value) {
        const q = serviceSearchQuery.value.toLowerCase();
        list = list.filter(s => s.name.toLowerCase().includes(q) || (s.description && s.description.toLowerCase().includes(q)));
    }
    return list;
});

const monthTitle = computed(() => {
    return currentDate.value.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
});

const canPrevMonth = computed(() => {
    const now = new Date();
    return currentDate.value.getFullYear() > now.getFullYear() ||
        (currentDate.value.getFullYear() === now.getFullYear() && currentDate.value.getMonth() > now.getMonth());
});

const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDay = firstDay.getDay();
    const totalDays = lastDay.getDate();
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const days = [];
    for (let i = startDay - 1; i >= 0; i--) {
        const d = new Date(year, month, -i);
        days.push({ day: d.getDate(), dateStr: d.toISOString().split('T')[0], otherMonth: true, disabled: true });
    }
    for (let d = 1; d <= totalDays; d++) {
        const dateObj = new Date(year, month, d);
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const isPast = dateObj < today;
        days.push({
            day: d,
            dateStr,
            otherMonth: false,
            disabled: isPast,
            isToday: dateObj.getTime() === today.getTime(),
            selected: selectedDate.value === dateStr,
        });
    }
    return days;
});

const prevMonth = () => {
    if (!canPrevMonth.value) return;
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
};

const selectProfessional = (pro) => {
    chosenProfessionalId.value = pro.id;
    bookingForm.professional_id = pro.id;
    currentStep.value = stepType.service;
};

const selectService = (svc) => {
    selectedServiceId.value = svc.id;
    selectedService.value = svc;
    bookingForm.service_id = svc.id;
    currentStep.value = stepType.datetime;
};

const selectDate = (day) => {
    selectedDate.value = day.dateStr;
    bookingForm.appointment_date = day.dateStr;
    selectedTime.value = '';
    fetchSlots(day.dateStr);
};

const selectTime = (time) => {
    selectedTime.value = time;
    bookingForm.appointment_time = time;
    currentStep.value = stepType.confirm;
};

const goToStep = (step) => {
    if (step === stepType.service && showProfessionalStep.value && !chosenProfessionalId.value) return;
    if (step === stepType.datetime && !selectedServiceId.value) return;
    if (step === stepType.confirm && (!selectedDate.value || !selectedTime.value)) return;
    currentStep.value = step;
};

const fetchSlots = (dateStr) => {
    slotsLoading.value = true;
    const url = route('public.slots');
    fetch(`${url}?date=${dateStr}&service_id=${selectedServiceId.value || ''}&professional_id=${chosenProfessionalId.value || ''}`)
        .then(res => res.json())
        .then(data => {
            availableSlots.value = data.slots || [];
            slotsLoading.value = false;
        })
        .catch(() => {
            availableSlots.value = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];
            slotsLoading.value = false;
        });
};

const submitBooking = (withPayment) => {
    bookingForm.payment_method = withPayment ? 'pix' : 'in_person';
    bookingForm.post(route('public.booking.store'), {
        preserveScroll: true,
        onSuccess: (resp) => {
            if (withPayment && resp.props.paymentDetails) {
                paymentDetails.value = resp.props.paymentDetails;
                isPaying.value = true;
                pollPaymentStatus(resp.props.paymentDetails.payment_id);
            }
        },
    });
};

const pollPaymentStatus = (paymentId) => {
    if (!paymentId) return;
    clearInterval(paymentPollInterval);
    paymentPollInterval = setInterval(() => {
        fetch(route('public.payment.status', paymentId))
            .then(r => r.json())
            .then(d => {
                paymentStatus.value = d.status;
                if (d.status === 'approved') {
                    clearInterval(paymentPollInterval);
                    setTimeout(() => { isPaying.value = false; }, 2000);
                }
            });
    }, 4000);
};

onMounted(() => {
    if (props.services.length > 0 && !selectedServiceId.value) {
        // Ready
    }
});
</script>

<template>
    <PublicLayout :branding="branding" :company="company" :title="pageTitle">
        <Head :title="pageTitle" />

        <div class="max-w-[960px] w-full mx-auto px-3 sm:px-4 py-4 space-y-6">
            <!-- Cover Banner Header -->
            <div v-if="branding?.banner_url" class="mb-6 rounded-3xl overflow-hidden shadow-lg border border-slate-200/50 relative h-40 sm:h-56 w-full">
                <img :src="branding.banner_url" class="w-full h-full object-cover" :alt="businessDisplayName" />
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/35 to-transparent flex flex-col justify-end p-5 sm:p-7 text-white">
                    <span v-if="branding?.settings?.business_name" class="text-xs sm:text-sm font-bold uppercase tracking-widest text-white/85 mb-1">
                        {{ branding.settings.business_name }}
                    </span>
                    <h1 class="text-xl sm:text-3xl font-black tracking-tight drop-shadow-md">
                        {{ branding?.settings?.tagline || 'Agende seu Atendimento' }}
                    </h1>
                </div>
            </div>

            <div v-else class="text-center mb-8 pt-2">
                <div v-if="branding?.settings?.business_name" class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider mb-2" :style="{ backgroundColor: 'var(--primary-light)', color: 'var(--primary)' }">
                    <i class="fa-solid fa-store text-[10px]"></i>
                    <span>{{ branding.settings.business_name }}</span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight mb-2" style="color: var(--text-heading);">
                    Agende seu Atendimento
                </h1>
                <p class="text-xs sm:text-sm max-w-[560px] mx-auto leading-relaxed text-slate-500">
                    {{ branding?.settings?.tagline || (showProfessionalStep ? 'Escolha o profissional, selecione o serviço desejado, data e horário e confirme sua reserva em poucos segundos.' : 'Escolha o serviço desejado, selecione data e horário em tempo real e confirme sua reserva em poucos segundos.') }}
                </p>
            </div>

            <!-- Stepper Indicators -->
            <BookingStepperNav
                :current-step="currentStep"
                :step-type="stepType"
                :show-professional-step="showProfessionalStep"
                :chosen-professional-id="chosenProfessionalId"
                :selected-service-id="selectedServiceId"
                :selected-date="selectedDate"
                :selected-time="selectedTime"
                @go-to-step="goToStep"
            />

            <!-- Steps Components -->
            <div class="space-y-6">
                <!-- Step 1: Professional -->
                <BookingProfessionalStep
                    v-if="currentStep === stepType.professional && showProfessionalStep"
                    :professionals="filteredProfessionals"
                    :chosen-professional-id="chosenProfessionalId"
                    v-model:searchQuery="professionalSearchQuery"
                    @select-professional="selectProfessional"
                />

                <!-- Step 2: Service -->
                <BookingServiceStep
                    v-if="currentStep === stepType.service"
                    :services="filteredServices"
                    :selected-service-id="selectedServiceId"
                    v-model:searchQuery="serviceSearchQuery"
                    @select-service="selectService"
                />

                <!-- Step 3: DateTime -->
                <BookingDateTimeStep
                    v-if="currentStep === stepType.datetime"
                    :month-title="monthTitle"
                    :calendar-days="calendarDays"
                    :selected-date="selectedDate"
                    :selected-time="selectedTime"
                    :available-slots="availableSlots"
                    :slots-loading="slotsLoading"
                    :can-prev-month="canPrevMonth"
                    @prev-month="prevMonth"
                    @next-month="nextMonth"
                    @select-date="selectDate"
                    @select-time="selectTime"
                />

                <!-- Step 4: Confirm -->
                <BookingConfirmStep
                    v-if="currentStep === stepType.confirm"
                    :active-professional="props.teamMembers.find(m => m.id === chosenProfessionalId) || props.selectedProfessional"
                    :selected-service="selectedService"
                    :selected-date="selectedDate"
                    :selected-time="selectedTime"
                    :booking-form="bookingForm"
                    :payment-enabled="paymentEnabled"
                    @prev-step="currentStep = stepType.datetime"
                    @submit-booking="submitBooking"
                />
            </div>
        </div>

        <!-- PIX Payment Modal -->
        <BookingPixModal
            :show="isPaying"
            :payment-loading="paymentLoading"
            :payment-details="paymentDetails"
            :payment-status="paymentStatus"
            :selected-service="selectedService"
            @close="isPaying = false"
            @fallback-store="isPaying = false"
        />
    </PublicLayout>
</template>
