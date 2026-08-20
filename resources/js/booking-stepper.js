import { formatDateString, formatCurrency, generateCalendarDays } from './stepper/calendar-utils.js';

export function createBookingStepperConfig(Vue, props) {
    const { ref, computed } = Vue;

    const teamMembers = ref(props.teamMembersData || []);
    const selectedProfessional = ref(props.selectedProfessionalData || null);
    const services = ref(props.servicesData || []);
    const blockedSlots = ref(props.blockedSlotsData || []);
    const selectedService = ref(null);
    const currentStep = ref(1);

    const currentDate = ref(new Date());
    const selectedDate = ref(formatDateString(new Date()));
    const selectedTime = ref('');
    const availableSlots = ref([]);
    const isLoadingSlots = ref(false);

    const calendarDays = computed(() => {
        return generateCalendarDays(currentDate.value, blockedSlots.value, selectedDate.value);
    });

    return {
        teamMembers,
        selectedProfessional,
        services,
        blockedSlots,
        selectedService,
        currentStep,
        currentDate,
        selectedDate,
        selectedTime,
        availableSlots,
        isLoadingSlots,
        calendarDays,
        formatCurrency,
        formatDateString,
    };
}
