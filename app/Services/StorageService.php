<?php

namespace App\Services;

use App\Enums\StorageDriver;
use App\Exceptions\StorageMigrationRequiredException;
use App\Models\File;
use App\Models\StorageServer;
use App\Repositories\Interfaces\StorageServerRepositoryInterface;
use App\Services\Interfaces\FileMigrationServiceInterface;
use App\Services\Interfaces\StorageServiceInterface;
use Illuminate\Support\Facades\Config;

class StorageService implements StorageServiceInterface
{
    private array $registered = [];

    public function __construct(
        private StorageServerRepositoryInterface $storageServerRepository,
        private FileMigrationServiceInterface $migrationService
    ) {}

    public function registerDisk(StorageServer $server): void
    {
        if (isset($this->registered[$server->id])) {
            return;
        }

        $config = $this->buildDiskConfig($server);

        Config::set("filesystems.disks.{$server->name}", $config);

        $this->registered[$server->id] = true;
    }

    private function buildDiskConfig(StorageServer $server): array
    {
        $driver = StorageDriver::tryFrom($server->driver) ?? throw new \InvalidArgumentException(
            "Unsupported driver: {$server->driver}"
        );

        $c = $server->config;

        return match ($driver) {
            StorageDriver::Local => [
                'driver' => 'local',
                'root' => $c['root'] ?? storage_path("app/{$server->name}"),
            ],
            StorageDriver::S3 => [
                'driver' => 's3',
                'key' => $c['key'] ?? null,
                'secret' => $c['secret'] ?? null,
                'region' => $c['region'] ?? 'us-east-1',
                'bucket' => $c['bucket'] ?? null,
                'endpoint' => $c['endpoint'] ?? null,
                'use_path_style_endpoint' => $c['use_path_style'] ?? false,
            ],
            StorageDriver::Wasabi => [
                'driver' => 's3',
                'key' => $c['key'] ?? null,
                'secret' => $c['secret'] ?? null,
                'region' => $c['region'] ?? 'us-east-1',
                'bucket' => $c['bucket'] ?? null,
                'endpoint' => 'https://s3.'.($c['region'] ?? 'us-east-1').'.wasabisys.com',
                'use_path_style_endpoint' => true,
            ],
            StorageDriver::B2 => [
                'driver' => 's3',
                'key' => $c['key_id'] ?? null,
                'secret' => $c['application_key'] ?? null,
                'region' => $c['region'] ?? 'us-east-1',
                'bucket' => $c['bucket'] ?? null,
                'endpoint' => 'https://s3.'.($c['region'] ?? 'us-east-1').'.backblazeb2.com',
                'use_path_style_endpoint' => true,
            ],
            StorageDriver::Dropbox => [
                'driver' => 'dropbox',
                'token' => $c['token'] ?? null,
            ],
            StorageDriver::GCS => [
                'driver' => 'gcs',
                'key_file' => $c['key_file'] ?? null,
                'bucket' => $c['bucket'] ?? null,
            ],
            StorageDriver::Azure => [
                'driver' => 'azure-storage-blob',
                'connection_string' => $c['connection_string'] ?? null,
                'container' => $c['container'] ?? null,
            ],
            StorageDriver::FTP => [
                'driver' => 'ftp',
                'host' => $c['host'] ?? null,
                'port' => $c['port'] ?? 21,
                'username' => $c['username'] ?? null,
                'password' => $c['password'] ?? null,
                'root' => $c['root'] ?? '/',
                'passive' => $c['passive'] ?? true,
                'ssl' => $c['ssl'] ?? false,
            ],
            StorageDriver::SFTP => [
                'driver' => 'sftp',
                'host' => $c['host'] ?? null,
                'port' => $c['port'] ?? 22,
                'username' => $c['username'] ?? null,
                'password' => $c['password'] ?? null,
                'privateKey' => $c['private_key'] ?? null,
                'root' => $c['root'] ?? '/',
                'passphrase' => $c['passphrase'] ?? null,
            ],
            StorageDriver::Rackspace => [
                'driver' => 'rackspace',
                'username' => $c['username'] ?? null,
                'key' => $c['api_key'] ?? null,
                'container' => $c['container'] ?? null,
                'region' => $c['region'] ?? null,
            ],
            default => throw new \InvalidArgumentException("Unsupported driver: {$server->driver}"),
        };
    }

