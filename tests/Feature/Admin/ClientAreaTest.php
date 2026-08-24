<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\AppointmentReview;
use App\Models\ClientAccount;
use App\Models\CompanyReview;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_clients_service_reviews_and_company_reviews_from_its_company(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        [$client, $appointment, $review] = $this->createReviewedAppointment($owner, 'cliente@empresa.test');
        $this->createReviewedAppointment($otherOwner, 'cliente@outra.test');

        CompanyReview::create([
            'user_id' => $owner->id,
            'client_account_id' => $client->id,
            'rating' => 5,
            'comment' => 'Empresa excelente.',
            'is_public' => true,
        ]);

        $response = $this->actingAs($owner)->get(route('admin.client-area.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ClientArea/Index')
            ->has('clients.data', 1)
            ->where('clients.data.0.email', 'cliente@empresa.test')
            ->has('serviceReviews.data', 1)
            ->where('serviceReviews.data.0.id', $review->id)
            ->where('serviceReviews.data.0.is_public', false)
            ->has('companyReviews.data', 1)
            ->where('stats.clients', 1)
            ->where('stats.internal_reviews', 1)
        );
    }

    public function test_professional_sees_only_clients_and_reviews_from_own_appointments(): void
    {
        $owner = User::factory()->create();
        $professionalUser = User::factory()->create([
            'parent_id' => $owner->id,
            'email' => 'profissional@empresa.test',
        ]);
        $professional = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Profissional Um',
            'email' => $professionalUser->email,
            'role_id' => 'professional',
            'is_active' => true,
        ]);
        $otherProfessional = TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Profissional Dois',
            'email' => 'outro@empresa.test',
            'role_id' => 'professional',
            'is_active' => true,
        ]);

        $this->createReviewedAppointment($owner, 'meu-cliente@empresa.test', $professional);
        $this->createReviewedAppointment($owner, 'outro-cliente@empresa.test', $otherProfessional);

        $response = $this->actingAs($professionalUser)->get(route('admin.client-area.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.email', 'meu-cliente@empresa.test')
            ->has('serviceReviews.data', 1)
            ->where('scopeLabel', 'Somente meus atendimentos')
        );
    }

    public function test_custom_role_permissions_can_block_client_area_access(): void
    {
        $owner = User::factory()->create([
            'role_permissions' => ['professional' => []],
        ]);
        $professionalUser = User::factory()->create([
            'parent_id' => $owner->id,
            'email' => 'sem-permissao@empresa.test',
        ]);
        TeamMember::create([
            'user_id' => $owner->id,
            'name' => 'Sem Permissão',
            'email' => $professionalUser->email,
            'role_id' => 'professional',
            'is_active' => true,
        ]);

        $this->actingAs($professionalUser)
            ->get(route('admin.client-area.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_edit_company_contact_history_without_changing_client_login_email(): void
    {
        $owner = User::factory()->create();
        [$client, $appointment] = $this->createReviewedAppointment($owner, 'login@cliente.test');

        $response = $this->actingAs($owner)->patch(route('admin.client-area.clients.update', $client), [
            'name' => 'Nome Atualizado',
            'phone' => '(11) 98888-7777',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'client_name' => 'Nome Atualizado',
            'client_phone' => '(11) 98888-7777',
            'client_email' => 'login@cliente.test',
        ]);
        $this->assertSame('login@cliente.test', $client->fresh()->email);
    }

    public function test_review_moderation_is_tenant_scoped(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        [, , $ownReview] = $this->createReviewedAppointment($owner, 'cliente@empresa.test');
        [, , $otherReview] = $this->createReviewedAppointment($otherOwner, 'cliente@outra.test');

        $this->actingAs($owner)
            ->patch(route('admin.client-area.service-reviews.toggle-public', $ownReview))
            ->assertRedirect();

        $this->assertTrue($ownReview->fresh()->is_public);

        $this->actingAs($owner)
            ->patch(route('admin.client-area.service-reviews.toggle-public', $otherReview))
            ->assertNotFound();

        $this->assertFalse($otherReview->fresh()->is_public);
    }

    public function test_client_permissions_are_available_in_roles_matrix(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('admin.roles.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('permissionModules.clients.title', 'Área do Cliente & Avaliações')
                ->has('permissionModules.clients.permissions', 4)
            );
    }

    public function test_owner_can_customize_client_portal_settings(): void
    {
        $owner = User::factory()->create(['name' => 'Barbearia Premium']);

        $response = $this->actingAs($owner)->post(route('admin.client-area.customization.update'), [
            'portal_welcome_title' => 'Espaço Exclusivo VIP',
            'portal_welcome_subtitle' => 'Seus cortes e benefícios na Barbearia Premium',
            'portal_announcement' => '🎉 Promoção de Aniversário: 10% OFF na próxima visita!',
            'portal_announcement_enabled' => true,
            'portal_primary_color' => '#8b5cf6',
            'portal_secondary_color' => '#ec4899',
            'portal_show_loyalty_badges' => true,
            'portal_show_reviews' => true,
            'portal_show_professionals' => true,
            'portal_show_service_prices' => true,
            'portal_support_whatsapp' => '11988887777',
            'portal_custom_instructions' => 'Por favor, chegue com 10 minutos de antecedência.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $branding = \App\Models\BrandingSetting::where('user_id', $owner->id)->first();
        $this->assertSame('Espaço Exclusivo VIP', $branding->settings['portal_welcome_title']);
        $this->assertSame('11988887777', $branding->settings['portal_support_whatsapp']);
        $this->assertTrue($branding->settings['portal_announcement_enabled']);
        $this->assertSame('#8b5cf6', $branding->settings['portal_primary_color']);
        $this->assertSame('#ec4899', $branding->settings['portal_secondary_color']);
    }

    public function test_owner_can_create_update_toggle_and_delete_coupons(): void
    {
        $owner = User::factory()->create();

        // Create
        $response = $this->actingAs($owner)->post(route('admin.client-area.coupons.store'), [
            'code' => 'PROMO20',
            'description' => 'Desconto de 20% em todos os serviços',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'min_spend' => 50,
            'max_uses' => 100,
            'is_active' => true,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('coupons', [
            'user_id' => $owner->id,
            'code' => 'PROMO20',
            'discount_value' => 20,
        ]);

        $coupon = \App\Models\Coupon::where('user_id', $owner->id)->where('code', 'PROMO20')->first();

        // Update
        $this->actingAs($owner)->put(route('admin.client-area.coupons.update', $coupon), [
            'code' => 'PROMO25',
            'description' => 'Desconto atualizado para 25%',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'is_active' => true,
        ])->assertRedirect();
        $this->assertSame('PROMO25', $coupon->fresh()->code);

        // Toggle
        $this->actingAs($owner)->patch(route('admin.client-area.coupons.toggle', $coupon))->assertRedirect();
        $this->assertFalse($coupon->fresh()->is_active);

        // Delete
        $this->actingAs($owner)->delete(route('admin.client-area.coupons.destroy', $coupon))->assertRedirect();
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    public function test_owner_can_gift_exclusive_coupon_to_client(): void
    {
        $owner = User::factory()->create();
        $client = ClientAccount::create([
            'name' => 'Cliente Fiel',
            'email' => 'fiel@cliente.test',
            'phone' => '11999999999',
            'password' => Hash::make('password'),
            'must_reset_password' => false,
        ]);

        $response = $this->actingAs($owner)->post(route('admin.client-area.coupons.gift'), [
            'client_account_id' => $client->id,
            'code' => 'PRESENTEFIEL',
            'description' => 'Cupom de aniversário',
            'discount_type' => 'fixed',
            'discount_value' => 30,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('coupons', [
            'user_id' => $owner->id,
            'client_account_id' => $client->id,
            'code' => 'PRESENTEFIEL',
            'discount_type' => 'fixed',
            'discount_value' => 30,
        ]);
    }

    public function test_owner_can_customize_loyalty_tiers_and_rewards(): void
    {
        $owner = User::factory()->create();

        $tiers = [
            ['name' => 'Bronze', 'minimum' => 2, 'icon' => 'sparkles', 'color' => '#6366f1', 'reward' => 'Café cortesia'],
            ['name' => 'Prata', 'minimum' => 5, 'icon' => 'star', 'color' => '#3b82f6', 'reward' => '10% de desconto'],
            ['name' => 'Ouro VIP', 'minimum' => 10, 'icon' => 'crown', 'color' => '#f59e0b', 'reward' => 'Corte grátis no aniversário'],
        ];

        $response = $this->actingAs($owner)->post(route('admin.client-area.loyalty-tiers.update'), [
            'tiers' => $tiers,
        ]);

        $response->assertRedirect();
        $branding = \App\Models\BrandingSetting::where('user_id', $owner->id)->first();
        $this->assertCount(3, $branding->settings['loyalty_tiers']);
        $this->assertSame('Ouro VIP', $branding->settings['loyalty_tiers'][2]['name']);
        $this->assertSame('Corte grátis no aniversário', $branding->settings['loyalty_tiers'][2]['reward']);
    }

    public function test_public_booking_coupon_validation(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $owner = User::factory()->create([
            'subdomain' => 'barbearia-top',
            'active_domain_type' => 'subdomain',
        ]);
        $service = Service::create([
            'user_id' => $owner->id,
            'name' => 'Corte Masculino',
            'price' => 100,
            'duration_minutes' => 30,
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $coupon = \App\Models\Coupon::create([
            'user_id' => $owner->id,
            'code' => 'DESCONTO15',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'is_active' => true,
        ]);

        $response = $this->postJson('https://barbearia-top.agendae.app/api/coupons/validate', [
            'code' => 'DESCONTO15',
            'service_id' => $service->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => true,
            'code' => 'DESCONTO15',
            'discount_amount' => 15.0,
            'final_price' => 85.0,
        ]);
    }

    private function createReviewedAppointment(User $owner, string $email, ?TeamMember $teamMember = null): array
    {
        $client = ClientAccount::create([
            'name' => 'Cliente '.uniqid(),
            'email' => $email,
            'phone' => '11999999999',
            'password' => Hash::make('password'),
            'must_reset_password' => false,
        ]);
        $service = Service::create([
            'user_id' => $owner->id,
            'name' => 'Serviço '.uniqid(),
            'description' => 'Serviço de teste',
            'price' => 100,
            'duration_minutes' => 60,
            'slot_duration_minutes' => 60,
            'is_active' => true,
        ]);
        $appointment = Appointment::create([
            'user_id' => $owner->id,
            'client_account_id' => $client->id,
            'service_id' => $service->id,
            'team_member_id' => $teamMember?->id,
            'client_name' => $client->name,
            'client_email' => $client->email,
            'client_phone' => $client->phone,
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00',
            'status' => 'completed',
        ]);
        $review = AppointmentReview::create([
            'appointment_id' => $appointment->id,
            'client_account_id' => $client->id,
            'rating' => 4,
            'comment' => 'Feedback interno do serviço.',
            'is_public' => false,
        ]);

        return [$client, $appointment, $review];
    }
}
