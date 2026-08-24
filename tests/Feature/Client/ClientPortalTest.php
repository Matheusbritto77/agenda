<?php

namespace Tests\Feature\Client;

use App\Models\Appointment;
use App\Models\BrandingSetting;
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

    public function test_dashboard_exposes_the_saved_company_portal_customization(): void
    {
        $account = ClientAccount::create([
            'name' => 'Cliente Personalizado',
            'email' => 'cliente.personalizado@example.com',
            'password' => Hash::make('password'),
            'must_reset_password' => false,
        ]);
        [$company, $appointment] = $this->appointment(['status' => 'completed']);
        $appointment->update(['client_account_id' => $account->id]);

        BrandingSetting::create([
            'user_id' => $company->id,
            'primary_color' => '#111827',
            'secondary_color' => '#334155',
            'settings' => [
                'portal_welcome_title' => 'Bem-vindo ao Clube Premium',
                'portal_welcome_subtitle' => 'Seu espaço personalizado',
                'portal_announcement' => 'Comunicado exclusivo',
                'portal_announcement_enabled' => true,
                'portal_primary_color' => '#8b5cf6',
                'portal_secondary_color' => '#ec4899',
                'portal_show_loyalty_badges' => false,
                'portal_show_reviews' => false,
                'portal_show_professionals' => false,
                'portal_show_service_prices' => false,
                'portal_support_whatsapp' => '11999998888',
                'portal_custom_instructions' => 'Chegue com dez minutos de antecedência.',
            ],
        ]);

        $this->actingAs($account, 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeCompany.welcome_title', 'Bem-vindo ao Clube Premium')
                ->where('activeCompany.welcome_subtitle', 'Seu espaço personalizado')
                ->where('activeCompany.announcement', 'Comunicado exclusivo')
                ->where('activeCompany.announcement_enabled', true)
                ->where('activeCompany.primary_color', '#8b5cf6')
                ->where('activeCompany.secondary_color', '#ec4899')
                ->where('activeCompany.show_loyalty_badges', false)
                ->where('activeCompany.show_reviews', false)
                ->where('activeCompany.show_professionals', false)
                ->where('activeCompany.show_service_prices', false)
                ->where('activeCompany.support_whatsapp', '11999998888')
                ->where('activeCompany.custom_instructions', 'Chegue com dez minutos de antecedência.')
                ->where('appointments.0.can_review', false)
                ->where('appointments.0.show_professionals', false)
                ->where('appointments.0.show_service_prices', false)
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

    public function test_client_can_update_profile_info_and_phone(): void
    {
        $account = ClientAccount::create([
            'name' => 'Nome Antigo',
            'email' => 'antigo@example.com',
            'phone' => '11999990000',
            'password' => Hash::make('password'),
            'must_reset_password' => false,
        ]);

        $this->actingAs($account, 'client')
            ->post(route('client.profile.update'), [
                'name' => 'Nome Novo',
                'email' => 'novo@example.com',
                'phone' => '11988887777',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $account->refresh();
        $this->assertSame('Nome Novo', $account->name);
        $this->assertSame('novo@example.com', $account->email);
        $this->assertSame('11988887777', $account->phone);
    }

    public function test_client_can_update_profile_avatar_and_remove_it(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $account = ClientAccount::create([
            'name' => 'Cliente Avatar',
            'email' => 'avatar@example.com',
            'password' => Hash::make('password'),
            'must_reset_password' => false,
        ]);

        $avatarFile = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $this->actingAs($account, 'client')
            ->post(route('client.profile.update'), [
                'name' => 'Cliente Avatar',
                'email' => 'avatar@example.com',
                'avatar' => $avatarFile,
            ])
            ->assertRedirect();

        $account->refresh();
        $this->assertNotNull($account->avatar_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($account->avatar_path);
        $this->assertNotNull($account->avatar_url);

        // Now remove avatar
        $this->actingAs($account, 'client')
            ->post(route('client.profile.update'), [
                'name' => 'Cliente Avatar',
                'email' => 'avatar@example.com',
                'remove_avatar' => true,
            ])
            ->assertRedirect();

        $account->refresh();
        $this->assertNull($account->avatar_path);
        $this->assertNull($account->avatar_url);
    }

    public function test_client_can_update_password_with_current_password(): void
    {
        $account = ClientAccount::create([
            'name' => 'Cliente Senha',
            'email' => 'senha@example.com',
            'password' => Hash::make('antiga-senha'),
            'must_reset_password' => false,
        ]);

        $this->actingAs($account, 'client')
            ->post(route('client.profile.update'), [
                'name' => 'Cliente Senha',
                'email' => 'senha@example.com',
                'current_password' => 'antiga-senha',
                'password' => 'nova-senha-123',
                'password_confirmation' => 'nova-senha-123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $account->refresh();
        $this->assertTrue(Hash::check('nova-senha-123', $account->password));
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
