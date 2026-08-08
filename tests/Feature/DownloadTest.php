<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\Downloads\DownloadFromUrlJob;
use App\Jobs\Downloads\DownloadTorrentJob;
use App\Models\Downloads\DownloadTask;
use App\Models\Users\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_member_can_view_downloads_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cloud-downloads.index'))
            ->assertOk();
    }

    public function test_downloads_page_exposes_expected_props(): void
    {
        $user = User::factory()->create();

        DownloadTask::create([
            'user_id' => $user->id,
            'type' => 'torrent',
            'source' => 'magnet:?xt=urn:btih:6f46bf0445a33ec41c5651e5716d0e6c0e03ba44',
            'status' => 'downloading',
            'progress' => 0.5,
            'info_hash' => '6f46bf0445a33ec41c5651e5716d0e6c0e03ba44',
        ]);

        $response = $this->actingAs($user)
            ->get(route('cloud-downloads.index'))
            ->assertOk();

        $payload = $response->viewData('page')['props'];

        $this->assertArrayHasKey('tasks', $payload);
        $this->assertArrayHasKey('directoryTree', $payload);
        $this->assertCount(1, $payload['tasks']['data']);

        $row = $payload['tasks']['data'][0];
        $this->assertSame('downloading', $row['status']);
        $this->assertSame('Downloading', $row['status_label']);
        $this->assertArrayHasKey('status_badge_variant', $row);
        $this->assertArrayHasKey('source_display', $row);
    }

    public function test_store_url_creates_task_and_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('cloud-downloads.url'), ['url' => 'https://example.com/file.mp4'])
            ->assertRedirect(route('cloud-downloads.index'));

        $this->assertDatabaseHas('download_tasks', [
            'user_id' => $user->id,
            'type' => 'url',
            'source' => 'https://example.com/file.mp4',
            'status' => 'queued',
        ]);

        Queue::assertPushed(DownloadFromUrlJob::class);
    }

    public function test_store_torrent_magnet_creates_task_with_info_hash(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $magnet = 'magnet:?xt=urn:btih:6f46bf0445a33ec41c5651e5716d0e6c0e03ba44&dn=ubuntu';

        $this->actingAs($user)
            ->post(route('cloud-downloads.torrent'), ['magnet' => $magnet])
            ->assertRedirect(route('cloud-downloads.index'));

        $this->assertDatabaseHas('download_tasks', [
            'user_id' => $user->id,
            'type' => 'torrent',
            'info_hash' => '6f46bf0445a33ec41c5651e5716d0e6c0e03ba44',
            'status' => 'queued',
        ]);

        Queue::assertPushed(DownloadTorrentJob::class);
    }

    public function test_store_torrent_requires_valid_source(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('cloud-downloads.torrent'), [])
            ->assertSessionHasErrors();
    }

    public function test_store_torrent_rejects_invalid_magnet_uri(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('cloud-downloads.torrent'), ['magnet' => 'this-is-not-a-magnet'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_retry_requires_ownership_and_final_status(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $other = User::factory()->create();

        $task = DownloadTask::create([
            'user_id' => $owner->id,
            'type' => 'url',
            'source' => 'https://example.com/file.mp4',
            'status' => 'failed',
        ]);

        // Other user cannot retry
        $this->actingAs($other)
            ->post(route('cloud-downloads.retry', $task->id))
            ->assertNotFound();

        // Owner retry requeues and dispatches
        $this->actingAs($owner)
            ->post(route('cloud-downloads.retry', $task->id))
            ->assertRedirect();

        $this->assertDatabaseHas('download_tasks', ['id' => $task->id, 'status' => 'queued']);
        Queue::assertPushed(DownloadFromUrlJob::class);
    }

    public function test_cancel_only_for_owner_and_not_final(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $active = DownloadTask::create([
            'user_id' => $owner->id,
            'type' => 'url',
            'source' => 'https://example.com/file.mp4',
            'status' => 'downloading',
            'info_hash' => null,
        ]);

        $this->actingAs($other)
            ->post(route('cloud-downloads.cancel', $active->id))
            ->assertNotFound();

        $this->actingAs($owner)
            ->post(route('cloud-downloads.cancel', $active->id))
            ->assertRedirect();

        $this->assertDatabaseHas('download_tasks', ['id' => $active->id, 'status' => 'cancelled']);
    }

    public function test_cancel_does_not_alter_final_task(): void
    {
        $owner = User::factory()->create();

        $task = DownloadTask::create([
            'user_id' => $owner->id,
            'type' => 'url',
            'source' => 'https://example.com/file.mp4',
            'status' => 'completed',
        ]);

        $this->actingAs($owner)
            ->post(route('cloud-downloads.cancel', $task->id))
            ->assertRedirect();

        $this->assertDatabaseHas('download_tasks', ['id' => $task->id, 'status' => 'completed']);
    }
}
