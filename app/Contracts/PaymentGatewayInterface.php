<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Cria um pagamento PIX.
     * Retorna array com chaves: gateway_payment_id, status, pix_qr_code, pix_qr_code_base64, gateway_data.
     */
    public function createPixPayment(float $amount, string $description, string $payerEmail, array $metadata = []): array;

    /**
     * Consulta o status do pagamento no gateway.
     */
    public function getPaymentStatus(string $gatewayPaymentId): string;

    /**
     * Processa a notificação/webhook recebido do gateway.
     * Retorna array com chaves: gateway_payment_id, status.
     */
    public function handleWebhook(array $payload): array;

    /**
     * Retorna o identificador do gateway.
     */
    public function getName(): string;
}
