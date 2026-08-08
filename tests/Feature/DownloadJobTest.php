<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Repositories\Downloads\DownloadTaskRepositoryInterface;
use App\Contracts\Services\Downloads\TorrentCatalogServiceInterface;
use App\Contracts\Services\Downloads\TorrentImportServiceInterface;
use App\Contracts\Services\Downloads\UrlDownloaderInterface;
use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Storage\StorageQuotaServiceInterface;
use App\Jobs\Downloads\DownloadFromUrlJob;
use App\Jobs\Downloads\DownloadTorrentJob;
use App\Models\Downloads\DownloadTask;
use App\Models\Downloads\Torrent;
use App\Models\Files\FileChecksum;
use App\Models\Users\User;
use App\Services\Downloads\TorrentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_download_job_completes_task_and_creates_file(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['storage_used' => 0, 'storage_limit' => null]);

        Storage::fake('local');

        $task = DownloadTask::create([
            'user_id' => $user->id,
            'type' => 'url',
            'source' => 'https://example.com/movie.mp4',
            'status' => 'queued',
        ]);

        // Stub the HTTP downloader to write a real temp file.
        app()->instance(UrlDownloaderInterface::class, new class implements UrlDownloaderInterface
        {
            public function download(string $url, string $tmpPath, int $timeout = 600): void
            {
                file_put_contents($tmpPath, 'fake-video-bytes');
            }
        });

        (new DownloadFromUrlJob($task->id))->handle(
            app(DownloadTaskRepositoryInterface::class),
            app(FileServiceInterface::class),
            app(StorageQuotaServiceInterface::class),
            app(UrlDownloaderInterface::class)
        );

        $this->assertDatabaseHas('download_tasks', [
            'id' => $task->id,
            'status' => 'completed',
            'progress' => 1.0,
        ]);

        $this->assertNotNull($task->fresh()->file_id);
        $this->assertDatabaseHas('files', ['id' => $task->fresh()->file_id, 'user_id' => $user->id]);
    }

    public function test_url_download_job_marks_failed_on_error(): void
    {
        $user = User::factory()->create();

        $task = DownloadTask::create([
            'user_id' => $user->id,
            'type' => 'url',
            'source' => 'https://example.com/missing.mp4',
            'status' => 'queued',
        ]);

        app()->instance(UrlDownloaderInterface::class, new class implements UrlDownloaderInterface
        {
            public function download(string $url, string $tmpPath, int $timeout = 600): void
            {
                throw new \RuntimeException('connection refused');
            }
        });

        (new DownloadFromUrlJob($task->id))->handle(
            app(DownloadTaskRepositoryInterface::class),
            app(FileServiceInterface::class),
            app(StorageQuotaServiceInterface::class),
            app(UrlDownloaderInterface::class)
        );

        $this->assertDatabaseHas('download_tasks', [
            'id' => $task->id,
            'status' => 'failed',
        ]);

        $this->assertNotNull($task->fresh()->error);
    }

    public function test_catalog_reuse_skips_download_when_files_exist(): void
    {
        $owner = User::factory()->create();
        $owner->profile()->create(['storage_used' => 0, 'storage_limit' => null]);

        $requester = User::factory()->create();
        $requester->profile()->create(['storage_used' => 0, 'storage_limit' => null]);

        Storage::fake('local');

        // Seed a checksum-backed file (as if imported once before).
        $checksum = FileChecksum::create([
            'crc32' => 123456,
            'md5' => str_repeat('a', 32),
            'sha1' => str_repeat('b', 40),
            'sha256' => str_repeat('c', 64),
            'sha512' => str_repeat('d', 128),
            'disk' => 'local',
            'path' => 'torrents/test.txt',
            'size' => 5,
            'mime_type' => 'text/plain',
            'file_name' => 'test.txt',
            'reference_count' => 1,
        ]);

        Storage::disk('local')->put('torrents/test.txt', 'hello');

        $torrent = Torrent::create([
            'info_hash' => '6f46bf0445a33ec41c5651e5716d0e6c0e03ba44',
            'name' => 'test-torrent',
            'total_size' => 5,
        ]);

        $torrent->files()->create([
            'file_checksum_id' => $checksum->id,
            'relative_path' => 'test.txt',
            'file_name' => 'test.txt',
            'size' => 5,
        ]);

        $task = DownloadTask::create([
            'user_id' => $requester->id,
            'type' => 'torrent',
            'source' => 'magnet:?xt=urn:btih:6f46bf0445a33ec41c5651e5716d0e6c0e03ba44',
            'status' => 'queued',
            'info_hash' => '6f46bf0445a33ec41c5651e5716d0e6c0e03ba44',
        ]);

        // DownloadTorrentJob must NOT touch the torrent client when reuse is possible.
        (new DownloadTorrentJob($task->id))->handle(
            app(DownloadTaskRepositoryInterface::class),
            app(TorrentCatalogServiceInterface::class),
            app(TorrentImportServiceInterface::class),
            app(TorrentService::class)
        );

        $this->assertDatabaseHas('download_tasks', [
            'id' => $task->id,
            'status' => 'completed',
            'progress' => 1.0,
        ]);

        $this->assertNotNull($task->fresh()->file_id);
        $this->assertDatabaseHas('files', ['id' => $task->fresh()->file_id, 'user_id' => $requester->id]);
    }
}
