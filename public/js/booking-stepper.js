const { createApp, ref, computed, onMounted, watch } = Vue;

const BookingStepper = {
    props: {
        servicesData: {
            type: Array,
            required: true
        },
        blockedSlotsData: {
            type: Array,
            default: () => []
        },
        teamMembersData: {
            type: Array,
            default: () => []
        },
        selectedProfessionalData: {
            type: Object,
            default: null
        },
        storeUrl: {
            type: String,
            required: true
        },
        slotsUrl: {
            type: String,
            required: true
        },
        csrfToken: {
            type: String,
            required: true
        }
    },
    setup(props) {
        const teamMembers = ref(props.teamMembersData || []);
        const selectedProfessional = ref(props.selectedProfessionalData || null);
        
        const hasProfessionalStep = computed(() => teamMembers.value.length > 0 && !props.selectedProfessionalData);

        const stepService = 1;
        const stepProfessional = computed(() => hasProfessionalStep.value ? 2 : 0);
        const stepDate = computed(() => hasProfessionalStep.value ? 3 : 2);
        const stepTime = computed(() => hasProfessionalStep.value ? 4 : 3);
        const totalSteps = computed(() => hasProfessionalStep.value ? 4 : 3);

        const currentStep = ref(1);
        const services = ref(props.servicesData || []);
        const blockedSlots = ref(props.blockedSlotsData || []);
        const selectedDayBlockedNotice = ref('');
        const selectedService = ref(null);
        const searchQuery = ref('');
        const professionalSearchQuery = ref('');
        
        // Calendar & Date state
        const currentDate = ref(new Date());
        const selectedDate = ref(formatDateString(new Date()));
        const availableSlots = ref([]);
        const selectedTime = ref('');
        const isLoadingSlots = ref(false);
        const isSubmitting = ref(false);

        // Form state
        const clientName = ref('');
        const clientEmail = ref('');
        const clientPhone = ref('');
        const notes = ref('');

        const monthNames = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
        ];

        const dayNames = [
            'Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira',
            'Quinta-feira', 'Sexta-feira', 'Sábado'
        ];

        const filteredServices = computed(() => {
            let list = services.value;

            // If a specific professional with limited services is selected
            if (selectedProfessional.value && Array.isArray(selectedProfessional.value.services) && selectedProfessional.value.services.length > 0) {
                const allowedIds = selectedProfessional.value.services.map(Number);
                list = list.filter(s => allowedIds.includes(Number(s.id)));
            }

            if (!searchQuery.value.trim()) return list;
            const q = searchQuery.value.toLowerCase();
            return list.filter(s => 
                (s.name && s.name.toLowerCase().includes(q)) || 
                (s.description && s.description.toLowerCase().includes(q))
            );
        });

        const filteredTeamMembers = computed(() => {
            let list = teamMembers.value;

            if (selectedService.value) {
                const sId = Number(selectedService.value.id);
                list = list.filter(m => {
                    if (!Array.isArray(m.services) || m.services.length === 0) return true;
                    return m.services.map(Number).includes(sId);
                });
            }

            if (!professionalSearchQuery.value.trim()) return list;
            const q = professionalSearchQuery.value.toLowerCase();
            return list.filter(m => 
                (m.name && m.name.toLowerCase().includes(q)) || 
                (m.job_title && m.job_title.toLowerCase().includes(q)) ||
                (m.bio && m.bio.toLowerCase().includes(q))
            );
        });

        const monthTitle = computed(() => {
            return `${monthNames[currentDate.value.getMonth()]} de ${currentDate.value.getFullYear()}`;
        });

        const canGoPrevMonth = computed(() => {
            const today = new Date();
            return currentDate.value.getFullYear() > today.getFullYear() || 
                   currentDate.value.getMonth() > today.getMonth();
        });

        const calendarDays = computed(() => {
            const year = currentDate.value.getFullYear();
            const month = currentDate.value.getMonth();
            const firstDayIndex = new Date(year, month, 1).getDay();
            const lastDay = new Date(year, month + 1, 0).getDate();
            const prevLastDay = new Date(year, month, 0).getDate();

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const days = [];

            // Previous month padding
            for (let x = firstDayIndex; x > 0; x--) {
                days.push({
                    day: prevLastDay - x + 1,
                    isOtherMonth: true,
                    isDisabled: true,
                    isBlocked: false,
                    blockReason: '',
                    fullDate: null
                });
            }

            // Current month days
            for (let i = 1; i <= lastDay; i++) {
                const thisDate = new Date(year, month, i);
                thisDate.setHours(0, 0, 0, 0);
                const dateStr = formatDateString(thisDate);

                const matchingBlock = blockedSlots.value.find(b => {
                    if (!b.start_date || !b.end_date) return false;
                    return dateStr >= b.start_date && dateStr <= b.end_date;
                });
                const isBlocked = !!matchingBlock;
                const blockReason = matchingBlock ? (matchingBlock.reason || 'Manutenção / Feriado') : '';

                days.push({
                    day: i,
                    isOtherMonth: false,
                    isToday: thisDate.getTime() === today.getTime(),
                    isDisabled: thisDate < today || isBlocked,
                    isBlocked: isBlocked,
                    blockReason: blockReason,
                    isSelected: dateStr === selectedDate.value && !isBlocked,
                    fullDate: dateStr,
                    rawDate: thisDate
                });
            }

            return days;
        });

        const formattedSelectedDateDisplay = computed(() => {
            if (!selectedDate.value) return '';
            const parts = selectedDate.value.split('-');
            if (parts.length !== 3) return selectedDate.value;
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        });

        const formattedSelectedDateFull = computed(() => {
            if (!selectedDate.value) return '';
            const parts = selectedDate.value.split('-');
            if (parts.length !== 3) return selectedDate.value;
            const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
            const dayOfWeek = dayNames[d.getDay()];
            const monthName = monthNames[d.getMonth()];
            return `${dayOfWeek}, ${parts[2]} de ${monthName} de ${parts[0]}`;
        });

        function formatDateString(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function formatPrice(val) {
            if (val === null || val === undefined) return '0,00';
            return Number(val).toFixed(2).replace('.', ',');
        }

        function handlePhoneInput(e) {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length > 11) val = val.substring(0, 11);
            
            if (val.length > 10) {
                val = val.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (val.length > 6) {
                val = val.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
            } else if (val.length > 2) {
                val = val.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
            } else if (val.length > 0) {
                val = val.replace(/^(\d*)$/, '($1');
            }
            clientPhone.value = val;
        }

        const nextFreeDate = computed(() => {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Try to find a date starting from today
            for (let offset = 0; offset < 90; offset++) {
                const checkDate = new Date(today);
                checkDate.setDate(today.getDate() + offset);
                
                const dateStr = formatDateString(checkDate);
                
                // Check if blocked in blockedSlots
                const isBlocked = blockedSlots.value.some(b => {
                    if (!b.start_date || !b.end_date) return false;
                    return dateStr >= b.start_date && dateStr <= b.end_date;
                });
                
                if (!isBlocked) {
                    return checkDate;
                }
            }
            return null;
        });

        const nextFreeDateFormatted = computed(() => {
            if (!nextFreeDate.value) return '';
            const d = nextFreeDate.value;
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            const dayName = dayNames[d.getDay()];
            return `${dayName}, ${day}/${month}/${year}`;
        });

        function jumpToNextFreeDate() {
            if (nextFreeDate.value) {
                const targetDate = nextFreeDate.value;
                currentDate.value = new Date(targetDate.getFullYear(), targetDate.getMonth(), 1);
                selectedDate.value = formatDateString(targetDate);
                selectedTime.value = '';
                selectedDayBlockedNotice.value = '';
                fetchAvailableSlots();
            }
        }

        function selectService(service) {
            selectedService.value = service;
            selectedTime.value = '';
            if (selectedProfessional.value && selectedService.value && Array.isArray(selectedProfessional.value.services) && selectedProfessional.value.services.length > 0) {
                const allowedIds = selectedProfessional.value.services.map(Number);
                if (!allowedIds.includes(Number(selectedService.value.id))) {
                    selectedProfessional.value = null;
                }
            }

            if (hasProfessionalStep.value) {
                currentStep.value = stepProfessional.value;
            } else {
                currentStep.value = stepDate.value;
            }
            fetchAvailableSlots();
            window.scrollTo({ top: 120, behavior: 'smooth' });
        }

        function selectProfessional(member) {
            selectedProfessional.value = member;
            selectedTime.value = '';
            currentStep.value = stepDate.value;
            fetchAvailableSlots();
            window.scrollTo({ top: 120, behavior: 'smooth' });
        }

        function prevMonth() {
            if (!canGoPrevMonth.value) return;
            const newDate = new Date(currentDate.value);
            newDate.setMonth(newDate.getMonth() - 1);
            currentDate.value = newDate;
        }

        function nextMonth() {
            const newDate = new Date(currentDate.value);
            newDate.setMonth(newDate.getMonth() + 1);
            currentDate.value = newDate;
        }

        function goToToday() {
            const today = new Date();
            currentDate.value = new Date(today.getFullYear(), today.getMonth(), 1);
            selectedDate.value = formatDateString(today);
            selectedTime.value = '';
            fetchAvailableSlots();
        }

        function selectDay(dayObj) {
            if (dayObj.isBlocked) {
                selectedDayBlockedNotice.value = `🚫 Feriado / Bloqueio: ${dayObj.blockReason || 'Dia indisponível para atendimento'}`;
                return;
            }
            selectedDayBlockedNotice.value = '';
            if (dayObj.isDisabled || dayObj.isOtherMonth) return;
            selectedDate.value = dayObj.fullDate;
            selectedTime.value = '';
            fetchAvailableSlots();
        }

        async function fetchAvailableSlots() {
            if (!selectedService.value || !selectedDate.value) return;
            if (hasProfessionalStep.value && currentStep.value < stepDate.value && !selectedProfessional.value) {
                return;
            }
            isLoadingSlots.value = true;
            availableSlots.value = [];

            try {
                const params = new URLSearchParams({
                    service_id: selectedService.value.id,
                    date: selectedDate.value
                });

                if (selectedProfessional.value) {
                    params.set('team_member_id', selectedProfessional.value.id);
                }

                const url = `${props.slotsUrl}?${params.toString()}`;
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) throw new Error('Falha ao carregar horários');
                const data = await res.json();
                availableSlots.value = data.slots || [];
            } catch (err) {
                console.error('Erro ao buscar horários:', err);
            } finally {
                isLoadingSlots.value = false;
            }
        }

        function selectTime(slot) {
            selectedTime.value = slot;
        }

        function goToStep(step) {
            if (step === 1) {
                currentStep.value = 1;
            } else if (hasProfessionalStep.value && step === stepProfessional.value) {
                if (!selectedService.value) {
                    alert('Por favor, selecione um serviço primeiro.');
                    return;
                }
                currentStep.value = stepProfessional.value;
            } else if (step === stepDate.value) {
                if (!selectedService.value) {
                    alert('Por favor, selecione um serviço primeiro.');
                    return;
                }
                currentStep.value = stepDate.value;
            } else if (step === stepTime.value) {
                if (!selectedService.value) {
                    alert('Por favor, selecione um serviço.');
                    return;
                }
                if (hasProfessionalStep.value && !selectedProfessional.value) {
                    alert('Por favor, escolha um profissional para continuar.');
                    return;
                }
                currentStep.value = stepTime.value;
                if (availableSlots.value.length === 0) {
                    fetchAvailableSlots();
                }
            }
            window.scrollTo({ top: 120, behavior: 'smooth' });
        }

        function nextStep() {
            if (currentStep.value === stepService) {
                if (!selectedService.value) {
                    alert('Por favor, selecione um serviço desejado para continuar.');
                    return;
                }
                if (hasProfessionalStep.value) {
                    currentStep.value = stepProfessional.value;
                } else {
                    currentStep.value = stepDate.value;
                    fetchAvailableSlots();
                }
            } else if (hasProfessionalStep.value && currentStep.value === stepProfessional.value) {
                currentStep.value = stepDate.value;
                fetchAvailableSlots();
            } else if (currentStep.value === stepDate.value) {
                if (!selectedDate.value) {
                    alert('Por favor, selecione uma data.');
                    return;
                }
                currentStep.value = stepTime.value;
                fetchAvailableSlots();
            } else if (currentStep.value === stepTime.value) {
                if (!selectedTime.value) {
                    alert('Por favor, escolha um horário disponível no calendário.');
                    return;
                }
            }
            window.scrollTo({ top: 120, behavior: 'smooth' });
        }

        function prevStep() {
            if (currentStep.value > 1) {
                currentStep.value = currentStep.value - 1;
                window.scrollTo({ top: 120, behavior: 'smooth' });
            }
        }

        function handleSubmit(e) {
            if (!selectedService.value || !selectedDate.value || !selectedTime.value) {
                e.preventDefault();
                alert('Informações de serviço, data ou horário incompletas.');
                return;
            }
            isSubmitting.value = true;
        }

        onMounted(() => {
            if (selectedService.value && services.value.length > 0) {
                fetchAvailableSlots();
            }
        });

        return {
            currentStep,
            hasProfessionalStep,
            stepService,
            stepProfessional,
            stepDate,
            stepTime,
            totalSteps,
            teamMembers,
            filteredTeamMembers,
            selectedProfessional,
            professionalSearchQuery,
            services,
            filteredServices,
            searchQuery,
            selectedService,
            selectedDayBlockedNotice,
            blockedSlots,
            selectedDate,
            monthTitle,
            canGoPrevMonth,
            calendarDays,
            availableSlots,
            selectedTime,
            isLoadingSlots,
            isSubmitting,
            clientName,
            clientEmail,
            clientPhone,
            notes,
            formattedSelectedDateDisplay,
            formattedSelectedDateFull,
            formatPrice,
            handlePhoneInput,
            selectProfessional,
            selectService,
            prevMonth,
            nextMonth,
            goToToday,
            selectDay,
            selectTime,
            goToStep,
            nextStep,
            prevStep,
            handleSubmit,
            nextFreeDate,
            nextFreeDateFormatted,
            jumpToNextFreeDate
        };
    },
    template: `
        <div class="booking-vue-container">
            <!-- Stepper Indicator -->
            <div class="stepper-wrapper">
                <div class="stepper-track-bg"></div>
                <div class="stepper-progress-bar" :style="{ width: ((currentStep - 1) / (totalSteps - 1)) * 100 + '%' }"></div>
                
                <!-- Step 1: Service -->
                <div class="step-item" :class="{ active: currentStep === 1, completed: currentStep > 1 }" @click="goToStep(1)">
                    <div class="step-number">
                        <i v-if="currentStep > 1" class="fa-solid fa-check text-sm"></i>
                        <span v-else>1</span>
                    </div>
                    <div class="step-label">Serviço</div>
                    <div class="step-sublabel">Escolha a opção</div>
                </div>

                <!-- Step 2: Professional (Only if hasProfessionalStep) -->
                <div v-if="hasProfessionalStep" class="step-item" :class="{ active: currentStep === stepProfessional, completed: currentStep > stepProfessional }" @click="goToStep(stepProfessional)">
                    <div class="step-number">
                        <i v-if="currentStep > stepProfessional" class="fa-solid fa-check text-sm"></i>
                        <span v-else>{{ stepProfessional }}</span>
                    </div>
                    <div class="step-label">Profissional</div>
                    <div class="step-sublabel">Escolha quem atende</div>
                </div>

                <!-- Step 3: Date -->
                <div class="step-item" :class="{ active: currentStep === stepDate, completed: currentStep > stepDate }" @click="goToStep(stepDate)">
                    <div class="step-number">
                        <i v-if="currentStep > stepDate" class="fa-solid fa-check text-sm"></i>
                        <span v-else>{{ stepDate }}</span>
                    </div>
                    <div class="step-label">Data</div>
                    <div class="step-sublabel">Escolha o dia</div>
                </div>

                <!-- Step 4: Time -->
                <div class="step-item" :class="{ active: currentStep === stepTime }" @click="goToStep(stepTime)">
                    <div class="step-number">{{ stepTime }}</div>
                    <div class="step-label">Horário</div>
                    <div class="step-sublabel">Escolha o slot</div>
                </div>
            </div>

            <!-- Form -->
            <form :action="storeUrl" method="POST" @submit="handleSubmit">
                <input type="hidden" name="_token" :value="csrfToken">
                <input type="hidden" name="team_member_id" :value="selectedProfessional ? selectedProfessional.id : ''">
                <input type="hidden" name="service_id" :value="selectedService ? selectedService.id : ''">
                <input type="hidden" name="appointment_date" :value="selectedDate">
                <input type="hidden" name="appointment_time" :value="selectedTime">

                <!-- STEP 1: Services Selection (First Step) -->
                <div v-show="currentStep === 1" class="step-card">
                    <div class="step-card-header">
                        <h3 class="step-card-title">
                            <i class="fa-solid fa-scissors"></i>
                            <span>1. Selecione o Serviço Desejado</span>
                        </h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                {{ filteredServices.length }} {{ filteredServices.length === 1 ? 'opção' : 'opções' }}
                            </span>
                        </div>
                    </div>

                    <!-- Search filter always visible -->
                    <div class="services-search-wrapper">
                        <i class="fa-solid fa-magnifying-glass services-search-icon"></i>
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            placeholder="Buscar serviço por nome ou descrição..." 
                            class="services-search-input"
                        >
                    </div>

                    <div class="services-grid">
                        <div 
                            v-for="service in filteredServices" 
                            :key="service.id" 
                            class="service-card" 
                            :class="{ selected: selectedService && selectedService.id === service.id }"
                            @click="selectService(service)"
                        >
                            <div v-if="selectedService && selectedService.id === service.id" class="service-selected-badge">
                                <i class="fa-solid fa-check"></i>
                            </div>

                            <div class="service-img-wrapper">
                                <img v-if="service.image_url" :src="service.image_url" :alt="service.name" class="service-img" loading="lazy">
                                <div v-else class="text-slate-400 dark:text-slate-500 flex flex-col items-center gap-2">
                                    <i class="fa-solid fa-wand-magic-sparkles text-3xl text-indigo-500 dark:text-indigo-400 opacity-70"></i>
                                    <span class="text-[10px] font-semibold uppercase tracking-wider opacity-80">Agendae Premium</span>
                                </div>
                            </div>

                            <div>
                                <div class="service-name">{{ service.name }}</div>
                                <div class="service-desc">{{ service.description || 'Atendimento profissional personalizado com máxima qualidade e conforto.' }}</div>
                            </div>

                            <div class="service-footer">
                                <div class="service-price">R$ {{ formatPrice(service.price) }}</div>
                                <div class="service-duration">
                                    <i class="fa-regular fa-clock text-xs text-indigo-500 dark:text-indigo-400"></i>
                                    <span>{{ service.duration_minutes }} min</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="filteredServices.length === 0" class="text-center py-12 text-slate-400">
                        <i class="fa-solid fa-circle-exclamation text-3xl mb-3 text-slate-400"></i>
                        <p>Nenhum serviço encontrado para "{{ searchQuery }}".</p>
                    </div>

                    <div class="wizard-actions">
                        <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                            <i class="fa-solid fa-circle-info text-indigo-500 dark:text-indigo-400"></i>
                            <span>Clique no card do serviço para selecioná-lo</span>
                        </div>

                        <button type="button" class="btn btn-primary" @click="nextStep" :disabled="!selectedService">
                            <span>{{ hasProfessionalStep ? 'Avançar para Escolha do Profissional' : 'Avançar para Data e Horário' }}</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Team / Professional Selection (After Service) -->
                <div v-if="hasProfessionalStep" v-show="currentStep === stepProfessional" class="step-card">
                    <div class="step-card-header">
                        <h3 class="step-card-title">
                            <i class="fa-solid fa-users"></i>
                            <span>2. Escolha o Profissional do Time</span>
                        </h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                                <i class="fa-solid fa-scissors mr-1"></i>
                                {{ selectedService ? selectedService.name : 'Serviço' }}
                            </span>
                            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                {{ filteredTeamMembers.length }} {{ filteredTeamMembers.length === 1 ? 'profissional' : 'profissionais' }}
                            </span>
                        </div>
                    </div>

                    <!-- Search filter if multiple professionals -->
                    <div v-if="teamMembers.length > 3" class="services-search-wrapper">
                        <i class="fa-solid fa-magnifying-glass services-search-icon"></i>
                        <input 
                            type="text" 
                            v-model="professionalSearchQuery" 
                            placeholder="Buscar profissional por nome ou especialidade..." 
                            class="services-search-input"
                        >
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 my-4">
                        <!-- Option: Qualquer Profissional Disponível -->
                        <div 
                            class="service-card cursor-pointer p-4 rounded-2xl border transition-all flex flex-col justify-between"
                            :class="{ selected: selectedProfessional === null }"
                            @click="selectProfessional(null)"
                        >
                            <div class="flex items-center gap-3.5 mb-3">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-500 to-brand-600 text-white flex items-center justify-center text-xl shadow-md shrink-0">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-extrabold text-sm text-slate-900 dark:text-white truncate">Sem preferência</h4>
                                    <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">Primeiro Disponível</p>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                Atendimento rápido com qualquer especialista da equipe livre no horário.
                            </p>
                            <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                <span>Avançar para Horários</span>
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </div>
                        </div>

                        <!-- Each Specific Professional -->
                        <div 
                            v-for="member in filteredTeamMembers" 
                            :key="member.id" 
                            class="service-card cursor-pointer p-4 rounded-2xl border transition-all flex flex-col justify-between relative"
                            :class="{ selected: selectedProfessional && selectedProfessional.id === member.id }"
                            @click="selectProfessional(member)"
                        >
                            <div v-if="selectedProfessional && selectedProfessional.id === member.id" class="service-selected-badge">
                                <i class="fa-solid fa-check"></i>
                            </div>

                            <div class="flex items-center gap-3.5 mb-3">
                                <div class="w-14 h-14 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center shrink-0 shadow-md">
                                    <img v-if="member.avatar_url" :src="member.avatar_url" :alt="member.name" class="w-full h-full object-cover" loading="lazy">
                                    <div v-else class="w-full h-full bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-extrabold text-lg">
                                        {{ (member.name || 'P').substring(0, 2).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-extrabold text-sm text-slate-900 dark:text-white truncate">{{ member.name }}</h4>
                                    <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ member.job_title || 'Especialista' }}</p>
                                </div>
                            </div>

                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                {{ member.bio || 'Atendimento profissional personalizado de excelência.' }}
                            </p>

                            <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                <span>Selecionar e Avançar</span>
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-actions">
                        <button type="button" class="btn btn-outline" @click="prevStep">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            <span>Voltar aos Serviços</span>
                        </button>
                        <button type="button" class="btn btn-primary" @click="nextStep">
                            <span>Avançar para Data e Horário</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: Date -->
                <div v-show="currentStep === stepDate" class="step-card">
                    <div class="step-card-header">
                        <h3 class="step-card-title">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span>{{ hasProfessionalStep ? '3' : '2' }}. Escolha a Data</span>
                        </h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/20 transition-colors">
                                {{ selectedService ? selectedService.name : 'Serviço' }} ({{ selectedService ? selectedService.duration_minutes : 0 }} min)
                            </span>
                            <span v-if="selectedProfessional" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                <i class="fa-solid fa-user-check mr-1 text-indigo-500"></i>
                                {{ selectedProfessional.name }}
                            </span>
                        </div>
                    <!-- Caixa de Próximo Dia Livre -->
                    <div v-if="nextFreeDate" class="p-4 mb-4 rounded-2xl bg-indigo-500/10 dark:bg-indigo-500/15 border border-indigo-500/20 dark:border-indigo-500/30 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-base shrink-0 shadow-sm">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <div>
                                <span class="font-extrabold block text-slate-900 dark:text-white text-xs">Próximo Dia Livre</span>
                                <span class="opacity-80 text-[11px] block mt-0.5">Temos horários livres em <strong class="text-indigo-600 dark:text-indigo-300">{{ nextFreeDateFormatted }}</strong></span>
                            </div>
                        </div>
                        <button type="button" @click="jumpToNextFreeDate" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/20 flex items-center justify-center gap-1.5 self-start sm:self-auto transition-all">
                            <span>Ir para esta data</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>

                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button 
                                type="button" 
                                class="calendar-nav-btn" 
                                :disabled="!canGoPrevMonth" 
                                @click="prevMonth"
                                title="Mês Anterior"
                            >
                                <i class="fa-solid fa-chevron-left text-xs"></i>
                            </button>

                            <div class="flex items-center gap-3">
                                <div class="calendar-month-title">{{ monthTitle }}</div>
                                <button 
                                    type="button" 
                                    @click="goToToday" 
                                    class="text-[11px] font-bold px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition-colors"
                                >
                                    Hoje
                                </button>
                            </div>

                            <button 
                                type="button" 
                                class="calendar-nav-btn" 
                                @click="nextMonth"
                                title="Próximo Mês"
                            >
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </button>
                        </div>

                        <div class="calendar-grid">
                            <div class="calendar-day-header text-rose-500 dark:text-rose-400">Dom</div>
                            <div class="calendar-day-header">Seg</div>
                            <div class="calendar-day-header">Ter</div>
                            <div class="calendar-day-header">Qua</div>
                            <div class="calendar-day-header">Qui</div>
                            <div class="calendar-day-header">Sex</div>
                            <div class="calendar-day-header text-rose-500 dark:text-rose-400">Sáb</div>
                        </div>

                        <div class="calendar-grid">
                            <div 
                                v-for="(dayObj, idx) in calendarDays" 
                                :key="idx"
                                class="calendar-day-cell relative"
                                :class="{
                                    'other-month': dayObj.isOtherMonth,
                                    'disabled': dayObj.isDisabled && !dayObj.isBlocked,
                                    'blocked-day': dayObj.isBlocked,
                                    'bg-red-500/15 border-red-500/40 text-red-600 dark:text-red-400 cursor-not-allowed font-bold': dayObj.isBlocked,
                                    'today': dayObj.isToday && !dayObj.isBlocked,
                                    'selected': dayObj.isSelected
                                }"
                                @click="selectDay(dayObj)"
                                :title="dayObj.isBlocked ? ('🚫 ' + dayObj.blockReason) : (dayObj.fullDate || '')"
                            >
                                <span class="relative z-10">{{ dayObj.day }}</span>
                                <span v-if="dayObj.isBlocked" class="absolute bottom-1 w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            </div>
                        </div>

                        <div v-if="selectedDayBlockedNotice" class="mt-3 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 text-xs font-semibold flex items-center gap-2 animate-pulse">
                            <i class="fa-solid fa-ban text-sm shrink-0"></i>
                            <span>{{ selectedDayBlockedNotice }}</span>
                        </div>
                    </div>

                    <div class="wizard-actions">
                        <button type="button" class="btn btn-outline" @click="prevStep">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            <span>{{ hasProfessionalStep ? 'Voltar ao Profissional' : 'Voltar aos Serviços' }}</span>
                        </button>
                        <button type="button" class="btn btn-primary" @click="nextStep" :disabled="!selectedDate">
                            <span>Avançar para Horários</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 4: Time -->
                <div v-show="currentStep === stepTime" class="step-card">
                    <div class="step-card-header">
                        <h3 class="step-card-title">
                            <i class="fa-solid fa-clock"></i>
                            <span>{{ hasProfessionalStep ? '4' : '3' }}. Escolha o Horário</span>
                        </h3>
                        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/20 flex items-center gap-1.5 transition-colors">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                            Reservando na agenda certa
                        </span>
                    </div>

                    <div class="slots-section">
                        <div class="slots-section-header">
                            <div class="slots-title">
                                <i class="fa-regular fa-clock text-indigo-500 dark:text-indigo-400"></i>
                                <span>Horários Disponíveis</span>
                            </div>
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                {{ formattedSelectedDateFull }}
                            </span>
                        </div>

                        <div v-if="isLoadingSlots" class="loading-spinner">
                            <div class="spinner"></div>
                            <p class="text-xs font-medium">Consultando horários disponíveis em tempo real...</p>
                        </div>

                        <div v-else-if="availableSlots.length > 0" class="slots-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <div 
                                v-for="slot in availableSlots" 
                                :key="slot" 
                                class="slot-button min-h-[48px] py-2.5 px-3 flex items-center justify-center text-center font-extrabold text-sm sm:text-base rounded-xl transition-all"
                                :class="{ selected: selectedTime === slot }"
                                @click="selectTime(slot)"
                            >
                                <i class="fa-regular fa-circle-check text-xs shrink-0" v-if="selectedTime === slot"></i>
                                <span class="tracking-wide">{{ slot }}</span>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-slate-500 dark:text-slate-400">
                            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center justify-center mx-auto mb-3 text-slate-400 dark:text-slate-500">
                                <i class="fa-regular fa-calendar-xmark text-xl"></i>
                            </div>
                            <p class="font-semibold text-sm text-slate-800 dark:text-slate-300">Sem horários livres nesta data</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Por favor, volte para o calendário e escolha outro dia.</p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-title">
                            <i class="fa-solid fa-receipt"></i>
                            <span>Resumo da Sua Reserva</span>
                        </div>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Serviço Selecionado</span>
                                <span class="summary-value">{{ selectedService ? selectedService.name : '-' }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">Duração: {{ selectedService ? selectedService.duration_minutes : '-' }} min</span>
                            </div>

                            <div v-if="selectedProfessional" class="summary-item">
                                <span class="summary-label">Profissional</span>
                                <span class="summary-value highlight">{{ selectedProfessional.name }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ selectedProfessional.job_title || 'Especialista' }}</span>
                            </div>

                            <div class="summary-item">
                                <span class="summary-label">Data Agendada</span>
                                <span class="summary-value highlight">{{ formattedSelectedDateDisplay }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ formattedSelectedDateFull }}</span>
                            </div>

                            <div class="summary-item">
                                <span class="summary-label">Horário de Início</span>
                                <span class="summary-value highlight">{{ selectedTime || '-' }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">Chegar com 5 min de antecedência</span>
                            </div>

                            <div class="summary-item">
                                <span class="summary-label">Investimento Total</span>
                                <span class="summary-value price">R$ {{ selectedService ? formatPrice(selectedService.price) : '0,00' }}</span>
                                <span class="text-xs text-emerald-600 dark:text-emerald-400 opacity-90">Pagamento no local</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="client_name">
                            <i class="fa-regular fa-user text-indigo-500 dark:text-indigo-400 mr-1.5"></i>
                            <span>Nome Completo *</span>
                        </label>
                        <input 
                            type="text" 
                            id="client_name" 
                            name="client_name" 
                            v-model="clientName" 
                            class="form-control" 
                            placeholder="Ex: Maria Eduarda Silva" 
                            required
                        >
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem;">
                        <div class="form-group">
                            <label class="form-label" for="client_email">
                                <i class="fa-regular fa-envelope text-indigo-500 dark:text-indigo-400 mr-1.5"></i>
                                <span>E-mail para Confirmação *</span>
                            </label>
                            <input 
                                type="email" 
                                id="client_email" 
                                name="client_email" 
                                v-model="clientEmail" 
                                class="form-control" 
                                placeholder="maria.silva@exemplo.com" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="client_phone">
                                <i class="fa-brands fa-whatsapp text-emerald-500 dark:text-emerald-400 mr-1.5"></i>
                                <span>WhatsApp / Telefone *</span>
                            </label>
                            <input 
                                type="tel" 
                                id="client_phone" 
                                name="client_phone" 
                                :value="clientPhone" 
                                @input="handlePhoneInput" 
                                class="form-control" 
                                placeholder="(11) 99999-8888" 
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="notes">
                            <i class="fa-regular fa-comment-dots text-indigo-500 dark:text-indigo-400 mr-1.5"></i>
                            <span>Observações ou Pedidos Especiais (opcional)</span>
                        </label>
                        <textarea 
                            id="notes" 
                            name="notes" 
                            v-model="notes" 
                            class="form-control" 
                            rows="2" 
                            placeholder="Ex: Preferência por corte com tesoura, restrições ou observações para a equipe"
                        ></textarea>
                    </div>

                    <div class="wizard-actions">
                        <button type="button" class="btn btn-outline" @click="prevStep" :disabled="isSubmitting">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            <span>{{ hasProfessionalStep ? 'Voltar à Data' : 'Voltar à Data' }}</span>
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="isSubmitting || !clientName || !clientEmail || !clientPhone || !selectedTime" style="padding: 0.95rem 2rem; font-size: 1.05rem;">
                            <i v-if="isSubmitting" class="fa-solid fa-circle-notch fa-spin"></i>
                            <i v-else class="fa-solid fa-check-circle"></i>
                            <span>{{ isSubmitting ? 'Processando Agendamento...' : 'Confirmar e Finalizar' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    `
};

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('vue-booking-app');
    if (el) {
        let servicesData = [];
        try {
            servicesData = JSON.parse(el.dataset.services || '[]');
        } catch (e) {
            console.error('Erro ao fazer parse dos serviços:', e);
        }

        let blockedSlotsData = [];
        try {
            blockedSlotsData = JSON.parse(el.dataset.blockedSlots || '[]');
        } catch (e) {
            console.error('Erro ao fazer parse dos bloqueios:', e);
        }

        let teamMembersData = [];
        try {
            teamMembersData = JSON.parse(el.dataset.teamMembers || '[]');
        } catch (e) {
            console.error('Erro ao fazer parse dos membros do time:', e);
        }

        let selectedProfessionalData = null;
        try {
            if (el.dataset.selectedProfessional && el.dataset.selectedProfessional !== 'null') {
                selectedProfessionalData = JSON.parse(el.dataset.selectedProfessional);
            }
        } catch (e) {
            console.error('Erro ao fazer parse do profissional selecionado:', e);
        }

        const app = createApp(BookingStepper, {
            servicesData: servicesData,
            blockedSlotsData: blockedSlotsData,
            teamMembersData: teamMembersData,
            selectedProfessionalData: selectedProfessionalData,
            storeUrl: el.dataset.storeUrl,
            slotsUrl: el.dataset.slotsUrl,
            csrfToken: el.dataset.csrfToken
        });
        app.mount('#vue-booking-app');
    }
});
