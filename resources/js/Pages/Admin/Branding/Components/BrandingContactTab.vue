<script setup>
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
            <label class="form-label text-xs font-bold block" for="whatsapp_number">Número de WhatsApp para Atendimento</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-emerald-500">
                    <i class="fa-brands fa-whatsapp text-base"></i>
                </div>
                <input
                    type="text"
                    id="whatsapp_number"
                    v-model="form.whatsapp_number"
                    class="form-control pl-10 text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: (11) 99999-8888 ou 5511999998888"
                />
            </div>
        </div>

        <!-- WhatsApp Floating Button Toggle -->
        <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Botão Flutuante de WhatsApp</p>
                <p class="text-[11px] text-slate-400">Exibir ícone flutuante no canto da tela para clientes tirarem dúvidas direto no seu WhatsApp.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input
                    type="checkbox"
                    v-model="form.whatsapp_button_enabled"
                    class="sr-only peer"
                />
                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
            </label>
        </div>

        <!-- Instagram Handle -->
        <div class="form-group mb-0">
            <label class="form-label text-xs font-bold block" for="instagram_handle">Perfil do Instagram (Opcional)</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-pink-500">
                    <i class="fa-brands fa-instagram text-base"></i>
                </div>
                <input
                    type="text"
                    id="instagram_handle"
                    v-model="form.instagram_handle"
                    class="form-control pl-10 text-xs sm:text-sm rounded-xl"
                    placeholder="Ex: @minhaempresa"
                />
            </div>
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
