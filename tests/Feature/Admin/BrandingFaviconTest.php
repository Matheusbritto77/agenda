<?php

namespace Tests\Feature\Admin;

use App\Models\BrandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingFaviconTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_upload_and_delete_custom_favicon(): void
    {
        Storage::fake('public');

        $tenant = User::factory()->create([
            'subdomain' => 'clinica-bella',
        ]);

        $file = UploadedFile::fake()->image('custom_favicon.png', 32, 32);

        $response = $this->actingAs($tenant)->post(route('admin.branding.update'), [
            'favicon_file' => $file,
            'business_name' => 'Clínica Bella',
        ]);

        $response->assertRedirect(route('admin.branding.index'));

        $branding = BrandingSetting::where('user_id', $tenant->id)->first();
        $this->assertNotNull($branding);
        $this->assertNotNull($branding->settings['favicon_path'] ?? null);
        $this->assertNotNull($branding->favicon_url);

        Storage::disk('public')->assertExists($branding->settings['favicon_path']);

        // Test delete favicon
        $deleteResponse = $this->actingAs($tenant)->post(route('admin.branding.update'), [
            'delete_favicon' => true,
        ]);

        $deleteResponse->assertRedirect(route('admin.branding.index'));

        $branding->refresh();
        $this->assertNull($branding->settings['favicon_path'] ?? null);
        $this->assertNull($branding->favicon_url);
    }

    public function test_public_company_page_renders_the_saved_custom_favicon(): void
    {
        $tenant = User::factory()->create([
            'subdomain' => 'clinica-favicon',
        ]);

        $branding = BrandingSetting::create([
            'user_id' => $tenant->id,
            'settings' => [
                'favicon_path' => 'branding/favicons/company-icon.png',
            ],
        ]);

        $response = $this->get('http://clinica-favicon.localhost/');

        $response->assertOk();
        $response->assertSee('id="dynamic-favicon"', false);
        $response->assertSee('href="'.$branding->favicon_url.'"', false);
        $response->assertDontSee('href="/favicon.svg"', false);
        $response->assertDontSee('href="/favicon.png"', false);
    }

    public function test_public_professional_page_uses_the_company_favicon(): void
    {
        $company = User::factory()->create([
            'subdomain' => 'clinica-company',
        ]);
        $professional = User::factory()->create([
            'parent_id' => $company->id,
            'subdomain' => 'profissional-favicon',
        ]);

        $branding = BrandingSetting::create([
            'user_id' => $company->id,
            'settings' => [
                'favicon_path' => 'branding/favicons/company-icon.png',
            ],
        ]);

        $response = $this->get('http://profissional-favicon.localhost/');

        $response->assertOk();
        $response->assertSee('id="dynamic-favicon"', false);
        $response->assertSee('href="'.$branding->favicon_url.'"', false);
        $response->assertDontSee('href="/favicon.svg"', false);
    }
}
