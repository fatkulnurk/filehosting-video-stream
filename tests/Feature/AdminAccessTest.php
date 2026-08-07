<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\StoragePackage;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createVideoFile(User $user): File
    {
        Storage::fake('local');

        $file = File::create([
            'user_id' => $user->id,
            'name' => 'clip.mp4',
            'slug' => 'clip-mp4',
            'is_public' => false,
        ]);

        $media = $file->addMedia(UploadedFile::fake()->create('clip.mp4', 2048))
            ->usingName('clip.mp4')
            ->toMediaCollection('default', 'local');

        $media->mime_type = 'video/mp4';
        $media->save();

        return $file;
    }

    public function test_member_cannot_access_admin_files_page(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get(route('admin.files.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_all_files(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $this->createVideoFile($owner);

        $this->actingAs($admin)
            ->get(route('admin.files.index'))
            ->assertOk();
    }

    public function test_admin_can_trash_another_users_file(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $file = $this->createVideoFile($owner);

        $this->actingAs($admin)
            ->delete(route('admin.files.destroy', $file->id))
            ->assertRedirect();

        $this->assertSoftDeleted('files', ['id' => $file->id]);
    }

    public function test_admin_can_force_delete_another_users_file(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $file = $this->createVideoFile($owner);

        $this->actingAs($admin)
            ->delete(route('admin.files.forceDestroy', $file->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'role' => 'member',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_admin_can_assign_subscription(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $member->profile()->create(['storage_used' => 0, 'storage_limit' => null]);

        $package = StoragePackage::create([
            'name' => 'Free',
            'slug' => 'free',
            'storage_limit' => 1000000,
            'features' => [],
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.store'), [
                'user_id' => $member->id,
                'package_id' => $package->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $member->id,
            'storage_package_id' => $package->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_directory_for_user(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.directories.store'), [
                'user_id' => $member->id,
                'name' => 'Test Folder',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('directories', [
            'user_id' => $member->id,
            'name' => 'Test Folder',
        ]);
    }

    public function test_admin_can_view_file_detail_page(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $file = $this->createVideoFile($owner);

        $this->actingAs($admin)
            ->get(route('admin.files.show', $file->id))
            ->assertOk();
    }

    public function test_directory_tree_endpoint_returns_paths(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        \App\Models\Directory::create([
            'user_id' => $member->id,
            'name' => 'Parent',
            'slug' => 'parent',
            'path' => '/parent',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.files.directories', ['user_id' => $member->id]))
            ->assertOk()
            ->assertJsonStructure(['directories' => [['id', 'label']]]);
    }

    public function test_admin_files_supports_search_sort_and_per_page(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        File::create(['user_id' => $owner->id, 'name' => 'Alpha.mp4', 'slug' => 'alpha', 'views' => 5]);
        File::create(['user_id' => $owner->id, 'name' => 'Beta.mp4', 'slug' => 'beta', 'views' => 9]);
        File::create(['user_id' => $owner->id, 'name' => 'Gamma.mp4', 'slug' => 'gamma', 'views' => 1]);

        // Search filters to matching row only
        $response = $this->actingAs($admin)->get(route('admin.files.index', ['search' => 'Beta', 'per_page' => 10]));
        $response->assertOk();
        $payload = $response->viewData('page')['props'];
        $names = collect($payload['files']['data'])->pluck('name')->all();
        $this->assertEquals(['Beta.mp4'], $names);

        // Sort by views asc
        $response = $this->actingAs($admin)->get(route('admin.files.index', ['sort' => 'views', 'direction' => 'asc', 'per_page' => 10]));
        $payload = $response->viewData('page')['props'];
        $this->assertEquals(['Gamma.mp4', 'Alpha.mp4', 'Beta.mp4'], collect($payload['files']['data'])->pluck('name')->all());
    }

    public function test_admin_files_supports_category_filter(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        File::create(['user_id' => $owner->id, 'name' => 'Public.mp4', 'slug' => 'public', 'is_public' => true]);
        File::create(['user_id' => $owner->id, 'name' => 'Private.mp4', 'slug' => 'private', 'is_public' => false]);

        // Filter by public status
        $response = $this->actingAs($admin)->get(route('admin.files.index', ['status' => 'public', 'per_page' => 10]));
        $payload = $response->viewData('page')['props'];
        $this->assertEquals(['Public.mp4'], collect($payload['files']['data'])->pluck('name')->all());
        $this->assertEquals('public', $payload['filters']['status']);

        // Filter by private status
        $response = $this->actingAs($admin)->get(route('admin.files.index', ['status' => 'private', 'per_page' => 10]));
        $payload = $response->viewData('page')['props'];
        $this->assertEquals(['Private.mp4'], collect($payload['files']['data'])->pluck('name')->all());
    }
}
