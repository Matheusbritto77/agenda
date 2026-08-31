<script setup>
import PhoneInputWithCountry from '@/Components/PhoneInputWithCountry.vue';

defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="space-y-5">
        <!-- WhatsApp -->
        <div class="form-group mb-0">
            <PhoneInputWithCountry
                id="whatsapp_number"
                label="Número de WhatsApp da Empresa / Atendimento"
                v-model="form.whatsapp_number"
                v-model:countryCode="form.country_code"
                placeholder="(00) 00000-0000"
            />
            <p class="text-[11px] text-slate-400 mt-1">Utilizado para botão de contato e envio de confirmações aos clientes.</p>
        </div>

        <!-- Instagram Handle -->
        <div class="form-group mb-0">
            <label class="form-label text-xs font-bold block" for="instagram_handle">
                <i class="fa-brands fa-instagram text-pink-500 mr-1.5"></i>
                Perfil do Instagram (Opcional)
            </label>
            <input
                type="text"
                id="instagram_handle"
                v-model="form.instagram_handle"
                class="form-control text-xs sm:text-sm rounded-xl"
                placeholder="Ex: @minhaempresa ou https://instagram.com/minhaempresa"
            />
        </div>

        <!-- Physical Address -->
        <div class="form-group mb-0">
            <label class="form-label text-xs font-bold block" for="company_address">
                <i class="fa-solid fa-location-dot text-indigo-500 mr-1.5"></i>
                Endereço Físico / Localização
            </label>
            <input
                type="text"
                id="company_address"
                v-model="form.company_address"
                class="form-control text-xs sm:text-sm rounded-xl"
                placeholder="Ex: Av. Paulista, 1000 - Sala 42, Bela Vista - São Paulo / SP"
            />
            <p class="text-[11px] text-slate-400 mt-1">Exibido nos cards de contato e nos detalhes do agendamento.</p>
        </div>

        <!-- Footer Text -->
        <div class="form-group mb-0">
            <label class="form-label text-xs font-bold block" for="footer_text">Texto Personalizado do Rodapé</label>
            <textarea
                id="footer_text"
                v-model="form.footer_text"
                rows="2"
                class="form-control text-xs sm:text-sm rounded-xl"
                placeholder="Ex: © 2026 Minha Barbearia. CNPJ 00.000.000/0001-00. Todos os direitos reservados."
            ></textarea>
        </div>
    </div>
</template>
