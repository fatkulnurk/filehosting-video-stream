<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Files\Directory;
use App\Models\Files\File;
use App\Models\Subscriptions\StoragePackage;
use App\Models\Users\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrictEnumValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_change_role_rejects_invalid_role(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.role', $member->id), ['role' => 'superuser'])
            ->assertSessionHasErrors('role');
    }

    public function test_change_role_accepts_valid_role(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.role', $member->id), ['role' => 'admin'])
            ->assertSessionHasNoErrors();

        $this->assertSame('admin', $member->fresh()->role->value);
    }

    public function test_subscription_status_rejects_invalid_value(): void
    {
        $admin = User::factory()->admin()->create();
        $package = StoragePackage::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'features' => [],
            'storage_limit' => 1073741824,
            'price_monthly' => 10,
            'price_yearly' => 100,
            'is_active' => true,
        ]);
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.store'), [
                'user_id' => $member->id,
                'package_id' => $package->id,
                'status' => 'suspended',
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_file_share_permission_rejects_directory_only_value(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $file = File::create([
            'user_id' => $user->id,
            'name' => 'Clip.mp4',
            'slug' => 'clip',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('files.share', $file->id), [
                'shared_with' => $target->email,
                'permission' => 'manage',
            ])
            ->assertSessionHasErrors('permission');
    }

    public function test_directory_share_permission_rejects_invalid_value(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $directory = Directory::create([
            'user_id' => $user->id,
            'name' => 'Movies',
            'path' => '/Movies',
        ]);

        $this->actingAs($user)
            ->post(route('directories.share', $directory->id), [
                'shared_with' => $target->email,
                'permission' => 'download',
            ])
            ->assertSessionHasErrors('permission');
    }

    public function test_admin_file_filter_rejects_invalid_status(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.files.index', ['filter' => ['status' => 'bogus']]))
            ->assertSessionHasErrors('filter.status');
    }

    public function test_admin_file_filter_accepts_valid_sort_array(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        File::create(['user_id' => $owner->id, 'name' => 'Z.mp4', 'slug' => 'z', 'views' => 2]);
        File::create(['user_id' => $owner->id, 'name' => 'A.mp4', 'slug' => 'a', 'views' => 8]);

        $response = $this->actingAs($admin)
            ->get(route('admin.files.index', ['sort' => ['name' => 'asc'], 'per_page' => 10]));

        $response->assertOk();
        $names = collect($response->viewData('page')['props']['files']['data'])->pluck('name')->all();
        $this->assertEquals(['A.mp4', 'Z.mp4'], $names);
    }

    public function test_admin_file_filter_rejects_invalid_sort_direction(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.files.index', ['sort' => ['created_at' => 'bogus']]))
            ->assertSessionHasErrors('sort.created_at');
    }

    public function test_admin_file_filter_ignores_non_sortable_key(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.files.index', ['sort' => ['hacked' => 'asc'], 'per_page' => 10]))
            ->assertOk();
    }
}
