<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentSetting;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use Illuminate\Support\Facades\Log;

class MercadoPagoGateway implements PaymentGatewayInterface
{
    private PaymentSetting $setting;
    private ?string $accessToken;

    public function __construct(PaymentSetting $setting)
    {
        $this->setting = $setting;
        
        $credentials = $setting->credentials;
        $this->accessToken = $credentials['access_token'] ?? config('services.mercadopago.token') ?? env('MP_ACCESS_TOKEN');
        
        if ($this->accessToken) {
            MercadoPagoConfig::setAccessToken($this->accessToken);
        }
    }

    public function createPixPayment(float $amount, string $description, string $payerEmail, array $metadata = []): array
    {
        // Safe mock during testing with dummy test tokens
        if (app()->environment('testing') && (!$this->accessToken || str_starts_with($this->accessToken, 'TEST-'))) {
            return [
                'gateway_payment_id' => 'mock_pix_' . uniqid(),
                'status' => 'pending',
                'pix_qr_code' => '00020126580014br.gov.bcb.pix2536agendae.app/pix/mock',
                'pix_qr_code_base64' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'gateway_data' => ['mock' => true],
            ];
        }

        $client = new PaymentClient();

        $appUrl = rtrim(config('app.url') ?? env('APP_URL') ?? url('/'), '/');
        $notificationUrl = $appUrl . '/webhooks/mercadopago';

        $request = [
            "transaction_amount" => (float) $amount,
            "description" => $description,
            "payment_method_id" => "pix",
            "notification_url" => $notificationUrl,
            "payer" => [
                "email" => $payerEmail,
            ],
            "metadata" => $metadata,
        ];

        try {
            $payment = $client->create($request);
            $transactionData = $payment->point_of_interaction->transaction_data ?? null;

            return [
                'gateway_payment_id' => (string) $payment->id,
                'status' => $this->normalizeStatus($payment->status),
                'pix_qr_code' => $transactionData->qr_code ?? '',
                'pix_qr_code_base64' => $transactionData->qr_code_base64 ?? '',
                'gateway_data' => json_decode(json_encode($payment), true) ?: [],
            ];
        } catch (\Throwable $e) {
            Log::error('MercadoPagoGateway::createPixPayment error: ' . $e->getMessage(), [
                'request' => $request,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    public function getPaymentStatus(string $gatewayPaymentId): string
    {
        if (str_starts_with($gatewayPaymentId, 'mock_') || (app()->environment('testing') && (!$this->accessToken || str_starts_with($this->accessToken, 'TEST-')))) {
            return 'approved';
        }

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
