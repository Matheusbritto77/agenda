<script setup>
import { ref, computed } from 'vue';
import PhoneInputWithCountry from '@/Components/PhoneInputWithCountry.vue';

const props = defineProps({
    activeProfessional: {
        type: Object,
        default: null,
    },
    selectedService: {
        type: Object,
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
    bookingForm: {
        type: Object,
        required: true,
    },
    paymentEnabled: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Dados & Confirmação',
    },
    confirmButtonLabel: {
        type: String,
        default: 'Confirmar Agendamento',
    },
    showNotes: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['prev-step', 'submit-booking']);

const couponInput = ref('');
const couponLoading = ref(false);
const couponError = ref('');
const appliedCoupon = ref(null);

const finalPrice = computed(() => {
    const original = Number(props.selectedService?.price || 0);
    if (!appliedCoupon.value) return original;
    return Math.max(0, original - appliedCoupon.value.discount_amount);
});

const applyCoupon = async () => {
    if (!couponInput.value.trim()) return;
    couponLoading.value = true;
    couponError.value = '';
    try {
        const response = await fetch(route('booking.coupons.validate'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                code: couponInput.value.trim(),
                service_id: props.selectedService?.id,
                client_email: props.bookingForm?.client_email || props.bookingForm?.customer_email || '',
            }),
        });

        const data = await response.json();
        if (response.ok && data.valid) {
            appliedCoupon.value = data;
            props.bookingForm.coupon_code = data.code;
        } else {
            couponError.value = data.message || 'Cupom inválido ou não aplicável.';
            appliedCoupon.value = null;
            props.bookingForm.coupon_code = '';
        }
    } catch (e) {
        couponError.value = 'Erro ao verificar cupom. Tente novamente.';
    } finally {
        couponLoading.value = false;
    }
};

const removeCoupon = () => {
    appliedCoupon.value = null;
    couponInput.value = '';
    couponError.value = '';
    props.bookingForm.coupon_code = '';
};

const formatDateLong = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
};

