<?php

namespace Tests\Feature;

use App\Enums\DownloaderDriver;
use App\Models\DownloadServer;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDownloaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_member_cannot_access_downloader_settings(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get(route('admin.downloaders.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_downloader_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.downloaders.index'))
            ->assertOk();
    }

    public function test_admin_can_create_torrent_client(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.downloaders.store'), [
                'name' => 'Transmission 1',
                'driver' => 'torrent',
                'is_active' => 1,
                'config' => [
                    'host' => 'http://127.0.0.1',
                    'port' => '9091',
                    'username' => 'admin',
                    'password' => 'secret',
                    'download_dir' => base_path('storage/downloader/incoming'),
                    'container_download_dir' => '/downloads',
                ],
            ])
            ->assertRedirect(route('admin.downloaders.index'));

        $this->assertDatabaseHas('download_servers', [
            'name' => 'Transmission 1',
            'driver' => 'torrent',
        ]);

        $server = DownloadServer::where('name', 'Transmission 1')->first();
        $this->assertSame('http://127.0.0.1', $server->config['host']);
        $this->assertEquals(
            ['host', 'port', 'username', 'password', 'download_dir', 'container_download_dir'],
            DownloaderDriver::Torrent->requiredFields()
        );
    }

    public function test_admin_can_delete_downloader_client(): void
    {
        $admin = User::factory()->admin()->create();

        $server = DownloadServer::create([
            'name' => 'Local 1',
            'driver' => 'local',
            'config' => [],
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.downloaders.destroy', $server->id))
            ->assertRedirect(route('admin.downloaders.index'));

        $this->assertDatabaseMissing('download_servers', ['id' => $server->id]);
    }
}
