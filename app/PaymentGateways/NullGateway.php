<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;

class NullGateway implements PaymentGatewayInterface
{
    public function createPixPayment(float $amount, string $description, string $payerEmail, array $metadata = []): array
    {
        return [
            'gateway_payment_id' => 'mock_id_' . uniqid(),
            'status' => 'pending',
            'pix_qr_code' => 'mock_qr_code_value',
            'pix_qr_code_base64' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'gateway_data' => ['is_mock' => true],
        ];
    }

    public function getPaymentStatus(string $gatewayPaymentId): string
    {
        return 'approved';
    }

    public function handleWebhook(array $payload): array
    {
        return [
            'gateway_payment_id' => (string) ($payload['data']['id'] ?? $payload['id'] ?? 'mock_id'),
            'status' => 'approved',
        ];
    }

    public function getName(): string
    {
        return 'null';
    }
}
