<?php

namespace App\Enums;

enum StorageDriver: string
{
    case Local = 'local';
    case S3 = 's3';
    case Dropbox = 'dropbox';
    case GCS = 'gcs';
    case Azure = 'azure';
    case FTP = 'ftp';
    case SFTP = 'sftp';
    case Rackspace = 'rackspace';
    case Wasabi = 'wasabi';
    case B2 = 'b2';

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Local Storage',
            self::S3 => 'Amazon S3',
            self::Dropbox => 'Dropbox',
            self::GCS => 'Google Cloud Storage',
            self::Azure => 'Azure Blob Storage',
            self::FTP => 'FTP Server',
            self::SFTP => 'SFTP (SSH)',
            self::Rackspace => 'Rackspace Cloud Files',
            self::Wasabi => 'Wasabi',
            self::B2 => 'Backblaze B2',
        };
    }

    public function requiredFields(): array
    {
        return match ($this) {
            self::Local => ['root'],
            self::S3 => ['key', 'secret', 'region', 'bucket'],
            self::Dropbox => ['token'],
            self::GCS => ['key_file', 'bucket'],
            self::Azure => ['connection_string', 'container'],
            self::FTP => ['host', 'port', 'username', 'password', 'root'],
            self::SFTP => ['host', 'port', 'username', 'password', 'root'],
            self::Rackspace => ['username', 'api_key', 'container', 'region'],
            self::Wasabi => ['key', 'secret', 'region', 'bucket'],
            self::B2 => ['key_id', 'application_key', 'bucket'],
        };
    }

    public function requiresAuth(): bool
    {
        return match ($this) {
            self::Local => false,
            default => true,
        };
    }

    public function supportsTemporaryUrls(): bool
    {
        return match ($this) {
            self::S3, self::GCS, self::Azure, self::Wasabi, self::B2 => true,
            default => false,
        };
    }

    public static function isSupported(string $driver): bool
    {
        return in_array($driver, array_column(self::cases(), 'value'));
    }

    public static function supported(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($driver) => [$driver->value => $driver->label()])
            ->toArray();
    }
}
