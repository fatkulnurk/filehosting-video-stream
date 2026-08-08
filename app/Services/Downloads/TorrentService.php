<?php

declare(strict_types=1);

namespace App\Services\Downloads;

use App\Models\Downloads\DownloadServer;
use Fatkulnurk\Torrent\Data\Torrent;
use Fatkulnurk\Torrent\TorrentClientManager;

class TorrentService
{
    private array $clients = [];

    public function add(string $source, array $options = [], ?DownloadServer $server = null): bool
    {
        return $this->client($server)->addTorrent($source, $options);
    }

    public function get(string $hash, ?DownloadServer $server = null): Torrent
    {
        return $this->client($server)->getTorrent($hash);
    }

    public function progress(string $hash, ?DownloadServer $server = null): float
    {
        return $this->client($server)->getTorrent($hash)->percentDone;
    }

    public function remove(string $hash, bool $deleteFiles = false, ?DownloadServer $server = null): bool
    {
        return $this->client($server)->removeTorrent($hash, $deleteFiles);
    }

    public function setDownloadPath(string $hash, string $path, ?DownloadServer $server = null): bool
    {
        return $this->client($server)->setDownloadPath($hash, $path);
    }

    public function serverReachable(?DownloadServer $server = null): bool
    {
        try {
            $this->client($server)->getServerStatus();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function active(string $which = 'torrent'): ?DownloadServer
    {
        return DownloadServer::query()
            ->where('driver', $which)
            ->where('is_active', true)
            ->first();
    }

    private function client(?DownloadServer $server = null)
    {
        $config = $server ? $server->config : [
            'host' => config('services.torrent.host'),
            'port' => config('services.torrent.port'),
            'username' => config('services.torrent.username'),
            'password' => config('services.torrent.password'),
        ];

        $baseUrl = rtrim((string) ($config['host'] ?? 'http://127.0.0.1'), '/').':'.(string) ($config['port'] ?? 9091);

        $key = $baseUrl.'|'.$server?->id ?? 'default';

        if (! isset($this->clients[$key])) {
            $clientConfig = [
                'username' => $config['username'] ?? 'admin',
                'password' => $config['password'] ?? '',
            ];

            $this->clients[$key] = TorrentClientManager::make('transmission', $baseUrl, $clientConfig);
        }

        return $this->clients[$key];
    }
}
