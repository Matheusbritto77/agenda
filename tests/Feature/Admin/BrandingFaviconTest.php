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
}
