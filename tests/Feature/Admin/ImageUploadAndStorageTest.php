<?php

namespace Tests\Feature\Admin;

use App\Models\BrandingSetting;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\StorageHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadAndStorageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'parent_id' => null,
            'subdomain' => 'studiodemo',
            'active_domain_type' => 'subdomain',
        ]);
    }

    public function test_branding_logo_and_banner_upload_storage_and_db_persistence(): void
    {
        Storage::fake('public');

        $logoFile = UploadedFile::fake()->image('logo.png', 300, 300);
        $bannerFile = UploadedFile::fake()->image('banner.jpg', 1200, 400);

        $response = $this->actingAs($this->user)->post(route('admin.branding.update'), [
            'business_name' => 'Studio Demo VIP',
            'logo_file' => $logoFile,
            'banner_file' => $bannerFile,
        ]);

        $response->assertRedirect();

        $branding = BrandingSetting::where('user_id', $this->user->id)->first();
        $this->assertNotNull($branding);

        // 1. Check path saved in DB
        $this->assertNotNull($branding->logo_path);
        $this->assertStringStartsWith('branding/logos/', $branding->logo_path);
        $this->assertNotNull($branding->settings['banner_path']);
        $this->assertStringStartsWith('branding/banners/', $branding->settings['banner_path']);

        // 2. Check physical storage existence
        Storage::disk('public')->assertExists($branding->logo_path);
        Storage::disk('public')->assertExists($branding->settings['banner_path']);

        // 3. Check public URL accessor resolution
        $this->assertEquals(Storage::disk('public')->url($branding->logo_path), $branding->logo_url);
        $this->assertEquals(Storage::disk('public')->url($branding->settings['banner_path']), $branding->banner_url);

        // 4. Test replacing logo deletes old file
        $oldLogoPath = $branding->logo_path;
        $newLogoFile = UploadedFile::fake()->image('new_logo.png', 400, 400);

        $this->actingAs($this->user)->post(route('admin.branding.update'), [
            'logo_file' => $newLogoFile,
        ]);

        $branding->refresh();
        Storage::disk('public')->assertMissing($oldLogoPath);
        Storage::disk('public')->assertExists($branding->logo_path);
        $this->assertNotEquals($oldLogoPath, $branding->logo_path);

        // 5. Test deleting logo and banner
        $logoToDelete = $branding->logo_path;
        $bannerToDelete = $branding->settings['banner_path'];

        $this->actingAs($this->user)->post(route('admin.branding.update'), [
            'delete_logo' => true,
            'delete_banner' => true,
        ]);

        $branding->refresh();
        Storage::disk('public')->assertMissing($logoToDelete);
        Storage::disk('public')->assertMissing($bannerToDelete);
        $this->assertNull($branding->logo_path);
        $this->assertNull($branding->logo_url);
        $this->assertNull($branding->settings['banner_path'] ?? null);
        $this->assertNull($branding->banner_url);
    }

    public function test_service_image_upload_storage_and_db_persistence(): void
    {
        Storage::fake('public');

        $imageFile = UploadedFile::fake()->image('corte_premium.jpg', 600, 600);

        // 1. Create service with image
        $response = $this->actingAs($this->user)->post(route('admin.services.store'), [
            'name' => 'Corte Premium',
            'price' => '75.00',
            'duration_minutes' => 45,
            'image_file' => $imageFile,
        ]);

        $response->assertRedirect();

        $service = Service::where('user_id', $this->user->id)->where('name', 'Corte Premium')->first();
        $this->assertNotNull($service);
        $this->assertNotNull($service->image_path);
        $this->assertStringStartsWith('services/', $service->image_path);

        Storage::disk('public')->assertExists($service->image_path);
        $this->assertEquals(Storage::disk('public')->url($service->image_path), $service->image_url);

        // 2. Update service with a new image (should remove old image)
        $oldImagePath = $service->image_path;
        $newImageFile = UploadedFile::fake()->image('corte_v2.webp', 800, 800);

        $this->actingAs($this->user)->put(route('admin.services.update', $service), [
            'name' => 'Corte Premium Atualizado',
            'price' => '80.00',
            'duration_minutes' => 50,
            'image_file' => $newImageFile,
        ]);

        $service->refresh();
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($service->image_path);
        $this->assertNotEquals($oldImagePath, $service->image_path);

        // 3. Destroy service deletes image from storage
        $imageToDelete = $service->image_path;
        $this->actingAs($this->user)->delete(route('admin.services.destroy', $service));

        Storage::disk('public')->assertMissing($imageToDelete);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_team_member_avatar_upload_storage_and_db_persistence(): void
    {
        Storage::fake('public');

        $avatar = UploadedFile::fake()->image('barbeiro.jpg', 500, 500);

        // 1. Create team member with avatar
        $response = $this->actingAs($this->user)->post(route('admin.team.store'), [
            'name' => 'Marcos Barbeiro',
            'job_title' => 'Barbeiro Master',
            'role_id' => 'professional',
            'email' => 'marcos@example.com',
            'avatar' => $avatar,
        ]);

        $response->assertRedirect();

        $member = TeamMember::where('user_id', $this->user->id)->where('name', 'Marcos Barbeiro')->first();
        $this->assertNotNull($member);
        $this->assertNotNull($member->avatar_url);
        $this->assertStringStartsWith('team/avatars/', $member->getRawOriginal('avatar_url'));

        Storage::disk('public')->assertExists($member->getRawOriginal('avatar_url'));
        $this->assertEquals(Storage::disk('public')->url($member->getRawOriginal('avatar_url')), $member->avatar_url);

        // 2. Update team member avatar (should remove old avatar)
        $oldAvatarPath = $member->getRawOriginal('avatar_url');
        $newAvatar = UploadedFile::fake()->image('marcos_novo.png', 600, 600);

        $this->actingAs($this->user)->put(route('admin.team.update', $member), [
            'name' => 'Marcos Barbeiro Senior',
            'avatar' => $newAvatar,
        ]);

        $member->refresh();
        Storage::disk('public')->assertMissing($oldAvatarPath);
        Storage::disk('public')->assertExists($member->getRawOriginal('avatar_url'));

        // 3. Destroy team member deletes avatar from storage
        $avatarToDelete = $member->getRawOriginal('avatar_url');
        $this->actingAs($this->user)->delete(route('admin.team.destroy', $member));

        Storage::disk('public')->assertMissing($avatarToDelete);
        $this->assertDatabaseMissing('team_members', ['id' => $member->id]);
    }

    public function test_user_profile_avatar_upload_storage_and_db_persistence(): void
    {
        Storage::fake('public');

        $avatar = UploadedFile::fake()->image('perfil_admin.jpg', 400, 400);

        $response = $this->actingAs($this->user)->post(route('profile.update.upload'), [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'avatar' => $avatar,
        ]);

        $response->assertRedirect();

        $this->user->refresh();
        $rawAvatar = $this->user->getRawOriginal('avatar_url');
        $this->assertNotNull($rawAvatar);
        $this->assertStringStartsWith('avatars/', $rawAvatar);

        Storage::disk('public')->assertExists($rawAvatar);
        $this->assertEquals(Storage::disk('public')->url($rawAvatar), $this->user->avatar_url);

        // Update with new avatar
        $oldAvatar = $rawAvatar;
        $newAvatar = UploadedFile::fake()->image('perfil_admin_v2.png', 500, 500);

        $this->actingAs($this->user)->post(route('profile.update.upload'), [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'avatar' => $newAvatar,
        ]);

        $this->user->refresh();
        Storage::disk('public')->assertMissing($oldAvatar);
        Storage::disk('public')->assertExists($this->user->getRawOriginal('avatar_url'));
    }

    public function test_storage_helper_handles_all_url_types(): void
    {
        // 1. External URL should remain intact
        $externalUrl = 'https://images.unsplash.com/photo-1585747860715-2ba37e788b70';
        $this->assertEquals($externalUrl, StorageHelper::url($externalUrl));

        // 2. Relative stored path
        $relativePath = 'branding/logos/mock.png';
        $this->assertEquals(Storage::disk('public')->url($relativePath), StorageHelper::url($relativePath));

        // 3. Legacy path with /storage/ or storage/
        $legacySlash = '/storage/services/item.jpg';
        $this->assertEquals(Storage::disk('public')->url('services/item.jpg'), StorageHelper::url($legacySlash));

        $legacyNoSlash = 'storage/team/avatars/user.jpg';
        $this->assertEquals(Storage::disk('public')->url('team/avatars/user.jpg'), StorageHelper::url($legacyNoSlash));

        // 4. Null or empty string returns null
        $this->assertNull(StorageHelper::url(null));
        $this->assertNull(StorageHelper::url(''));
    }
}
