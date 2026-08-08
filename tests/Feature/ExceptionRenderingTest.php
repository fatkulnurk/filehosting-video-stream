<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\Directories\DirectoryNotFoundException;
use App\Exceptions\Files\FileNotFoundException;
use App\Exceptions\Files\StorageQuotaExceededException;
use App\Exceptions\Storage\StorageMigrationRequiredException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ExceptionRenderingTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/v1/exception-file-not-found', fn () => throw new FileNotFoundException);
        Route::get('/api/v1/exception-directory-not-found', fn () => throw new DirectoryNotFoundException);
        Route::get('/api/v1/exception-quota', fn () => throw new StorageQuotaExceededException);
        Route::get('/api/v1/exception-migration', fn () => throw new StorageMigrationRequiredException);
    }

    public function test_file_not_found_renders_structured_json_for_api(): void
    {
        $this->getJson('/api/v1/exception-file-not-found')
            ->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'error' => [
                    'code' => 'file_not_found',
                    'message' => __('files.not_found'),
                ],
            ]);
    }

    public function test_domain_exception_maps_correct_status_code(): void
    {
        $this->assertSame(Response::HTTP_NOT_FOUND, (new FileNotFoundException)->getStatusCode());
        $this->assertSame(Response::HTTP_NOT_FOUND, (new DirectoryNotFoundException)->getStatusCode());
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, (new StorageQuotaExceededException)->getStatusCode());
        $this->assertSame(Response::HTTP_CONFLICT, (new StorageMigrationRequiredException)->getStatusCode());
    }

    public function test_domain_exception_uses_default_translated_message(): void
    {
        $this->assertSame('File not found.', (new FileNotFoundException)->getMessage());
    }

    public function test_not_found_exception_is_not_logged(): void
    {
        Log::spy();

        $this->getJson('/api/v1/exception-file-not-found')->assertNotFound();

        Log::shouldNotHaveReceived('error');
    }

    public function test_quota_exception_context_is_structured(): void
    {
        $e = new StorageQuotaExceededException('Quota exceeded.', requestedBytes: 500, remainingBytes: 123);

        $this->assertSame(['requested_bytes' => 500, 'remaining_bytes' => 123], $e->context());
    }

    public function test_migration_exception_context_is_structured(): void
    {
        $e = new StorageMigrationRequiredException('Migrate.', storageServerId: 'server-1', fileCount: 4);

        $this->assertSame(['storage_server_id' => 'server-1', 'file_count' => 4], $e->context());
    }
}
