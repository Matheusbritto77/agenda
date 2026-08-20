<script setup>
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import axios from 'axios';

const props = defineProps({
    services: {
        type: Array,
        default: () => []
    },
    blockedSlots: {
        type: Array,
        default: () => []
    },
    teamMembers: {
        type: Array,
        default: () => []
    },
    selectedProfessional: {
        type: Object,
        default: null
    },
    hasTeam: {
        type: Boolean,
        default: false
    },
    isOwnerPage: {
        type: Boolean,
        default: false
    },
    bookingSuccess: {
        type: Object,
        default: null
    },
    paymentEnabled: {
        type: Boolean,
        default: false
    },
    paymentGateway: {
        type: String,
        default: 'mercadopago'
    },
    branding: {
        type: Object,
        default: null
    }
});

// --- State ---
const searchQuery = ref('');
const professionalSearchQuery = ref('');
const selectedServiceId = ref(null);
const currentMonth = ref(new Date());
const selectedDate = ref(null);
const selectedTime = ref(null);
const slotsLoading = ref(false);
const availableSlots = ref([]);
const showSuccess = ref(!!props.bookingSuccess);

// Professional selection (only used on owner page with team)
const chosenProfessionalId = ref(null);

// Whether we should show the professional selection step
const showProfessionalStep = computed(() => {
    return props.isOwnerPage && props.hasTeam && props.teamMembers.length > 0 && !props.selectedProfessional;
});

// Dynamic steps
const steps = computed(() => {
    if (showProfessionalStep.value) {
        return [
            { num: 1, label: 'Profissional', sublabel: 'Quem atende?', icon: 'fa-solid fa-user-check' },
            { num: 2, label: 'Serviço', sublabel: 'O que deseja?', icon: 'fa-solid fa-scissors' },
            { num: 3, label: 'Data & Hora', sublabel: 'Quando deseja?', icon: 'fa-solid fa-calendar-days' },
            { num: 4, label: 'Confirmar', sublabel: 'Seus dados', icon: 'fa-solid fa-circle-check' }
        ];
    }
    return [
        { num: 1, label: 'Serviço', sublabel: 'O que deseja?', icon: 'fa-solid fa-scissors' },
        { num: 2, label: 'Data & Hora', sublabel: 'Quando deseja?', icon: 'fa-solid fa-calendar-days' },
        { num: 3, label: 'Confirmar', sublabel: 'Seus dados', icon: 'fa-solid fa-circle-check' }
    ];
});

// Logical step mapping: what "type" of step is the current number
const stepType = computed(() => {
    if (showProfessionalStep.value) {
        return { professional: 1, service: 2, datetime: 3, confirm: 4 };
    }
    return { professional: null, service: 1, datetime: 2, confirm: 3 };
});

const currentStep = ref(1);

const stepperProgress = computed(() => {
    return ((currentStep.value - 1) / (steps.value.length - 1)) * 100 + '%';
});

// Currently active professional (either chosen by client or pre-selected from backend)
const activeProfessional = computed(() => {
    if (props.selectedProfessional) return props.selectedProfessional;
    if (chosenProfessionalId.value && props.teamMembers.length > 0) {
        return props.teamMembers.find(m => m.id === chosenProfessionalId.value) || null;
    }
    return null;
});

// Filter team members by search
const filteredProfessionals = computed(() => {
    if (!professionalSearchQuery.value) return props.teamMembers;
    const q = professionalSearchQuery.value.toLowerCase();
    return props.teamMembers.filter(m =>
        (m.name || '').toLowerCase().includes(q) ||
        (m.job_title || '').toLowerCase().includes(q) ||
        (m.role_title || '').toLowerCase().includes(q)
    );
});

// Filter services: if a professional is chosen, only show their linked services
const filteredServices = computed(() => {
    let pool = props.services;
    if (activeProfessional.value && activeProfessional.value.services && activeProfessional.value.services.length > 0) {
        const professionalServiceIds = activeProfessional.value.services.map(id => Number(id));
        pool = props.services.filter(s => professionalServiceIds.includes(s.id));
    }
    if (!searchQuery.value) return pool;
    const q = searchQuery.value.toLowerCase();
    return pool.filter(s =>
        (s.name || '').toLowerCase().includes(q) ||
        (s.description || '').toLowerCase().includes(q)
    );
});

const selectedService = computed(() => {
    return props.services.find(s => s.id === selectedServiceId.value) || null;
});

const calendarDays = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startWeekday = firstDay.getDay();
    const daysInMonth = lastDay.getDate();

    const days = [];
    for (let i = 0; i < startWeekday; i++) {
        const d = new Date(year, month, i - startWeekday + 1);
        days.push({ date: d, day: d.getDate(), otherMonth: true });
    }
    for (let i = 1; i <= daysInMonth; i++) {
        days.push({ date: new Date(year, month, i), day: i, otherMonth: false });
    }
    while (days.length % 7 !== 0) {
        const last = days[days.length - 1].date;
        const next = new Date(last);
        next.setDate(next.getDate() + 1);
        days.push({ date: next, day: next.getDate(), otherMonth: true });
    }
    return days;
});

const monthTitle = computed(() => {
    return currentMonth.value.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
});

const todayStr = computed(() => new Date().toISOString().split('T')[0]);

const bookingForm = useForm({
    service_id: '',
    professional_id: '',
    team_member_id: '',
    appointment_date: '',
    appointment_time: '',
    client_name: '',
    client_email: '',
    client_phone: '',
    notes: ''
});

// --- Actions ---
const selectProfessional = (id) => {
    chosenProfessionalId.value = id;
    bookingForm.professional_id = id;
    bookingForm.team_member_id = id;
    // Reset service selection when changing professional
    selectedServiceId.value = null;
    bookingForm.service_id = '';
    selectedDate.value = null;
    selectedTime.value = null;
};

const selectService = (id) => {
    selectedServiceId.value = id;
    bookingForm.service_id = id;
};

