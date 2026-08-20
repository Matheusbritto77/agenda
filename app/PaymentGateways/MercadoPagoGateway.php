<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentSetting;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;

class MercadoPagoGateway implements PaymentGatewayInterface
{
    private PaymentSetting $setting;

    public function __construct(PaymentSetting $setting)
    {
        $this->setting = $setting;
        
        $credentials = $setting->credentials;
        $accessToken = $credentials['access_token'] ?? config('services.mercadopago.token') ?? env('MP_ACCESS_TOKEN');
        
        if ($accessToken) {
            MercadoPagoConfig::setAccessToken($accessToken);
        }
    }

    public function createPixPayment(float $amount, string $description, string $payerEmail, array $metadata = []): array
    {
        $client = new PaymentClient();

        $request = [
            "transaction_amount" => (float) $amount,
            "description" => $description,
            "payment_method_id" => "pix",
            "payer" => [
                "email" => $payerEmail,
            ],
            "metadata" => $metadata,
        ];

        $payment = $client->create($request);

        $transactionData = $payment->point_of_interaction->transaction_data ?? null;

        return [
            'gateway_payment_id' => (string) $payment->id,
            'status' => $this->normalizeStatus($payment->status),
            'pix_qr_code' => $transactionData->qr_code ?? '',
            'pix_qr_code_base64' => $transactionData->qr_code_base64 ?? '',
            'gateway_data' => json_decode(json_encode($payment), true) ?: [],
        ];
    }

    public function getPaymentStatus(string $gatewayPaymentId): string
    {
        $client = new PaymentClient();
        $payment = $client->get($gatewayPaymentId);
        return $this->normalizeStatus($payment->status);
    }

    public function handleWebhook(array $payload): array
    {
        $gatewayPaymentId = $payload['data']['id'] ?? $payload['id'] ?? null;
        if (!$gatewayPaymentId) {
            throw new \InvalidArgumentException('Gateway payment ID not found in payload.');
        }

        $status = $this->getPaymentStatus($gatewayPaymentId);

        return [
            'gateway_payment_id' => (string) $gatewayPaymentId,
            'status' => $status,
        ];
    }

    public function getName(): string
    {
        return 'mercadopago';
    }

    private function normalizeStatus(?string $status): string
    {
        return match ($status) {
            'approved' => 'approved',
            'pending', 'in_process' => 'pending',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
