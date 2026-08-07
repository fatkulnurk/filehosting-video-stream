<?php

namespace App\Services;

use Arokettu\Bencode\Bencode;
use Arokettu\Bencode\Bencode\Collection;

class TorrentHashService
{
    public function fromSource(string $source): ?string
    {
        if (str_starts_with(strtolower($source), 'magnet:')) {
            return $this->fromMagnet($source);
        }

        if (is_file($source)) {
            return $this->fromTorrentFile($source);
        }

        return null;
    }

    public function fromMagnet(string $magnet): ?string
    {
        if (preg_match('/xt=urn:btih:([a-zA-Z0-9]+)/i', $magnet, $m)) {
            $value = $m[1];

            if (strlen($value) === 40 && ctype_xdigit($value)) {
                return strtolower($value);
            }

            // base32-encoded (32 chars) v1 hash
            if (strlen($value) === 32) {
                $hex = bin2hex(base32_decode($value));

                return strtolower($hex);
            }
        }

        return null;
    }

    public function fromTorrentFile(string $path): ?string
    {
        try {
            $contents = file_get_contents($path);

            if ($contents === false) {
                return null;
            }

            $decoded = Bencode::decode($contents, dictType: Collection::ARRAY);

            if (! isset($decoded['info']) || ! is_array($decoded['info'])) {
                return null;
            }

            return strtolower(sha1(Bencode::encode($decoded['info'])));
        } catch (\Throwable) {
            return null;
        }
    }

    public function nameFromTorrentFile(string $path): ?string
    {
        try {
            $contents = file_get_contents($path);

            if ($contents === false) {
                return null;
            }

            $decoded = Bencode::decode($contents, dictType: Collection::ARRAY);

            if (isset($decoded['info']['name']) && is_string($decoded['info']['name'])) {
                return $decoded['info']['name'];
            }
        } catch (\Throwable) {
            // ignore
        }

        return null;
    }

    public function isMagnet(string $source): bool
    {
        return str_starts_with(strtolower($source), 'magnet:');
    }
}