const nextStep = () => {
    const st = stepType.value;
    if (currentStep.value === st.professional && !chosenProfessionalId.value) return;
    if (currentStep.value === st.service && !selectedServiceId.value) return;
    if (currentStep.value === st.datetime && (!selectedDate.value || !selectedTime.value)) return;
    if (currentStep.value < steps.value.length) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const goToStep = (num) => {
    const st = stepType.value;
    if (num === 1) { currentStep.value = 1; return; }
    if (showProfessionalStep.value) {
        if (num === 2 && chosenProfessionalId.value) { currentStep.value = 2; return; }
        if (num === 3 && selectedServiceId.value) { currentStep.value = 3; return; }
    } else {
        if (num === 2 && selectedServiceId.value) { currentStep.value = 2; return; }
    }
};

const prevMonth = () => {
    const d = new Date(currentMonth.value);
    d.setMonth(d.getMonth() - 1);
    currentMonth.value = d;
};

const nextMonth = () => {
    const d = new Date(currentMonth.value);
    d.setMonth(d.getMonth() + 1);
    currentMonth.value = d;
};

const isDateDisabled = (dateObj) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const d = new Date(dateObj.date);
    d.setHours(0, 0, 0, 0);
    return d < today;
};

const isDateSelected = (dateObj) => {
    if (!selectedDate.value) return false;
    return selectedDate.value === dateObj.date.toISOString().split('T')[0];
};

const isToday = (dateObj) => {
    return todayStr.value === dateObj.date.toISOString().split('T')[0];
};

const selectDate = (dateObj) => {
    if (dateObj.otherMonth || isDateDisabled(dateObj)) return;
    selectedDate.value = dateObj.date.toISOString().split('T')[0];
    selectedTime.value = null;
    bookingForm.appointment_date = selectedDate.value;
    fetchAvailableSlots();
};

const fetchAvailableSlots = () => {
    if (!selectedDate.value || !selectedServiceId.value) return;
    slotsLoading.value = true;
    availableSlots.value = [];
    const professionalId = activeProfessional.value?.id || '';
    router.get(route('booking.slots'), {
        date: selectedDate.value,
        service_id: selectedServiceId.value,
        professional_id: professionalId
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            availableSlots.value = page.props.availableSlots || page.props.slots || [];
        },
        onFinish: () => {
            slotsLoading.value = false;
        }
    });
};

const selectTime = (time) => {
    selectedTime.value = time;
    bookingForm.appointment_time = time;
};

const isPaying = ref(false);
const paymentDetails = ref(null);
const checkStatusInterval = ref(null);
const paymentStatus = ref(null);
const paymentLoading = ref(false);
const copied = ref(false);

const successData = ref(null);
const resolvedBookingSuccess = computed(() => {
    return successData.value || props.bookingSuccess;
});

const showSuccessScreen = (appointment) => {
    const serviceName = appointment.service?.name || props.services.find(s => s.id === bookingForm.service_id)?.name || '';
    successData.value = {
        id: appointment.id,
        customer_name: appointment.client_name || bookingForm.client_name,
        service_name: serviceName,
        datetime: formatDateLong(appointment.appointment_date || bookingForm.appointment_date) + ' às ' + (appointment.appointment_time || bookingForm.appointment_time).substring(0, 5),
    };
    showSuccess.value = true;
};

const submitBooking = async (payNow = false) => {
    bookingForm.clearErrors();
    bookingForm.processing = true;

    const payload = {
        service_id: bookingForm.service_id,
        professional_id: bookingForm.professional_id,
        team_member_id: bookingForm.team_member_id,
        appointment_date: bookingForm.appointment_date,
        appointment_time: bookingForm.appointment_time,
        client_name: bookingForm.client_name,
        client_email: bookingForm.client_email,
        client_phone: bookingForm.client_phone,
        notes: bookingForm.notes,
        pay_now: payNow,
    };

    try {
        const response = await axios.post(route('booking.store'), payload, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });

        const appointment = response.data.appointment;

        if (payNow) {
            paymentLoading.value = true;
            isPaying.value = true;
            paymentStatus.value = 'pending';
            
            try {
                const payResponse = await axios.post(route('payment.pix.create'), {
                    appointment_id: appointment.id
                });
                
                paymentDetails.value = payResponse.data.payment;
                startStatusPolling(payResponse.data.payment.id);
            } catch (error) {
                console.error(error);
                alert('Erro ao gerar pagamento PIX. O agendamento foi pré-confirmado, mas o pagamento deverá ser feito no local.');
                showSuccessScreen(appointment);
            } finally {
                paymentLoading.value = false;
            }
        } else {
            showSuccessScreen(appointment);
        }
    } catch (error) {
        if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors;
            Object.keys(errors).forEach(key => {
                bookingForm.setError(key, errors[key][0]);
            });
        } else {
            alert('Não foi possível concluir o agendamento.');
        }
    } finally {
        bookingForm.processing = false;
    }
};

const startStatusPolling = (paymentId) => {
    if (checkStatusInterval.value) {
        clearInterval(checkStatusInterval.value);
    }
    
    checkStatusInterval.value = setInterval(async () => {
        try {
            const response = await axios.get(route('payment.status', paymentId));
            const status = response.data.status;
            paymentStatus.value = status;
            
            if (status === 'approved') {
                clearInterval(checkStatusInterval.value);
                setTimeout(() => {
                    isPaying.value = false;
                    showSuccessScreen({
                        id: paymentDetails.value?.appointment_id,
                        client_name: bookingForm.client_name,
                        appointment_date: bookingForm.appointment_date,
                        appointment_time: bookingForm.appointment_time,
                    });
                    paymentDetails.value = null;
                }, 1500);
            } else if (status === 'cancelled' || status === 'rejected') {
                clearInterval(checkStatusInterval.value);
            }
        } catch (error) {
            console.error('Error checking payment status:', error);
        }
    }, 5000);
};

