<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\PaymentSetting;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $subUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'parent_id' => null,
            'subdomain' => 'testcompany',
            'active_domain_type' => 'subdomain',
        ]);

        $this->subUser = User::factory()->create([
            'parent_id' => $this->owner->id,
            'email' => 'professional@example.com',
        ]);

        for ($i = 0; $i <= 6; $i++) {
            \App\Models\BusinessHour::create([
                'user_id' => $this->owner->id,
                'day_of_week' => $i,
                'opens_at' => '08:00:00',
                'closes_at' => '18:00:00',
                'slot_duration_minutes' => 30,
                'is_active' => true,
            ]);
        }
    }

    public function test_owner_can_access_integrations_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.integrations.index'));
        $response->assertStatus(200);
    }

    public function test_subuser_cannot_access_integrations_without_permission(): void
    {
        $response = $this->actingAs($this->subUser)->get(route('admin.integrations.index'));
        $response->assertStatus(403);
    }

    public function test_owner_can_save_payment_settings(): void
    {
        $response = $this->actingAs($this->owner)->post(route('admin.integrations.payments.update'), [
            'gateway' => 'mercadopago',
            'is_active' => true,
            'access_token' => 'TEST-ACCESS-TOKEN-VALUE',
            'settings' => [
                'pix_expiration_minutes' => 45,
            ],
        ]);

        $response->assertRedirect();
        
        $setting = PaymentSetting::where('user_id', $this->owner->id)->first();
        $this->assertNotNull($setting);
        $this->assertTrue($setting->is_active);
        $this->assertEquals('mercadopago', $setting->gateway);
        $this->assertEquals('TEST-ACCESS-TOKEN-VALUE', $setting->credentials['access_token']);
        $this->assertEquals(45, $setting->settings['pix_expiration_minutes']);
    }

    public function test_can_book_without_payment(): void
    {
        $service = Service::create([
            'user_id' => $this->owner->id,
            'name' => 'Corte Masculino',
            'price' => 40.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->post('http://testcompany.localhost/booking', [
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '10:00',
            'client_name' => 'Cliente Teste',
            'client_email' => 'client@example.com',
            'client_phone' => '11999998888',
            'pay_now' => false,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(201);
        
        $appointment = Appointment::first();
        $this->assertNotNull($appointment);
        $this->assertEquals('none', $appointment->payment_status);
        $this->assertNull($appointment->payment_id);
    }

    public function test_can_book_and_generate_payment(): void
    {
        PaymentSetting::create([
            'user_id' => $this->owner->id,
            'gateway' => 'mercadopago',
            'is_active' => true,
            'credentials' => ['access_token' => 'TEST-TOKEN'],
            'settings' => ['pix_expiration_minutes' => 30],
        ]);

        $service = Service::create([
            'user_id' => $this->owner->id,
            'name' => 'Corte Completo',
            'price' => 50.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->post('http://testcompany.localhost/booking', [
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '11:00',
            'client_name' => 'Cliente Pagador',
            'client_email' => 'pagador@example.com',
            'client_phone' => '11999998888',
            'pay_now' => true,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(201);
        
        $appointment = Appointment::first();
        $this->assertNotNull($appointment);
        $this->assertEquals('pending', $appointment->payment_status);

        $payResponse = $this->post(route('payment.pix.create'), [
            'appointment_id' => $appointment->id,
        ], [
            'Accept' => 'application/json',
        ]);

        $payResponse->assertStatus(200);
        $payResponse->assertJsonStructure([
            'message',
            'payment' => [
                'id',
                'gateway_payment_id',
                'pix_qr_code',
                'pix_qr_code_base64',
                'amount',
                'status',
            ]
        ]);

        $payment = Payment::first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals($appointment->id, $payment->appointment_id);
        $this->assertEquals(50.00, $payment->amount);

        $appointment->refresh();
        $this->assertEquals($payment->id, $appointment->payment_id);
        
        $statusResponse = $this->get(route('payment.status', $payment->id));
        $statusResponse->assertStatus(200);
        $this->assertTrue(in_array($statusResponse->json('status'), ['pending', 'approved', 'cancelled']));
    }
}
