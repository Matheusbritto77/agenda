<?php

namespace Tests\Feature\Client;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingMandatoryPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $this->tenant = User::factory()->create([
            'subdomain' => 'barbearia-pix',
            'custom_domain' => null,
            'active_domain_type' => 'subdomain',
        ]);

        foreach (range(0, 6) as $dayOfWeek) {
            BusinessHour::create([
                'user_id' => $this->tenant->id,
                'day_of_week' => $dayOfWeek,
                'opens_at' => '08:00:00',
                'closes_at' => '18:00:00',
                'label' => 'Agenda Base',
                'slot_duration_minutes' => 30,
                'is_active' => true,
            ]);
        }
    }

    public function test_booking_with_active_payment_setting_creates_pix_payment(): void
    {
        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Corte & Barba Premium',
            'duration_minutes' => 60,
            'price' => 75.00,
            'is_active' => true,
        ]);

        PaymentSetting::create([
            'user_id' => $this->tenant->id,
            'gateway' => 'mercadopago',
            'is_active' => true,
            'credentials' => ['access_token' => 'TEST-123456'],
            'settings' => ['pix_expiration_minutes' => 15],
        ]);

        $targetDate = Carbon::now()->addDays(1)->format('Y-m-d');

        $response = $this->postJson('http://barbearia-pix.agendae.app/booking', [
            'service_id' => $service->id,
            'appointment_date' => $targetDate,
            'appointment_time' => '14:00',
            'client_name' => 'João Silva',
            'client_email' => 'joao@example.com',
            'client_phone' => '11999998888',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'appointment',
            'paymentDetails' => [
                'payment_id',
                'pix_copy_paste',
                'pix_qr_code_base64',
                'amount',
            ],
        ]);

        $this->assertDatabaseHas('appointments', [
            'user_id' => $this->tenant->id,
            'client_name' => 'João Silva',
            'payment_status' => 'pending',
        ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->tenant->id,
            'gateway' => 'mercadopago',
            'status' => 'pending',
            'amount' => 75.00,
        ]);
    }

    public function test_mercadopago_webhook_confirms_appointment_safely(): void
    {
        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Corte Simples',
            'duration_minutes' => 30,
            'price' => 50.00,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'user_id' => $this->tenant->id,
            'service_id' => $service->id,
            'client_name' => 'Lucas Santos',
            'client_email' => 'lucas@example.com',
            'client_phone' => '11988887777',
            'appointment_date' => '2026-08-25',
            'appointment_time' => '14:00',
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        $paymentSetting = PaymentSetting::create([
            'user_id' => $this->tenant->id,
            'gateway' => 'mercadopago',
            'is_active' => true,
            'credentials' => ['access_token' => 'TEST-123456'],
        ]);

        $payment = Payment::create([
            'user_id' => $this->tenant->id,
            'appointment_id' => $appointment->id,
            'gateway' => 'mercadopago',
            'gateway_payment_id' => '123456789',
            'method' => 'pix',
            'amount' => 50.00,
            'status' => 'pending',
        ]);

        $appointment->update(['payment_id' => $payment->id]);

        $response = $this->postJson(route('webhooks.mercadopago'), [
            'type' => 'payment',
            'data' => [
                'id' => '123456789',
            ],
        ]);

        $response->assertOk();

        $payment->refresh();
        $this->assertEquals('approved', $payment->status);
        $this->assertNotNull($payment->paid_at);

        $appointment->refresh();
        $this->assertEquals('paid', $appointment->payment_status);
        $this->assertEquals('confirmed', $appointment->status);
    }
}