const formatCurrency = (val) => Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <div class="space-y-6">
        <!-- Summary Box -->
        <div class="card p-5 sm:p-6 border space-y-4 shadow-sm" :style="{ backgroundColor: 'var(--primary-light)', borderColor: 'var(--primary)' }">
            <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider" :style="{ color: 'var(--primary)' }">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Resumo da sua Reserva</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs sm:text-sm">
                <div v-if="activeProfessional" class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase opacity-60" :style="{ color: 'var(--text-muted)' }">Profissional</span>
                    <p class="font-extrabold" :style="{ color: 'var(--primary)' }">{{ activeProfessional.name }}</p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase opacity-60" :style="{ color: 'var(--text-muted)' }">Serviço</span>
                    <p class="font-extrabold" :style="{ color: 'var(--text-heading)' }">{{ selectedService?.name }}</p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase opacity-60" :style="{ color: 'var(--text-muted)' }">Data</span>
                    <p class="font-bold capitalize" :style="{ color: 'var(--text)' }">{{ formatDateLong(selectedDate) }}</p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold uppercase opacity-60" :style="{ color: 'var(--text-muted)' }">Horário</span>
                    <p class="font-bold" :style="{ color: 'var(--text)' }">{{ selectedTime }} ({{ selectedService?.duration_minutes || 30 }} min)</p>
                </div>

                <div class="space-y-0.5 sm:col-span-2">
                    <span class="text-[10px] font-bold uppercase opacity-60" :style="{ color: 'var(--text-muted)' }">Valor a Pagar</span>
                    <div class="flex items-baseline gap-2">
                        <p class="text-base sm:text-lg font-black" :style="{ color: 'var(--primary)' }">
                            R$ {{ formatCurrency(finalPrice) }}
                        </p>
                        <span v-if="appliedCoupon" class="text-xs text-slate-400 line-through">
                            R$ {{ formatCurrency(selectedService?.price) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Coupon Input Block -->
            <div class="pt-3 border-t border-indigo-500/20 space-y-2">
                <label class="text-[11px] font-extrabold uppercase tracking-wider block opacity-80" :style="{ color: 'var(--text-heading)' }">
                    <i class="fa-solid fa-ticket text-indigo-500 mr-1"></i> Possui cupom de desconto?
                </label>
                <div v-if="!appliedCoupon" class="flex gap-2">
                    <input
                        type="text"
                        v-model="couponInput"
                        placeholder="Digite o código (ex: VIP10)"
                        class="form-control text-xs uppercase font-bold tracking-wider rounded-xl flex-1"
                        @keydown.enter.prevent="applyCoupon"
                    />
                    <button
                        type="button"
                        @click="applyCoupon"
                        :disabled="couponLoading || !couponInput.trim()"
                        class="btn btn-outline text-xs px-3.5 py-2 rounded-xl font-bold cursor-pointer shrink-0"
                    >
                        <i v-if="couponLoading" class="fa-solid fa-spinner fa-spin"></i>
                        <span v-else>Aplicar</span>
                    </button>
                </div>
                <div v-else class="p-2.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400"></i>
                        <span class="font-extrabold text-emerald-800 dark:text-emerald-200">
                            Cupom <strong>{{ appliedCoupon.code }}</strong> aplicado: -{{ appliedCoupon.formatted_discount_amount }}
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="removeCoupon"
                        class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-1 rounded cursor-pointer"
                        title="Remover cupom"
                    >
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
                <p v-if="couponError" class="text-xs text-rose-500 font-medium">{{ couponError }}</p>
            </div>
        </div>

        <!-- Customer Details Form -->
        <form @submit.prevent="$emit('submit-booking', paymentEnabled)" class="card p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-extrabold" :style="{ color: 'var(--text-heading)' }">
                    {{ title }}
                </h4>
                <span v-if="paymentEnabled" class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                    <i class="fa-brands fa-pix text-[11px]"></i>
                    <span>Pagamento PIX Obrigatório</span>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group mb-0">
                    <label class="form-label text-xs font-bold block mb-1" for="form_client_name" :style="{ color: 'var(--text-heading)' }">Nome Completo *</label>
                    <input
                        type="text"
                        id="form_client_name"
                        v-model="bookingForm.client_name"
                        class="form-control text-xs sm:text-sm rounded-xl"
                        placeholder="Ex: Maria da Silva"
                        required
                    />
                    <span v-if="bookingForm.errors?.client_name" class="text-xs text-rose-500 mt-1 block">{{ bookingForm.errors.client_name }}</span>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label text-xs font-bold block mb-1" for="form_client_email" :style="{ color: 'var(--text-heading)' }">E-mail *</label>
                    <input
                        type="email"
                        id="form_client_email"
                        v-model="bookingForm.client_email"
                        class="form-control text-xs sm:text-sm rounded-xl"
                        placeholder="seu@email.com"
                        required
                    />
                    <span v-if="bookingForm.errors?.client_email" class="text-xs text-rose-500 mt-1 block">{{ bookingForm.errors.client_email }}</span>
                </div>

                <div class="form-group mb-0 md:col-span-2">
                    <PhoneInputWithCountry
                        id="form_client_phone"
                        label="WhatsApp / Telefone *"
                        v-model="bookingForm.client_phone"
                        v-model:countryCode="bookingForm.country_code"
                        :error="bookingForm.errors?.client_phone"
                        placeholder="(00) 00000-0000"
                        :required="true"
                    />
                </div>

                <div v-if="showNotes" class="form-group mb-0 md:col-span-2">
                    <label class="form-label text-xs font-bold block mb-1" for="form_notes" :style="{ color: 'var(--text-heading)' }">Observações (opcional)</label>
                    <textarea
                        id="form_notes"
                        v-model="bookingForm.notes"
                        rows="2"
                        class="form-control text-xs sm:text-sm rounded-xl"
                        placeholder="Alguma preferência ou informação relevante para o profissional?"
                    ></textarea>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between gap-3 pt-4 border-t" :style="{ borderColor: 'var(--border, #e2e8f0)' }">
                <button
                    type="button"
                    @click="$emit('prev-step')"
                    class="btn btn-outline py-2.5 px-4 text-xs font-bold rounded-xl cursor-pointer"
                >
                    <i class="fa-solid fa-arrow-left text-xs mr-1"></i>
                    Voltar
                </button>

                <div class="flex items-center gap-2">
                    <!-- When PIX payment is enabled, "Agendar sem pagar" is NOT shown. Only "Pagar com PIX & Agendar" is available. -->
                    <button
                        type="submit"
                        :disabled="bookingForm.processing"
                        class="btn btn-primary py-2.5 px-6 text-xs font-bold rounded-xl shadow-md cursor-pointer inline-flex items-center gap-2"
                        :style="{
                            backgroundColor: 'var(--primary)',
                            color: 'var(--btn-text, #ffffff)'
                        }"
                    >
                        <i v-if="bookingForm.processing" class="fa-solid fa-spinner fa-spin text-xs"></i>
                        <i v-else-if="paymentEnabled" class="fa-brands fa-pix text-xs"></i>
                        <i v-else class="fa-solid fa-calendar-check text-xs"></i>
                        <span>{{ bookingForm.processing ? 'Gerando Pagamento...' : (paymentEnabled ? 'Pagar com PIX & Agendar' : confirmButtonLabel) }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>
