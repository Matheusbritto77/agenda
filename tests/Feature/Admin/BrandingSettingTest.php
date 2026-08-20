<?php

namespace Tests\Feature\Admin;

use App\Models\BrandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingSettingTest extends TestCase
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
    }

    public function test_owner_can_access_branding_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.branding.index'));
        $response->assertStatus(200);
    }

    public function test_subuser_cannot_access_branding_without_permission(): void
    {
        $response = $this->actingAs($this->subUser)->get(route('admin.branding.index'));
        $response->assertStatus(403);
    }

    public function test_owner_can_save_branding_settings_with_logo(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($this->owner)->post(route('admin.branding.update'), [
            'top_menu_color' => '#ffffff',
            'background_color' => '#f8fafc',
            'primary_color' => '#10b981',
            'logo_file' => $logo,
        ]);

        $response->assertRedirect();

        $branding = BrandingSetting::where('user_id', $this->owner->id)->first();
        $this->assertNotNull($branding);
        $this->assertEquals('#ffffff', $branding->top_menu_color);
        $this->assertEquals('#f8fafc', $branding->background_color);
        $this->assertEquals('#10b981', $branding->primary_color);
        $this->assertNotNull($branding->logo_path);
        
        Storage::disk('public')->assertExists($branding->logo_path);
    }

    public function test_public_booking_page_receives_branding_data(): void
    {
        Storage::fake('public');

        $branding = BrandingSetting::create([
            'user_id' => $this->owner->id,
            'top_menu_color' => '#22c55e',
            'background_color' => '#0f172a',
            'primary_color' => '#3b82f6',
            'logo_path' => 'branding/mock-logo.png',
        ]);

        $response = $this->get('http://testcompany.localhost/');
        $response->assertStatus(200);

        $response->assertInertia(function ($page) use ($branding) {
            $page->where('branding.top_menu_color', '#22c55e')
                 ->where('branding.background_color', '#0f172a')
                 ->where('branding.primary_color', '#3b82f6')
                 ->where('branding.logo_url', $branding->logo_url);
        });
    }
}
