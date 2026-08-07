<?php

namespace App\Enums;

enum DownloaderDriver: string
{
    case Local = 'local';
    case Torrent = 'torrent';

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Local (in-app)',
            self::Torrent => 'Torrent (Transmission)',
        };
    }

    public function requiredFields(): array
    {
        return match ($this) {
            self::Local => [],
            self::Torrent => ['host', 'port', 'username', 'password', 'download_dir', 'container_download_dir'],
        };
    }

    public function isTorrent(): bool
    {
        return $this === self::Torrent;
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
