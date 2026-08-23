<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\PaymentGateways\PaymentGatewayFactory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PaymentController extends Controller
{
    public function createPixForAppointment(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'appointment_id' => ['required', 'exists:appointments,id'],
        ])->validate();

        $appointment = Appointment::with('service')->findOrFail($validated['appointment_id']);
        
        $tenantId = $appointment->user_id;

        $paymentSetting = PaymentSetting::query()
            ->where('user_id', $tenantId)
            ->where('gateway', 'mercadopago')
            ->first();

        if (!$paymentSetting || !$paymentSetting->is_active) {
            return $this->jsonError($request, 'O pagamento online não está ativo para este estabelecimento.', 400);
        }

        $existingPayment = Payment::query()
            ->where('appointment_id', $appointment->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existingPayment) {
            return $this->jsonSuccess($request, 'Pagamento pendente recuperado com sucesso.', [
                'payment' => $existingPayment,
            ]);
        }

        try {
            $gateway = PaymentGatewayFactory::make($paymentSetting);
            $amount = (float) $appointment->service->price;
            $description = "Agendamento de " . $appointment->service->name . " - " . $appointment->client_name;
            $payerEmail = $appointment->client_email;
            
            $pixExpirationMinutes = $paymentSetting->settings['pix_expiration_minutes'] ?? 30;
            
            $gatewayResponse = $gateway->createPixPayment($amount, $description, $payerEmail, [
                'appointment_id' => $appointment->id,
            ]);

            $paymentRecord = Payment::create([
                'user_id' => $tenantId,
                'appointment_id' => $appointment->id,
                'gateway' => 'mercadopago',
                'gateway_payment_id' => $gatewayResponse['gateway_payment_id'],
                'method' => 'pix',
                'amount' => $amount,
                'status' => 'pending',
                'pix_qr_code' => $gatewayResponse['pix_qr_code'],
                'pix_qr_code_base64' => $gatewayResponse['pix_qr_code_base64'],
                'gateway_data' => $gatewayResponse['gateway_data'],
                'expires_at' => Carbon::now()->addMinutes($pixExpirationMinutes),
            ]);

            $appointment->update([
                'payment_id' => $paymentRecord->id,
                'payment_status' => 'pending',
            ]);

            return $this->jsonSuccess($request, 'Pagamento PIX gerado com sucesso.', [
                'payment' => $paymentRecord,
            ]);
        } catch (Throwable $e) {
            $this->reportThrowable($e);
            Log::error('Erro ao gerar pagamento PIX: ' . $e->getMessage());
            return $this->jsonError($request, 'Não foi possível gerar o pagamento PIX. Tente novamente mais tarde.', 500);
        }
    }

    public function checkStatus(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === 'approved') {
            return $this->jsonSuccess($request, 'Pagamento aprovado.', [
                'status' => 'approved',
            ]);
        }

        if ($payment->status === 'pending') {
            if ($payment->expires_at && Carbon::now()->greaterThan($payment->expires_at)) {
                $payment->update(['status' => 'cancelled']);
                $payment->appointment->update(['payment_status' => 'failed']);
                return $this->jsonSuccess($request, 'Pagamento expirado.', [
                    'status' => 'cancelled',
                ]);
            }

            try {
                $paymentSetting = PaymentSetting::query()
                    ->where('user_id', $payment->user_id)
                    ->where('gateway', 'mercadopago')
                    ->first();

                $gateway = PaymentGatewayFactory::make($paymentSetting);
                $status = $gateway->getPaymentStatus($payment->gateway_payment_id);

                if ($status !== $payment->status) {
                    $payment->update([
                        'status' => $status,
                        'paid_at' => $status === 'approved' ? Carbon::now() : null,
                    ]);

                    $appStatus = match ($status) {
                        'approved' => 'paid',
                        'rejected', 'cancelled' => 'failed',
                        default => 'pending',
                    };
                    
                    $payment->appointment->update([
                        'payment_status' => $appStatus,
                        'status' => $status === 'approved' ? 'confirmed' : $payment->appointment->status,
                    ]);
                }
            } catch (Throwable $e) {
                Log::warning('Erro ao consultar status do pagamento no gateway: ' . $e->getMessage());
            }
        }

        return $this->jsonSuccess($request, 'Status do pagamento consultado.', [
            'status' => $payment->status,
            'is_approved' => $payment->status === 'approved',
            'appointment' => $payment->appointment,
        ]);
    }

    public function webhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Mercado Pago Webhook recebido:', $payload);

        $topic = $request->input('topic') ?? $request->input('type');
        
        if ($topic !== 'payment') {
            return response()->json(['message' => 'Tópico ignorado.'], 200);
        }

        try {
            $gatewayPaymentId = $payload['data']['id'] ?? $payload['id'] ?? null;
            if (!$gatewayPaymentId) {
                return response()->json(['message' => 'ID do pagamento não enviado.'], 400);
            }

            $payment = Payment::where('gateway_payment_id', (string) $gatewayPaymentId)->first();
            if (!$payment) {
                return response()->json(['message' => 'Pagamento não encontrado no sistema.'], 404);
            }

            $paymentSetting = PaymentSetting::query()
                ->where('user_id', $payment->user_id)
                ->where('gateway', 'mercadopago')
                ->first();

            $gateway = PaymentGatewayFactory::make($paymentSetting);
            $webhookData = $gateway->handleWebhook($payload);

            if ($webhookData['status'] !== $payment->status) {
                $payment->update([
                    'status' => $webhookData['status'],
                    'paid_at' => $webhookData['status'] === 'approved' ? Carbon::now() : null,
                ]);

                $appStatus = match ($webhookData['status']) {
                    'approved' => 'paid',
                    'rejected', 'cancelled' => 'failed',
                    default => 'pending',
                };

                $payment->appointment->update([
                    'payment_status' => $appStatus,
                    'status' => $webhookData['status'] === 'approved' ? 'confirmed' : $payment->appointment->status,
                ]);
            }

            return response()->json(['message' => 'Webhook processado com sucesso.'], 200);
        } catch (Throwable $e) {
            Log::error('Erro ao processar webhook de pagamento: ' . $e->getMessage());
            return response()->json(['message' => 'Erro interno ao processar webhook.'], 500);
        }
    }
}