const closePaymentAndFallback = () => {
    if (checkStatusInterval.value) {
        clearInterval(checkStatusInterval.value);
    }
    isPaying.value = false;
    showSuccessScreen({
        id: paymentDetails.value?.appointment_id,
        client_name: bookingForm.client_name,
        appointment_date: bookingForm.appointment_date,
        appointment_time: bookingForm.appointment_time,
    });
    paymentDetails.value = null;
};

const copyPixCode = () => {
    if (paymentDetails.value && paymentDetails.value.pix_qr_code) {
        navigator.clipboard.writeText(paymentDetails.value.pix_qr_code);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
};

const resetBooking = () => {
    showSuccess.value = false;
    successData.value = null;
    router.get(route('booking.index'));
};

onBeforeUnmount(() => {
    if (checkStatusInterval.value) {
        clearInterval(checkStatusInterval.value);
    }
});

const formatCurrency = (val) => {
    return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
};

const formatDateLong = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
};

const canPrevMonth = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const firstOfCurrent = new Date(today.getFullYear(), today.getMonth(), 1);
    const test = new Date(currentMonth.value);
    test.setMonth(test.getMonth() - 1);
    test.setDate(1);
    return test >= firstOfCurrent;
});

onMounted(() => {
    availableSlots.value = props.blockedSlots || [];
});
</script>

