<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentReview;
use App\Models\BrandingSetting;
use App\Models\BusinessHour;
use App\Models\ClientAccount;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicBookingEndpointsTest extends TestCase
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
            'subdomain' => 'studio',
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

    public function test_index_shows_only_active_services(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $activeService = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Serviço Ativo',
            'description' => 'Visível na tela',
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Serviço Inativo',
            'description' => 'Não deve aparecer',
            'price' => 40,
            'duration_minutes' => 30,
            'is_active' => false,
        ]);

        $response = $this->get('http://studio.agendae.app/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Booking')
            ->has('services', 1)
            ->where('services.0.name', 'Serviço Ativo')
        );
    }

    public function test_company_subdomain_receives_company_profile_before_booking_steps(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 10:00:00'));

        Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Corte Premium',
            'description' => 'Corte com acabamento',
            'price' => 90,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        BrandingSetting::create([
            'user_id' => $this->tenant->id,
            'logo_path' => 'https://cdn.example.com/logo.jpg',
            'settings' => [
                'business_name' => 'Studio Agendae',
                'tagline' => 'Atendimento com hora marcada',
                'banner_path' => 'https://cdn.example.com/banner.jpg',
                'whatsapp_number' => '11999999999',
            ],
        ]);

        $response = $this->get('http://studio.agendae.app/');

        Carbon::setTestNow();

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Booking')
            ->where('companyProfile.is_company_page', true)
            ->where('companyProfile.business_name', 'Studio Agendae')
            ->where('companyProfile.description', 'Atendimento com hora marcada')
            ->where('companyProfile.logo_url', 'https://cdn.example.com/logo.jpg')
            ->where('companyProfile.banner_url', 'https://cdn.example.com/banner.jpg')
            ->where('companyProfile.status.is_open_now', true)
            ->where('companyProfile.status.checked_at', '10:00')
            ->where('companyProfile.services_count', 1)
            ->has('companyProfile.hours_summary', 7)
            ->has('companyProfile.services_preview', 1)
        );
    }

    public function test_company_profile_displays_only_verified_reviews_from_completed_services(): void
    {
        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Corte Premium',
            'price' => 90,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);
        $maria = ClientAccount::create([
            'name' => 'Maria da Silva',
            'email' => 'maria@example.com',
            'password' => 'password',
            'must_reset_password' => false,
        ]);
        $joao = ClientAccount::create([
            'name' => 'João dos Santos',
            'email' => 'joao@example.com',
            'password' => 'password',
            'must_reset_password' => false,
        ]);

        $firstCompleted = $this->createReviewableAppointment($service, $maria, 'completed', '14:00');
        $secondCompleted = $this->createReviewableAppointment($service, $joao, 'completed', '15:00');
        $notCompleted = $this->createReviewableAppointment($service, $maria, 'confirmed', '16:00');

        AppointmentReview::create([
            'appointment_id' => $firstCompleted->id,
            'client_account_id' => $maria->id,
            'rating' => 5,
            'comment' => 'Atendimento excelente e pontual.',
            'is_public' => true,
        ]);
        AppointmentReview::create([
            'appointment_id' => $secondCompleted->id,
            'client_account_id' => $joao->id,
            'rating' => 4,
            'comment' => 'Gostei muito do resultado.',
            'is_public' => true,
        ]);
        AppointmentReview::create([
            'appointment_id' => $notCompleted->id,
            'client_account_id' => $maria->id,
            'rating' => 1,
            'comment' => 'Esta avaliação não pode ser publicada.',
            'is_public' => false,
        ]);

        $response = $this->get('http://studio.agendae.app/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('companyProfile.reviews.average', 4.5)
            ->where('companyProfile.reviews.count', 2)
            ->has('companyProfile.reviews.items', 2)
            ->where('companyProfile.reviews.items.0.client_name', 'João S.')
            ->where('companyProfile.reviews.items.0.rating', 4)
            ->where('companyProfile.reviews.items.1.client_name', 'Maria S.')
            ->where('companyProfile.reviews.items.1.rating', 5)
        );
        $response->assertDontSee('Esta avaliação não pode ser publicada.');
        $response->assertDontSee('Maria da Silva');
    }

    public function test_index_renders_service_image_when_image_url_is_present(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Serviço com Imagem',
            'description' => 'Imagem pública',
            'price' => 75,
            'duration_minutes' => 45,
            'is_active' => true,
            'image_path' => 'https://cdn.example.com/service-image.jpg',
        ]);

        $response = $this->get('http://studio.agendae.app/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Booking')
            ->has('services', 1)
            ->where('services.0.name', 'Serviço com Imagem')
            ->where('services.0.image_url', 'https://cdn.example.com/service-image.jpg')
        );
        $response->assertSee('https:\/\/cdn.example.com\/service-image.jpg', false);
    }

    public function test_admin_can_create_service_with_uploaded_image(): void
    {
        $this->configurePublicDisk();

        $admin = User::factory()->create();
        $image = UploadedFile::fake()->image('service.jpg', 1200, 800);

        $response = $this->actingAs($admin)->post('/admin/services', [
            'name' => 'Serviço com Upload',
            'description' => 'Imagem enviada pelo admin',
            'price' => 90,
            'duration_minutes' => 60,
            'image_file' => $image,
        ]);

        $response->assertRedirect(route('admin.services.index'));

        $service = Service::query()->where('name', 'Serviço com Upload')->firstOrFail();

        $this->assertNotEmpty($service->image_path);
        $this->assertStringStartsWith('services/', $service->image_path);
        Storage::disk('public')->assertExists($service->image_path);
        $this->assertStringContainsString('/storage/', $service->image_url ?? '');
    }

    public function test_admin_can_replace_a_service_uploaded_image(): void
    {
        $this->configurePublicDisk();

        $admin = User::factory()->create();
        $existingPath = UploadedFile::fake()->image('old-service.jpg')->store('services', 'public');

        $service = Service::create([
            'user_id' => $admin->id,
            'name' => 'Serviço Atualizável',
            'description' => 'Imagem antiga',
            'price' => 110,
            'duration_minutes' => 45,
            'is_active' => true,
            'image_path' => $existingPath,
        ]);

        $response = $this->actingAs($admin)->put('/admin/services/'.$service->id, [
            'name' => 'Serviço Atualizável',
            'description' => 'Imagem nova enviada',
            'price' => 110,
            'duration_minutes' => 45,
            'image_file' => UploadedFile::fake()->image('new-service.jpg', 900, 600),
        ]);

        $response->assertRedirect(route('admin.services.index'));

        $service->refresh();

        Storage::disk('public')->assertMissing($existingPath);
        $this->assertNotEmpty($service->image_path);
        $this->assertNotSame($existingPath, $service->image_path);
        Storage::disk('public')->assertExists($service->image_path);
    }

    public function test_admin_cannot_submit_image_file_and_image_url_together_when_creating_service(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->from('/admin/services/create')->post('/admin/services', [
            'name' => 'Serviço com Conflito',
            'description' => 'Tentativa inválida',
            'price' => 95,
            'duration_minutes' => 30,
            'image_url' => 'https://cdn.example.com/service.jpg',
            'image_file' => UploadedFile::fake()->image('service.jpg'),
        ]);

        $response->assertSessionHasErrors(['image_file']);
        $this->assertDatabaseMissing('services', [
            'name' => 'Serviço com Conflito',
        ]);
    }

    public function test_available_slots_endpoint_excludes_occupied_times(): void
    {
        $testDate = Carbon::now()->addDays(2)->format('Y-m-d');
        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Barba',
            'description' => null,
            'price' => 35,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        Appointment::create([
            'user_id' => $this->tenant->id,
            'service_id' => $service->id,
            'client_name' => 'João',
            'client_email' => 'joao@example.com',
            'client_phone' => '11999990000',
            'appointment_date' => $testDate,
            'appointment_time' => '10:00',
            'status' => 'confirmed',
            'notes' => null,
        ]);

        $response = $this->getJson('http://studio.agendae.app/available-slots?service_id='.$service->id.'&date='.$testDate);

        $response->assertOk();
        $response->assertJsonPath('service_id', $service->id);
        $response->assertJsonPath('date', $testDate);

        $slots = $response->json('slots');

        $this->assertContains('09:00', $slots);
        $this->assertNotContains('10:00', $slots);
    }

    public function test_api_available_slots_endpoint_is_consistent(): void
    {
        $testDate = Carbon::now()->addDays(3)->format('Y-m-d');
        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Corte',
            'description' => null,
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $response = $this->getJson('http://studio.agendae.app/api/services/'.$service->id.'/slots?date='.$testDate);

        $response->assertOk();
        $response->assertJsonFragment([
            'service_id' => $service->id,
            'date' => $testDate,
        ]);
    }

    public function test_booking_endpoint_creates_appointment_and_flashes_success(): void
    {
        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Combo',
            'description' => null,
            'price' => 80,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $targetBookingDate = Carbon::now()->addDays(1)->format('Y-m-d');

        $response = $this->post('http://studio.agendae.app/booking', [
            'service_id' => $service->id,
            'appointment_date' => $targetBookingDate,
            'appointment_time' => '14:00',
            'client_name' => 'Maria Silva',
            'client_email' => 'maria@example.com',
            'client_phone' => '11988887777',
            'notes' => 'Primeira visita',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('booking_success');

        $this->assertDatabaseHas('appointments', [
            'service_id' => $service->id,
            'client_name' => 'Maria Silva',
            'client_email' => 'maria@example.com',
            'client_phone' => '11988887777',
            'appointment_date' => $targetBookingDate,
            'appointment_time' => '14:00',
            'status' => 'confirmed',
            'notes' => 'Primeira visita',
        ]);
    }

    public function test_booking_endpoint_rejects_an_occupied_time_slot(): void
    {
        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Corte',
            'description' => null,
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $targetBookingDate = Carbon::now()->addDays(2)->format('Y-m-d');

        $payload = [
            'service_id' => $service->id,
            'appointment_date' => $targetBookingDate,
            'appointment_time' => '10:00',
            'client_name' => 'Cliente 1',
            'client_email' => 'cliente1@example.com',
            'client_phone' => '11911112222',
        ];

        $first = $this->post('http://studio.agendae.app/booking', $payload);
        $first->assertRedirect();

        $second = $this->post('http://studio.agendae.app/booking', [
            'service_id' => $service->id,
            'appointment_date' => $targetBookingDate,
            'appointment_time' => '10:00',
            'client_name' => 'Cliente 2',
            'client_email' => 'cliente2@example.com',
            'client_phone' => '11933334444',
        ]);

        $second->assertSessionHasErrors(['appointment_time']);
        $this->assertSame(1, Appointment::query()->count());
    }

    private function configurePublicDisk(): string
    {
        $root = sys_get_temp_dir().'/agendae-public-'.uniqid('', true);

        if (! is_dir($root)) {
            mkdir($root, 0777, true);
        }

        config([
            'filesystems.disks.public.root' => $root,
        ]);

        return $root;
    }

    public function test_api_booking_endpoint_also_creates_appointment(): void
    {
        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Limpeza',
            'description' => null,
            'price' => 120,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $response = $this->postJson('http://studio.agendae.app/api/appointments', [
            'service_id' => $service->id,
            'appointment_date' => '2026-08-24',
            'appointment_time' => '11:00',
            'client_name' => 'Ana',
            'client_email' => 'ana@example.com',
            'client_phone' => '11955556666',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('customer_name', 'Ana');
        $this->assertDatabaseHas('appointments', [
            'service_id' => $service->id,
            'client_name' => 'Ana',
            'appointment_date' => '2026-08-24',
            'appointment_time' => '11:00',
        ]);
    }

    public function test_booking_on_team_member_subdomain_is_visible_in_company_agenda(): void
    {
        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Corte Moderno',
            'description' => null,
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $member = TeamMember::create([
            'user_id' => $this->tenant->id,
            'name' => 'Carlos Barbeiro',
            'job_title' => 'Especialista',
            'subdomain' => 'carlos',
            'services' => [$service->id],
            'is_active' => true,
        ]);

        $response = $this->post('http://carlos.agendae.app/booking', [
            'service_id' => $service->id,
            'appointment_date' => '2026-08-25',
            'appointment_time' => '09:00',
            'client_name' => 'Cliente Carlos',
            'client_email' => 'cliente.carlos@example.com',
            'client_phone' => '11944443333',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'user_id' => $this->tenant->id,
            'team_member_id' => $member->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Carlos',
            'appointment_date' => '2026-08-25',
            'appointment_time' => '09:00',
        ]);

        $adminResponse = $this->actingAs($this->tenant)->get(route('admin.appointments.index'));

        $adminResponse->assertInertia(fn (Assert $page) => $page
            ->has('appointments', 1)
            ->where('appointments.0.client_name', 'Cliente Carlos')
        );
    }

    public function test_index_shows_company_services_on_team_member_subdomain(): void
    {
        config([
            'app.domain' => 'agendae.app',
            'app.url' => 'https://agendae.app',
        ]);

        $service = Service::create([
            'user_id' => $this->tenant->id,
            'name' => 'Corte Moderno',
            'description' => 'Serviço da empresa',
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        // Create a TeamMember with its own subdomain
        TeamMember::create([
            'user_id' => $this->tenant->id,
            'name' => 'Carlos Barbeiro',
            'job_title' => 'Especialista',
            'subdomain' => 'carlos',
            'services' => [$service->id],
            'is_active' => true,
        ]);

        $response = $this->get('http://carlos.agendae.app/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Booking')
            ->has('services', 1)
            ->where('services.0.name', 'Corte Moderno')
            ->where('companyProfile.is_company_page', false)
        );
    }

    private function createReviewableAppointment(
        Service $service,
        ClientAccount $client,
        string $status,
        string $time
    ): Appointment {
        return Appointment::create([
            'user_id' => $this->tenant->id,
            'client_account_id' => $client->id,
            'service_id' => $service->id,
            'client_name' => $client->name,
            'client_email' => $client->email,
            'client_phone' => '11999999999',
            'appointment_date' => '2026-08-19',
            'appointment_time' => $time,
            'status' => $status,
        ]);
    }
}