    public function registerAllDisks(): void
    {
        $this->storageServerRepository->getActiveServers()
            ->each(fn (StorageServer $server) => $this->registerDisk($server));
    }

    public function getStats(string $userId): array
    {
        return $this->storageServerRepository->getStats($userId);
    }

    public function syncUsedSize(StorageServer $server): void
    {
        $totalSize = (int) File::query()
            ->join('media', function ($join) use ($server) {
                $join->on('files.id', '=', 'media.model_id')
                    ->where('media.model_type', '=', File::class)
                    ->where('media.disk', '=', $server->name);
            })
            ->sum('media.size');

        $this->storageServerRepository->update($server->id, [
            'storage_used' => $totalSize,
        ]);
    }

    public function addStorage(array $data): StorageServer
    {
        $server = $this->storageServerRepository->create([
            'user_id' => $data['user_id'] ?? null,
            'name' => $data['name'],
            'driver' => $data['driver'],
            'config' => $data['config'],
            'is_active' => true,
            'is_primary' => $data['is_primary'] ?? false,
            'backup_storage_server_id' => $data['backup_storage_server_id'] ?? null,
            'auto_backup' => $data['auto_backup'] ?? false,
            'storage_limit' => $data['storage_limit'] ?? null,
        ]);

        if ($server->is_primary) {
            $this->resetPrimary($server);
        }

        $this->registerDisk($server);
        $this->syncUsedSize($server);

        return $server;
    }

    public function updateStorage(string $id, array $data): StorageServer
    {
        $server = $this->storageServerRepository->update($id, $data);
        $this->registerDisk($server);
        $this->syncUsedSize($server);

        if ($server->is_primary) {
            $this->resetPrimary($server);
        }

        return $server;
    }

    public function deleteStorage(string $id): bool
    {
        $server = $this->storageServerRepository->findById($id);

        if (! $server) {
            return false;
        }

        $fileCount = $this->getFileCountOnDisk($server->name);

        if ($fileCount > 0) {
            throw new StorageMigrationRequiredException(
                "Storage server has {$fileCount} file(s). Migrate files to another server before deactivating."
            );
        }

        $this->storageServerRepository->update($id, [
            'is_active' => false,
            'is_primary' => false,
        ]);

        return true;
    }

    public function deactivateStorage(StorageServer $server, ?string $targetStorageId = null): bool
    {
        $fileCount = $this->getFileCountOnDisk($server->name);

        if ($fileCount > 0) {
            if (! $targetStorageId) {
                throw new StorageMigrationRequiredException(
                    "Storage server has {$fileCount} file(s). Please choose a target storage to migrate."
                );
            }

            $target = $this->storageServerRepository->findById($targetStorageId);

            if (! $target || $target->id === $server->id) {
                throw new \InvalidArgumentException('Invalid target storage server');
            }

            $this->migrateAllFiles($server, $target);
        }

        $this->storageServerRepository->update($server->id, [
            'is_active' => false,
            'is_primary' => false,
        ]);

        return true;
    }

    private function migrateAllFiles(StorageServer $source, StorageServer $target): void
    {
        $files = File::query()
            ->join('media', function ($join) use ($source) {
                $join->on('files.id', '=', 'media.model_id')
                    ->where('media.model_type', '=', File::class)
                    ->where('media.disk', '=', $source->name);
            })
            ->select('files.*')
            ->get();

        foreach ($files as $file) {
            $this->migrationService->moveFile($file, $target->name);
        }

        $this->syncUsedSize($source);
        $this->syncUsedSize($target);
    }

    private function getFileCountOnDisk(string $disk): int
    {
        return (int) File::query()
            ->join('media', function ($join) use ($disk) {
                $join->on('files.id', '=', 'media.model_id')
                    ->where('media.model_type', '=', File::class)
                    ->where('media.disk', '=', $disk);
            })
            ->count();
    }

    private function resetPrimary(StorageServer $primary): void
    {
        StorageServer::where('id', '!=', $primary->id)
            ->update(['is_primary' => false]);
    }

    public function getDefaultDiskName(string $userId): string
    {
        return $this->getSystemDiskName();
    }

    public function getSystemDiskName(): string
    {
        $server = $this->storageServerRepository->getLeastUtilizedActive();

        return $server?->name ?? config('filesystems.default');
    }

    public function getGlobalStats(): array
    {
        return $this->storageServerRepository->getGlobalStats();
    }
}