<template>
    <PublicLayout :branding="branding" title="Agendamento de Serviços - Agendae">
        <Head title="Agendamento de Serviços - Agendae" />

        <div class="max-w-[900px] w-full mx-auto px-3">

            <div v-if="showSuccess && resolvedBookingSuccess" class="mb-8 animate-[fadeInSlide_0.4s_ease]">
                <div class="rounded-2xl p-6 border border-emerald-500/35 shadow-lg" style="background: linear-gradient(135deg, rgba(16,185,129,0.12) 0%, rgba(6,182,212,0.08) 100%);">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4 pb-4 border-b border-emerald-500/20">
                        <div class="flex items-center gap-3.5 text-center md:text-left">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/40 flex items-center justify-center text-xl shrink-0 shadow-lg shadow-emerald-500/20">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20 mb-1">
                                    Agendamento Confirmado
                                </span>
                                <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white truncate">Pronto, {{ resolvedBookingSuccess.customer_name }}!</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Seu horário foi reservado com sucesso no sistema.</p>
                            </div>
                        </div>

                        <button @click="resetBooking" class="w-full md:w-auto px-4 py-2.5 rounded-xl font-semibold text-xs bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-plus"></i>
                            <span>Novo Agendamento</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 text-sm">
                        <div class="bg-white/80 dark:bg-slate-900/60 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
                            <span class="text-[11px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 block mb-1">Serviço</span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-300 text-sm flex items-center gap-2 truncate">
                                <i class="fa-solid fa-scissors text-xs shrink-0"></i>
                                {{ resolvedBookingSuccess.service_name }}
                            </span>
                        </div>

                        <div class="bg-white/80 dark:bg-slate-900/60 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
                            <span class="text-[11px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 block mb-1">Data & Horário</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm flex items-center gap-2 truncate">
                                <i class="fa-solid fa-calendar-day text-xs shrink-0"></i>
                                {{ resolvedBookingSuccess.datetime }}
                            </span>
                        </div>

                        <div class="bg-white/80 dark:bg-slate-900/60 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
                            <span class="text-[11px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 block mb-1">Status</span>
                            <span class="font-bold text-slate-900 dark:text-slate-200 text-sm flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                Confirmado
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mb-8">
                <h1 class="text-[2.25rem] font-black tracking-tight leading-tight mb-2" style="color: var(--text-heading);">
                    Agende seu Atendimento
                </h1>
                <p class="text-[0.95rem] max-w-[540px] mx-auto leading-relaxed" style="color: var(--text-muted);">
                    <template v-if="showProfessionalStep">
                        Escolha o profissional, selecione o serviço desejado, data e horário e confirme sua reserva em poucos segundos.
                    </template>
                    <template v-else>
                        Escolha o serviço desejado, selecione data e horário em tempo real e confirme sua reserva em poucos segundos.
                    </template>
                </p>
            </div>

            <!-- Professional Selected Card (when coming from team member subdomain) -->
            <div v-if="selectedProfessional && !showProfessionalStep" data-selected-professional="true" class="max-w-[640px] w-full mx-auto mb-8 animate-[fadeInSlide_0.35s_ease]">
                <div class="glass-card-3d p-5 rounded-2xl flex flex-col sm:flex-row items-center gap-4 border" style="background: var(--surface); border-color: var(--border);">
                    <div class="w-16 h-16 rounded-full overflow-hidden border-2 flex items-center justify-center shrink-0 bg-slate-100 dark:bg-slate-800" :style="{ borderColor: 'var(--primary)' }">
                        <img v-if="selectedProfessional.avatar_url" :src="selectedProfessional.avatar_url" :alt="selectedProfessional.name" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full bg-gradient-to-tr from-brand-600 to-indigo-700 text-white flex items-center justify-center font-bold text-xl">
                            {{ (selectedProfessional.name || 'P').substring(0, 2).toUpperCase() }}
                        </div>
                    </div>
                    <div class="text-center sm:text-left min-w-0 flex-1">
                        <span class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-0.5 rounded-full border mb-1" :style="{ color: 'var(--primary)', borderColor: 'var(--primary-light)', backgroundColor: 'var(--primary-light)' }">
                            Profissional Selecionado
                        </span>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white truncate">{{ selectedProfessional.name }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold">{{ selectedProfessional.job_title || selectedProfessional.role_title || 'Especialista' }}</p>
                        <p v-if="selectedProfessional.bio" class="text-xs text-slate-400 dark:text-slate-500 mt-1.5 italic line-clamp-2">{{ selectedProfessional.bio }}</p>
                    </div>
                </div>
            </div>

            <!-- Stepper -->
            <div class="flex justify-between items-center max-w-[720px] w-full mx-auto mb-9 relative px-4 sm:px-6">
                <div class="absolute top-6 left-[42px] right-[42px] h-[3px] z-10 rounded-full" style="background: rgba(148,163,184,0.25);"></div>
                <div class="absolute top-6 left-[42px] h-[3px] z-20 rounded-full transition-all duration-500" :style="{ width: stepperProgress, background: 'var(--primary-gradient)', boxShadow: '0 0 12px var(--primary-light)' }"></div>

                <div
                    v-for="s in steps"
                    :key="s.num"
                    @click="goToStep(s.num)"
                    :class="['relative z-10 flex flex-col items-center gap-[0.35rem] cursor-pointer select-none transition-all text-center', currentStep === s.num ? 'active' : '', s.num < currentStep ? 'completed' : '']"
                >
                    <div
                        :class="[
                            'w-12 h-12 rounded-full flex items-center justify-center font-black text-[1.05rem] transition-all shadow',
                            currentStep === s.num
                                ? 'text-white scale-108'
                                : s.num < currentStep
                                    ? 'bg-emerald-500 border-2 border-emerald-500 text-white'
                                    : 'border-2 text-slate-500'
                        ]"
                        :style="currentStep === s.num ? { background: 'var(--primary-gradient)', boxShadow: '0 0 0 6px var(--primary-light), 0 8px 20px var(--primary-light)' } : { backgroundColor: 'var(--surface)', borderColor: 'var(--border)' }"
                    >
                        <i v-if="s.num < currentStep" class="fa-solid fa-check text-sm"></i>
                        <span v-else>{{ s.num }}</span>
                    </div>
                    <span
                        :class="[
                            'text-[0.8rem] sm:text-[0.85rem] font-bold transition-all whitespace-nowrap',
                            s.num < currentStep ? 'text-emerald-600' : ''
                        ]"
                        :style="currentStep === s.num ? { color: 'var(--primary)' } : !(currentStep === s.num || s.num < currentStep) ? { color: 'var(--text-muted)' } : ''"
                    >
                        {{ s.label }}
                    </span>
                    <span class="text-[0.65rem] sm:text-[0.725rem] font-medium opacity-70 mt-[-0.2rem] whitespace-nowrap hidden sm:block" style="color: var(--text-muted);">
                        {{ s.sublabel }}
                    </span>
                </div>
            </div>

            <!-- Main Card -->
            <div class="rounded-2xl p-5 sm:p-7 w-full shadow-lg animate-[fadeInSlide_0.35s_ease]" style="background: var(--surface); backdrop-filter: blur(20px); border: 1px solid var(--border);">
                <div class="flex items-center justify-between mb-6 pb-4 border-b flex-wrap gap-3" style="border-color: var(--border);">
                    <h2 class="text-[1.25rem] font-black flex items-center gap-3" style="color: var(--text-heading);">
                        <template v-if="currentStep === stepType.professional">
                            <i class="fa-solid fa-user-check" :style="{ color: 'var(--primary)' }"></i>
                            Escolha o Profissional
                        </template>
                        <template v-else-if="currentStep === stepType.service">
                            <i class="fa-solid fa-scissors" :style="{ color: 'var(--primary)' }"></i>
                            Selecione o Serviço Desejado
                        </template>
                        <template v-else-if="currentStep === stepType.datetime">
                            <i class="fa-solid fa-calendar-days" :style="{ color: 'var(--primary)' }"></i>
                            Escolha Data & Horário
                        </template>
                        <template v-else-if="currentStep === stepType.confirm">
                            <i class="fa-solid fa-circle-check" :style="{ color: 'var(--primary)' }"></i>
                            Confirme seus Dados
                        </template>
                    </h2>
                </div>

                <!-- ========== STEP: PROFESSIONAL SELECTION ========== -->
                <div v-if="currentStep === stepType.professional">
                    <div class="mb-5 relative w-full">
                        <input
                            v-model="professionalSearchQuery"
                            type="text"
                            placeholder="Buscar profissional por nome ou especialidade..."
                            class="form-control w-full rounded-xl py-3 pl-11 pr-4 text-base focus:outline-none transition-all"
                        >
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-[0.9rem]" style="color: var(--text-muted);"></i>
                    </div>

                    <div v-if="filteredProfessionals.length === 0" class="text-center py-16">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl" style="background: var(--background-subtle); color: var(--text-muted); border: 1px solid var(--border);">
                            <i class="fa-solid fa-users-slash"></i>
                        </div>
                        <p class="font-bold" style="color: var(--text-heading);">Nenhum profissional encontrado</p>
                        <p class="text-xs opacity-70 mt-1">Tente ajustar os termos da busca.</p>
                    </div>

                    <div v-else class="grid gap-4 mb-7 w-full" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
                        <div
                            v-for="pro in filteredProfessionals"
                            :key="pro.id"
                            @click="selectProfessional(pro.id)"
                            :class="['rounded-2xl p-5 cursor-pointer transition-all relative flex flex-col items-center text-center overflow-hidden shadow group', chosenProfessionalId === pro.id ? 'selected' : '']"
                            :style="chosenProfessionalId === pro.id ? { borderColor: 'var(--primary)', background: 'var(--primary-light)', boxShadow: '0 0 0 2px var(--primary), 0 15px 35px var(--primary-light)', transform: 'translateY(-2px) scale(1.01)' } : { background: 'var(--surface)', border: '2px solid var(--border)' }"
                        >
                            <div v-if="chosenProfessionalId === pro.id" class="absolute top-2.5 right-2.5 w-6 h-6 rounded-full text-white flex items-center justify-center text-[0.7rem] shadow z-10" :style="{ backgroundColor: 'var(--primary)' }">
                                <i class="fa-solid fa-check"></i>
                            </div>

                            <div class="w-20 h-20 rounded-full overflow-hidden border-2 flex items-center justify-center mb-3 transition-all" :style="{ borderColor: chosenProfessionalId === pro.id ? 'var(--primary)' : 'var(--border)' }">
                                <img v-if="pro.avatar_url" :src="pro.avatar_url" :alt="pro.name" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center font-bold text-2xl" style="background: linear-gradient(135deg, var(--primary-light) 0%, rgba(99,102,241,0.15) 100%); color: var(--primary);">
                                    {{ (pro.name || 'P').substring(0, 2).toUpperCase() }}
                                </div>
                            </div>

                            <h3 class="text-[1rem] font-black mb-0.5 leading-tight truncate w-full" style="color: var(--text-heading);">
                                {{ pro.name }}
                            </h3>
                            <p class="text-[0.8rem] font-semibold mb-2 truncate w-full" style="color: var(--text-muted);">
                                {{ pro.job_title || pro.role_title || 'Especialista' }}
                            </p>
                            <p v-if="pro.bio" class="text-[0.75rem] leading-relaxed line-clamp-2 mb-3 opacity-70" style="color: var(--text-muted);">
                                {{ pro.bio }}
                            </p>

                            <div class="mt-auto w-full">
                                <div
                                    :class="['w-full py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all flex items-center justify-center gap-2']"
                                    :style="chosenProfessionalId === pro.id ? { background: 'var(--primary-gradient)', color: '#fff' } : { background: 'var(--background-subtle)', color: 'var(--text-muted)', border: '1px solid var(--border)' }"
                                >
                                    <i :class="chosenProfessionalId === pro.id ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'" class="text-xs"></i>
                                    {{ chosenProfessionalId === pro.id ? 'Selecionado' : 'Selecionar' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========== STEP: SERVICE SELECTION ========== -->
                <div v-if="currentStep === stepType.service">
                    <!-- Show chosen professional summary when on owner page -->
                    <div v-if="showProfessionalStep && activeProfessional" class="mb-5 p-3.5 rounded-xl flex items-center gap-3 border" style="background: var(--primary-light); border-color: var(--border);">
                        <div class="w-10 h-10 rounded-full overflow-hidden border-2 flex items-center justify-center shrink-0" :style="{ borderColor: 'var(--primary)' }">
                            <img v-if="activeProfessional.avatar_url" :src="activeProfessional.avatar_url" :alt="activeProfessional.name" class="w-full h-full object-cover">
                            <div v-else class="w-full h-full flex items-center justify-center font-bold text-sm" style="background: linear-gradient(135deg, var(--primary-light) 0%, rgba(99,102,241,0.15) 100%); color: var(--primary);">
                                {{ (activeProfessional.name || 'P').substring(0, 2).toUpperCase() }}
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[0.7rem] font-bold uppercase tracking-wider" :style="{ color: 'var(--primary)' }">Profissional</p>
                            <p class="text-sm font-black truncate" style="color: var(--text-heading);">{{ activeProfessional.name }}</p>
                        </div>
                        <button v-if="showProfessionalStep" @click="prevStep" class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all hover:opacity-80" :style="{ color: 'var(--primary)', background: 'var(--surface)', border: '1px solid var(--border)' }">
                            Alterar
                        </button>
                    </div>

                    <div class="mb-5 relative w-full">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar serviço por nome ou descrição..."
                            class="form-control w-full rounded-xl py-3 pl-11 pr-4 text-base focus:outline-none transition-all"
                        >
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-[0.9rem]" style="color: var(--text-muted);"></i>
                    </div>

                    <div v-if="filteredServices.length === 0" class="text-center py-16">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl" style="background: var(--background-subtle); color: var(--text-muted); border: 1px solid var(--border);">
                            <i class="fa-solid fa-inbox"></i>
                        </div>
                        <p class="font-bold" style="color: var(--text-heading);">Nenhum serviço encontrado</p>
                        <p class="text-xs opacity-70 mt-1">Tente ajustar os termos da busca.</p>
                    </div>

                    <div v-else class="grid gap-5 mb-7 w-full" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
                        <div
                            v-for="svc in filteredServices"
                            :key="svc.id"
                            @click="selectService(svc.id)"
                            :class="['rounded-2xl p-5 cursor-pointer transition-all relative flex flex-col justify-between overflow-hidden shadow', selectedServiceId === svc.id ? 'selected' : '']"
                            :style="selectedServiceId === svc.id ? { borderColor: 'var(--primary)', background: 'var(--primary-light)', boxShadow: '0 0 0 2px var(--primary), 0 15px 35px var(--primary-light)', transform: 'translateY(-2px) scale(1.01)' } : { background: 'var(--surface)', border: '2px solid var(--border)' }"
                        >
                            <div v-if="selectedServiceId === svc.id" class="absolute top-2.5 right-2.5 w-6 h-6 rounded-full text-white flex items-center justify-center text-[0.7rem] shadow z-10" :style="{ backgroundColor: 'var(--primary)' }">
                                <i class="fa-solid fa-check"></i>
                            </div>

                            <div class="w-full h-[130px] rounded-xl overflow-hidden mb-3.5 flex items-center justify-center" style="background: var(--background-subtle); border: 1px solid var(--border);">
                                <img v-if="svc.image_url" :src="svc.image_url" :alt="svc.name" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center text-5xl" style="background: linear-gradient(135deg, var(--primary-light) 0%, rgba(6,182,212,0.05) 100%);">
                                    <i class="fa-solid fa-scissors" style="color: var(--primary); opacity: 0.8;"></i>
                                </div>
                            </div>

                            <h3 class="text-[1.1rem] font-black mb-1 leading-tight" style="color: var(--text-heading);">
                                {{ svc.name }}
                            </h3>
                            <p class="text-[0.825rem] mb-4 leading-relaxed line-clamp-2" style="color: var(--text-muted);">
                                {{ svc.description || 'Serviço profissional de qualidade.' }}
                            </p>
                            <div class="flex justify-between items-center pt-3 border-t" style="border-color: var(--border);">
                                <span class="text-[1.2rem] font-black" :style="{ color: 'var(--primary)' }">
                                    R$ {{ formatCurrency(svc.price) }}
                                </span>
                                <span class="text-[0.8rem] font-semibold rounded-full px-2.5 py-1 flex items-center gap-2" style="background: var(--background-subtle); border: 1px solid var(--border); color: var(--text-muted);">
                                    <i class="fa-regular fa-clock text-xs"></i>
                                    {{ svc.duration_minutes || 30 }} min
                                </span>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- ========== STEP: DATE & TIME ========== -->
                <div v-if="currentStep === stepType.datetime">
                    <div class="rounded-2xl p-6 mb-7 w-full overflow-hidden" style="background: var(--surface); border: 1px solid var(--border);">
                        <div class="flex justify-between items-center mb-5 gap-2">
                            <button
                                @click="prevMonth"
                                :disabled="!canPrevMonth"
                                class="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:scale-105 shrink-0 disabled:opacity-30 disabled:cursor-not-allowed"
                                style="background: var(--background-subtle); border: 1px solid var(--border); color: var(--text);"
                            >
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <h3 class="text-[1.15rem] font-black capitalize tracking-tight" style="color: var(--text-heading);">
                                {{ monthTitle }}
                            </h3>
                            <button
                                @click="nextMonth"
                                class="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:scale-105 shrink-0"
                                style="background: var(--background-subtle); border: 1px solid var(--border); color: var(--text);"
                            >
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-7 gap-[0.4rem] text-center w-full">
                            <div v-for="d in ['DOM','SEG','TER','QUA','QUI','SEX','SÁB']" :key="d" class="text-[0.75rem] font-black uppercase tracking-wider py-2" style="color: var(--text-muted);">
                                {{ d }}
                            </div>
                            <div
                                v-for="(d, idx) in calendarDays"
                                :key="idx"
                                @click="selectDate(d)"
                                :class="[
                                    'aspect-square flex items-center justify-center font-bold text-[0.9rem] rounded-xl transition-all relative min-w-0',
                                    d.otherMonth ? 'other-month' : '',
                                    isDateDisabled(d) ? 'disabled' : '',
                                    isToday(d) && !d.otherMonth ? 'today' : '',
                                    isDateSelected(d) ? 'selected' : ''
                                ]"
                                :style="isDateSelected(d) ? { background: 'var(--primary-gradient)', color: '#fff', boxShadow: '0 6px 16px var(--primary-light)', transform: 'scale(1.04)', zIndex: 3 } : {}"
                            >
                                <span :style="(d.otherMonth || isDateDisabled(d)) && !isDateSelected(d) ? { color: 'var(--text-muted)', opacity: d.otherMonth ? 0.15 : 0.25, cursor: 'not-allowed' } : { background: isToday(d) && !isDateSelected(d) ? 'var(--background-subtle)' : 'var(--background-subtle)', border: isToday(d) && !isDateSelected(d) ? '1px solid var(--primary)' : '1px solid transparent', color: 'var(--text)', borderRadius: 'var(--radius-sm)' }" class="w-full h-full flex items-center justify-center rounded-xl">
                                    {{ d.day }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl p-6 mb-7 w-full" style="background: var(--surface); border: 1px solid var(--border);">
                        <div class="flex items-center justify-between mb-5 flex-wrap gap-2">
                            <h4 class="text-[1rem] font-black flex items-center gap-2" style="color: var(--text-heading);">
                                <i class="fa-regular fa-clock" :style="{ color: 'var(--primary)' }"></i>
                                Horários Disponíveis
                                <span v-if="selectedDate" class="text-[0.75rem] font-semibold px-2.5 py-0.5 rounded-full" :style="{ color: 'var(--primary)', borderColor: 'var(--primary-light)', backgroundColor: 'var(--primary-light)' }">
                                    {{ formatDateLong(selectedDate) }}
                                </span>
                            </h4>
                        </div>

                        <div v-if="!selectedDate" class="text-center py-10 opacity-70">
                            <i class="fa-regular fa-hand-pointer text-3xl mb-3 block animate-bounce" :style="{ color: 'var(--primary)' }"></i>
                            <p class="font-bold text-sm" style="color: var(--text-heading);">Selecione uma data no calendário acima</p>
                            <p class="text-xs opacity-70 mt-1">Os horários livres serão carregados automaticamente.</p>
                        </div>

                        <div v-else-if="slotsLoading" class="text-center py-14">
                            <div class="w-9 h-9 border-[3px] rounded-full animate-spin mx-auto mb-3.5" :style="{ borderColor: 'var(--primary-light)', borderTopColor: 'var(--primary)' }"></div>
                            <p style="color: var(--text-muted);">Carregando horários disponíveis...</p>
                        </div>

                        <div v-else-if="availableSlots.length === 0" class="text-center py-10">
                            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-500 border border-rose-500/20 flex items-center justify-center mx-auto mb-3 text-xl">
                                <i class="fa-solid fa-calendar-xmark"></i>
                            </div>
                            <p class="font-bold" style="color: var(--text-heading);">Nenhum horário livre nesta data</p>
                            <p class="text-xs opacity-70 mt-1 max-w-sm mx-auto">
                                Tente selecionar outra data ou volte em breve para novas aberturas.
                            </p>
                        </div>

                        <div v-else class="grid gap-3 w-full" style="grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));">
                            <button
                                v-for="slot in availableSlots"
                                :key="slot.time || slot"
                                @click="selectTime(slot.time || slot)"
                                type="button"
                                :class="['rounded-xl min-h-[48px] py-2.5 px-3 text-center font-black text-[0.95rem] cursor-pointer transition-all flex items-center justify-center gap-2 min-w-0', selectedTime === (slot.time || slot) ? 'selected' : '']"
                                :style="selectedTime === (slot.time || slot) ? { background: 'var(--primary-gradient)', color: '#fff', borderColor: 'var(--primary)', boxShadow: '0 6px 16px var(--primary-light)' } : { background: 'var(--background-subtle)', border: '2px solid var(--border)', color: 'var(--text)' }"
                            >
                                <i class="fa-regular fa-clock text-xs"></i>
                                {{ slot.time || slot }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========== STEP: CONFIRM ========== -->
                <div v-if="currentStep === stepType.confirm">
                    <div class="rounded-2xl p-6 mb-7 relative overflow-hidden w-full" style="background: linear-gradient(135deg, var(--primary-light) 0%, rgba(56,189,248,0.02) 100%); border: 1px solid var(--border);">
                        <div class="absolute top-0 left-0 w-1 h-full" style="background: var(--primary-gradient);"></div>
                        <div class="text-[0.825rem] uppercase tracking-wider font-black mb-5 flex items-center gap-2" :style="{ color: 'var(--primary)' }">
                            <i class="fa-solid fa-clipboard-list"></i>
                            Resumo do Agendamento
                        </div>

                        <div class="grid gap-5 w-full" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
                            <div v-if="activeProfessional" class="flex flex-col gap-1 min-w-0">
                                <span class="text-[0.725rem] uppercase tracking-wider font-bold" style="color: var(--text-muted);">Profissional</span>
                                <span class="text-[1.05rem] font-black break-words" :style="{ color: 'var(--primary)' }">
                                    {{ activeProfessional.name }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1 min-w-0">
                                <span class="text-[0.725rem] uppercase tracking-wider font-bold" style="color: var(--text-muted);">Serviço Selecionado</span>
                                <span class="text-[1.05rem] font-black break-words" :style="{ color: 'var(--primary)' }">
                                    {{ selectedService?.name }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1 min-w-0">
                                <span class="text-[0.725rem] uppercase tracking-wider font-bold" style="color: var(--text-muted);">Data Escolhida</span>
                                <span class="text-[1.05rem] font-black break-words" style="color: var(--text-heading);">
                                    {{ formatDateLong(selectedDate) }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1 min-w-0">
                                <span class="text-[0.725rem] uppercase tracking-wider font-bold" style="color: var(--text-muted);">Horário</span>
                                <span class="text-[1.05rem] font-black break-words" style="color: var(--text-heading);">
                                    <i class="fa-regular fa-clock mr-1" :style="{ color: 'var(--primary)' }"></i>
                                    {{ selectedTime }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1 min-w-0">
                                <span class="text-[0.725rem] uppercase tracking-wider font-bold" style="color: var(--text-muted);">Duração</span>
                                <span class="text-[1.05rem] font-black break-words" style="color: var(--text-heading);">
                                    {{ selectedService?.duration_minutes || 30 }} minutos
                                </span>
                            </div>
                            <div class="flex flex-col gap-1 min-w-0">
                                <span class="text-[0.725rem] uppercase tracking-wider font-bold" style="color: var(--text-muted);">Valor Total</span>
                                <span class="text-[1.3rem] font-black break-words text-emerald-600 dark:text-emerald-400">
                                    R$ {{ formatCurrency(selectedService?.price) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitBooking" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-[0.825rem] font-bold mb-2" style="color: var(--text-heading);" for="client_name">Nome Completo *</label>
                                <input
                                    id="client_name"
                                    v-model="bookingForm.client_name"
                                    type="text"
                                    required
                                    autocomplete="name"
                                    class="form-control w-full rounded-xl py-3 px-4 text-base focus:outline-none transition-all"
                                    placeholder="Ex: Maria da Silva"
                                >
                                <div v-if="bookingForm.errors.client_name" class="text-rose-500 text-xs font-semibold mt-1 block">
                                    {{ bookingForm.errors.client_name }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-[0.825rem] font-bold mb-2" style="color: var(--text-heading);" for="client_email">E-mail *</label>
                                <input
                                    id="client_email"
                                    v-model="bookingForm.client_email"
                                    type="email"
                                    required
                                    autocomplete="email"
                                    class="form-control w-full rounded-xl py-3 px-4 text-base focus:outline-none transition-all"
                                    placeholder="seu@email.com"
                                >
                                <div v-if="bookingForm.errors.client_email" class="text-rose-500 text-xs font-semibold mt-1 block">
                                    {{ bookingForm.errors.client_email }}
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[0.825rem] font-bold mb-2" style="color: var(--text-heading);" for="client_phone">Telefone / WhatsApp *</label>
                                <input
                                    id="client_phone"
                                    v-model="bookingForm.client_phone"
                                    type="tel"
                                    required
                                    autocomplete="tel"
                                    class="form-control w-full rounded-xl py-3 px-4 text-base focus:outline-none transition-all"
                                    placeholder="(11) 99999-8888"
                                >
                                <div v-if="bookingForm.errors.client_phone" class="text-rose-500 text-xs font-semibold mt-1 block">
                                    {{ bookingForm.errors.client_phone }}
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[0.825rem] font-bold mb-2" style="color: var(--text-heading);" for="notes">Observações (opcional)</label>
                                <textarea
                                    id="notes"
                                    v-model="bookingForm.notes"
                                    rows="3"
                                    class="form-control w-full rounded-xl py-3 px-4 text-sm focus:outline-none transition-all resize-none"
                                    placeholder="Alguma preferência ou informação importante para o profissional?"
                                ></textarea>
                            </div>
                        </div>

                        <div v-if="bookingForm.errors.service_id || bookingForm.errors.appointment_date || bookingForm.errors.appointment_time" class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-semibold">
                            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                            Verifique os dados e tente novamente.
                        </div>

                        <div class="flex justify-between items-center gap-[0.85rem] mt-7 pt-5 border-t flex-wrap w-full" style="border-color: var(--border);">
                            <button
                                @click.prevent="prevStep"
                                type="button"
                                class="inline-flex items-center justify-center gap-2 py-3.5 px-6 font-bold text-[0.95rem] rounded-xl transition-all cursor-pointer"
                                style="background: var(--background-subtle); border: 1px solid var(--border); color: var(--text);"
                            >
                                <i class="fa-solid fa-arrow-left text-sm"></i>
                                Voltar
                            </button>

                            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                                <button
                                    v-if="paymentEnabled"
                                    type="button"
                                    @click="submitBooking(false)"
                                    :disabled="bookingForm.processing"
                                    class="inline-flex items-center justify-center gap-2 py-3.5 px-6 font-bold text-[0.95rem] rounded-xl transition-all cursor-pointer border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span>Agendar sem Pagar</span>
                                </button>

                                <button
                                    type="button"
                                    @click="submitBooking(paymentEnabled)"
                                    :disabled="bookingForm.processing"
                                    class="btn-motion-3d inline-flex items-center justify-center gap-2 py-3.5 px-6 font-bold text-[0.95rem] rounded-xl text-white transition-all cursor-pointer min-h-[48px] disabled:opacity-50 disabled:cursor-not-allowed"
                                    :style="{ background: 'var(--primary-gradient)', boxShadow: '0 6px 18px var(--primary-light)' }"
                                >
                                    <i v-if="bookingForm.processing" class="fa-solid fa-spinner fa-spin"></i>
                                    <i v-else :class="paymentEnabled ? 'fa-solid fa-wallet' : 'fa-solid fa-calendar-check'"></i>
                                    <span>{{ bookingForm.processing ? 'Confirmando...' : (paymentEnabled ? 'Pagar e Finalizar' : 'Confirmar Agendamento') }}</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Payment Modal -->
                    <Teleport to="body">
                        <div v-if="isPaying" class="liquid-glass-backdrop fixed inset-0 z-[999999] flex items-center justify-center p-4" @click.self="closePaymentAndFallback">
                            <div class="liquid-glass-card w-full max-w-md p-6 sm:p-7 space-y-6 relative text-center" @click.stop>
                                <div class="flex items-center justify-between pb-3 border-b" style="border-color: var(--border);">
                                    <h3 class="text-base sm:text-lg font-extrabold" style="color: var(--text-heading);">Pagamento PIX</h3>
                                    <button type="button" @click="closePaymentAndFallback" class="w-8 h-8 rounded-xl flex items-center justify-center opacity-60 hover:opacity-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                                        <i class="fa-solid fa-xmark text-lg"></i>
                                    </button>
                                </div>

                                <div v-if="paymentLoading" class="py-12 flex flex-col items-center justify-center space-y-3">
                                    <i class="fa-solid fa-spinner fa-spin text-3xl text-indigo-600"></i>
                                    <p class="text-xs text-slate-500">Gerando QR Code PIX...</p>
                                </div>

                                <div v-else class="space-y-5">
                                    <div class="bg-indigo-500/10 border border-indigo-500/20 p-4 rounded-xl text-left">
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Total a pagar:</p>
                                        <p class="text-2xl font-black text-slate-900 dark:text-white">R$ {{ formatCurrency(selectedService?.price) }}</p>
                                        <p class="text-[10px] text-slate-400 mt-1">Serviço: {{ selectedService?.name }}</p>
                                    </div>

                                    <div v-if="paymentDetails?.pix_qr_code_base64" class="flex justify-center">
                                        <div class="p-3 bg-white rounded-2xl border border-slate-200 inline-block shadow-sm">
                                            <img :src="`data:image/png;base64,${paymentDetails.pix_qr_code_base64}`" class="w-48 h-48 block" alt="QR Code Pix" />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <button
                                            type="button"
                                            @click="copyPixCode"
                                            class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-bold text-xs bg-indigo-600 hover:bg-indigo-700 text-white transition-all shadow-md"
                                        >
                                            <i class="fa-solid fa-copy"></i>
                                            <span>{{ copied ? 'Código Copiado!' : 'Copiar Código PIX (Copia e Cola)' }}</span>
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-center gap-2 text-xs">
                                        <span v-if="paymentStatus === 'pending'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 animate-pulse font-semibold">
                                            <i class="fa-solid fa-clock"></i>
                                            Aguardando pagamento...
                                        </span>
                                        <span v-else-if="paymentStatus === 'approved'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-semibold">
                                            <i class="fa-solid fa-circle-check"></i>
                                            Pagamento Aprovado!
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400 font-semibold">
                                            <i class="fa-solid fa-circle-xmark"></i>
                                            Falha ou Cancelado
                                        </span>
                                    </div>

                                    <div class="pt-3 border-t flex flex-col space-y-2" style="border-color: var(--border);">
                                        <button
                                            type="button"
                                            @click="closePaymentAndFallback"
                                            class="w-full py-2.5 px-4 rounded-xl font-semibold text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition-all"
                                        >
                                            Pagar no Estabelecimento
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Teleport>
                </div>

                <!-- Navigation buttons (not on confirm step) -->
                <div v-if="currentStep !== stepType.confirm" class="flex justify-between items-center gap-[0.85rem] mt-7 pt-5 border-t flex-wrap w-full" style="border-color: var(--border);">
                    <button
                        v-if="currentStep > 1"
                        @click.prevent="prevStep"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 py-3.5 px-6 font-bold text-[0.95rem] rounded-xl transition-all cursor-pointer"
                        style="background: var(--background-subtle); border: 1px solid var(--border); color: var(--text);"
                    >
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                        Voltar
                    </button>
                    <div v-else></div>

                    <button
                        @click.prevent="nextStep"
                        type="button"
                        :disabled="
                            (currentStep === stepType.professional && !chosenProfessionalId) ||
                            (currentStep === stepType.service && !selectedServiceId) ||
                            (currentStep === stepType.datetime && (!selectedDate || !selectedTime))
                        "
                        class="btn-motion-3d inline-flex items-center justify-center gap-2 py-3.5 px-6 font-bold text-[0.95rem] rounded-xl text-white transition-all cursor-pointer min-h-[48px] disabled:opacity-50 disabled:cursor-not-allowed"
                        :style="{ background: 'var(--primary-gradient)', boxShadow: '0 6px 18px var(--primary-light)' }"
                    >
                        <span>Próximo Passo</span>
                        <i class="fa-solid fa-arrow-right text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style>
@keyframes fadeInSlide {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
