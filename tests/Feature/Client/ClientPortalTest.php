<?php

namespace Tests\Feature\Client;

use App\Models\Appointment;
use App\Models\ClientAccount;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\AppointmentCompletedForBusiness;
use App\Notifications\AppointmentCompletedForClient;
use App\Notifications\AppointmentConfirmedForBusiness;
use App\Notifications\AppointmentConfirmedForClient;
use App\Notifications\ClientAccountCreated;
use App\Services\ClientPortalProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_the_client_login_instead_of_the_admin_login(): void
    {
        $this->get(route('client.dashboard'))
            ->assertRedirect(route('client.login'));
    }

    public function test_confirming_an_appointment_creates_client_access_and_notifies_client_and_company(): void
    {
        Notification::fake();
        [$company, $appointment] = $this->appointment();

        $account = app(ClientPortalProvisioningService::class)->provisionFor($appointment);

        $this->assertNotNull($account);
        $this->assertSame('cliente@example.com', $account->email);
        $this->assertTrue($account->must_reset_password);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'client_account_id' => $account->id,
            'user_id' => $company->id,
        ]);

        Notification::assertSentTo($account, AppointmentConfirmedForClient::class);
        Notification::assertSentTo($account, ClientAccountCreated::class);
        Notification::assertSentOnDemand(
            AppointmentConfirmedForBusiness::class,
            fn ($notification, $channels, $notifiable): bool => $notifiable->routeNotificationFor('mail') === $company->email
        );
    }

    public function test_confirmation_is_also_sent_to_the_linked_professional_without_duplicates(): void
    {
        Notification::fake();
        [$company, $appointment] = $this->appointment();
        $company->update(['email' => 'empresa@example.com']);
        $professional = TeamMember::create([
            'user_id' => $company->id,
            'name' => 'Profissional Exemplo',
            'email' => 'profissional@example.com',
            'is_active' => true,
        ]);
        $appointment->update(['team_member_id' => $professional->id]);

        app(ClientPortalProvisioningService::class)->provisionFor($appointment);

        Notification::assertSentOnDemandTimes(AppointmentConfirmedForBusiness::class, 2);
        foreach (['empresa@example.com', 'profissional@example.com'] as $email) {
            Notification::assertSentOnDemand(
                AppointmentConfirmedForBusiness::class,
                fn ($notification, $channels, $notifiable): bool => $notifiable->routeNotificationFor('mail') === $email
            );
        }
    }

    public function test_completion_notifies_client_company_and_linked_professional(): void
    {
        Notification::fake();
        [$company, $appointment] = $this->appointment();
        $company->update(['email' => 'empresa@example.com']);
        $professional = TeamMember::create([
            'user_id' => $company->id,
            'name' => 'Profissional Exemplo',
            'email' => 'profissional@example.com',
            'is_active' => true,
        ]);
        $appointment->update(['team_member_id' => $professional->id]);

        $this->actingAs($company)
            ->patch(route('admin.appointments.update-status', $appointment), ['status' => 'completed'])
            ->assertRedirect();

        Notification::assertSentOnDemand(
            AppointmentCompletedForClient::class,
            fn ($notification, $channels, $notifiable): bool => $notifiable->routeNotificationFor('mail') === 'cliente@example.com'
        );
        Notification::assertSentOnDemandTimes(AppointmentCompletedForBusiness::class, 2);
        foreach (['empresa@example.com', 'profissional@example.com'] as $email) {
            Notification::assertSentOnDemand(
                AppointmentCompletedForBusiness::class,
                fn ($notification, $channels, $notifiable): bool => $notifiable->routeNotificationFor('mail') === $email
            );
        }
    }

    public function test_existing_account_is_reused_and_past_appointments_are_linked(): void
    {
        Notification::fake();
        $account = ClientAccount::create([
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'password' => Hash::make('password'),
            'must_reset_password' => false,
        ]);
        [, $pastAppointment] = $this->appointment(['status' => 'completed']);
        [, $newAppointment] = $this->appointment([
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '15:00',
        ]);

        $resolved = app(ClientPortalProvisioningService::class)->provisionFor($newAppointment);

        $this->assertTrue($account->is($resolved));
        $this->assertSame($account->id, $pastAppointment->fresh()->client_account_id);
        $this->assertSame($account->id, $newAppointment->fresh()->client_account_id);
        $this->assertDatabaseCount('client_accounts', 1);
        Notification::assertSentTo($account, AppointmentConfirmedForClient::class);
        Notification::assertNotSentTo($account, ClientAccountCreated::class);
    }

    public function test_client_must_change_temporary_password_before_opening_dashboard(): void
    {
        $account = ClientAccount::create([
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'password' => Hash::make('temporary-password'),
            'must_reset_password' => true,
        ]);

        $this->actingAs($account, 'client')
            ->get(route('client.dashboard'))
            ->assertRedirect(route('client.password.edit'));

        $this->actingAs($account, 'client')
            ->put(route('client.password.update'), [
                'current_password' => 'temporary-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('client.dashboard'));

        $this->assertFalse($account->fresh()->must_reset_password);
        $this->assertTrue(Hash::check('new-secure-password', $account->fresh()->password));
    }

    public function test_dashboard_combines_companies_and_completed_services(): void
    {
        $account = ClientAccount::create([
            'name' => 'Cliente Exemplo',
            'email' => 'cliente@example.com',
            'password' => Hash::make('password'),
            'must_reset_password' => false,
        ]);
        [$firstCompany, $first] = $this->appointment(['status' => 'completed']);
        [$secondCompany, $second] = $this->appointment([
            'status' => 'confirmed',
            'appointment_date' => now()->addDays(4)->toDateString(),
            'appointment_time' => '16:00',
        ]);
        $secondCompany->update(['name' => 'Segunda Empresa']);
        $first->update(['client_account_id' => $account->id]);
        $second->update(['client_account_id' => $account->id]);

        // Default view: scoped to active company with full companies list
        $this->actingAs($account, 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Portal/Dashboard')
                ->where('summary.companies', 2)
                ->has('companies', 2)
                ->has('activeCompany')
            );

        // Explicit view: consolidated across all companies
        $this->actingAs($account, 'client')
            ->get(route('client.dashboard', ['empresa' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Portal/Dashboard')
                ->where('summary.appointments', 2)
                ->where('summary.completed', 1)
                ->where('summary.companies', 2)
                ->where('activeCompany', null)
                ->has('companies', 2)
                ->has('appointments', 2)
                ->where('badges.0.earned', true)
                ->where('badges.1.earned', false)
            );
    }

    public function test_client_can_switch_company_context_via_endpoint(): void
    {
        $account = ClientAccount::create([
            'name' => 'Cliente Multi',
            'email' => 'cliente.multi@example.com',
            'password' => Hash::make('password'),
            'must_reset_password' => false,
        ]);
        [$companyA, $aptA] = $this->appointment(['status' => 'completed']);
        [$companyB, $aptB] = $this->appointment(['status' => 'confirmed']);
        $companyB->update(['name' => 'Barbearia B']);
        $aptA->update(['client_account_id' => $account->id]);
        $aptB->update(['client_account_id' => $account->id]);

        $this->actingAs($account, 'client')
            ->post(route('client.companies.select', $companyB->id))
            ->assertRedirect(route('client.dashboard'));

        $this->actingAs($account, 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Portal/Dashboard')
                ->where('activeCompany.id', $companyB->id)
                ->where('activeCompany.name', 'Barbearia B')
                ->has('appointments', 1)
                ->where('appointments.0.id', $aptB->id)
            );
    }

    public function test_client_can_review_only_their_completed_appointment(): void
    {
        $account = ClientAccount::create([
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'password' => Hash::make('password'),
            'must_reset_password' => false,
        ]);
        [, $completed] = $this->appointment(['status' => 'completed']);
        [, $confirmed] = $this->appointment([
            'status' => 'confirmed',
            'appointment_date' => now()->addDays(5)->toDateString(),
            'appointment_time' => '17:00',
        ]);
        $completed->update(['client_account_id' => $account->id]);
        $confirmed->update(['client_account_id' => $account->id]);

        $this->actingAs($account, 'client')
            ->put(route('client.reviews.store', $completed), [
                'rating' => 5,
                'comment' => '<b>Atendimento excelente!</b>',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointment_reviews', [
            'appointment_id' => $completed->id,
            'client_account_id' => $account->id,
            'rating' => 5,
            'comment' => 'Atendimento excelente!',
        ]);

        $this->actingAs($account, 'client')
            ->put(route('client.reviews.store', $confirmed), [
                'rating' => 5,
                'comment' => 'Ainda não aconteceu',
            ])
            ->assertStatus(422);
    }

    private function appointment(array $overrides = []): array
    {
        $company = User::factory()->create();
        $service = Service::create([
            'user_id' => $company->id,
            'name' => 'Corte Premium',
            'price' => 80,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $appointment = Appointment::create(array_merge([
            'user_id' => $company->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Exemplo',
            'client_email' => 'cliente@example.com',
            'client_phone' => '11999999999',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'appointment_time' => '14:00',
            'status' => 'confirmed',
        ], $overrides));

        return [$company, $appointment];
    }
}
