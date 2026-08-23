<?php

namespace Tests\Feature\Client;

use App\Models\Appointment;
use App\Models\ClientAccount;
use App\Models\CompanyReview;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientCompanyReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_review_visited_company(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'barbearia-top',
            'active_domain_type' => 'subdomain',
        ]);

        $service = Service::create([
            'user_id' => $tenant->id,
            'name' => 'Corte Moderno',
            'duration_minutes' => 30,
            'price' => 45.00,
            'is_active' => true,
        ]);

        $client = ClientAccount::create([
            'name' => 'Mateus Santos',
            'email' => 'mateus@example.com',
            'password' => 'password123',
            'must_reset_password' => false,
        ]);

        Appointment::create([
            'user_id' => $tenant->id,
            'service_id' => $service->id,
            'client_account_id' => $client->id,
            'client_name' => 'Mateus Santos',
            'client_email' => 'mateus@example.com',
            'client_phone' => '11999998888',
            'appointment_date' => '2026-08-20',
            'appointment_time' => '10:00',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($client, 'client')
            ->post(route('client.companies.review', $tenant->id), [
                'rating' => 5,
                'comment' => 'Melhor barbearia da cidade! Atendimento e ambiente impecáveis.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('company_reviews', [
            'user_id' => $tenant->id,
            'client_account_id' => $client->id,
            'rating' => 5,
            'comment' => 'Melhor barbearia da cidade! Atendimento e ambiente impecáveis.',
            'is_public' => true,
        ]);
    }

    public function test_client_cannot_review_unvisited_company(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'outra-empresa',
        ]);

        $client = ClientAccount::create([
            'name' => 'Carlos Lima',
            'email' => 'carlos@example.com',
            'password' => 'password123',
            'must_reset_password' => false,
        ]);

        $response = $this->actingAs($client, 'client')
            ->post(route('client.companies.review', $tenant->id), [
                'rating' => 5,
                'comment' => 'Tentativa de spam',
            ]);

        $response->assertForbidden();
    }
}
