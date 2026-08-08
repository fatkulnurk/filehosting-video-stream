<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Downloads\TorrentHashService;
use Arokettu\Bencode\Bencode;
use PHPUnit\Framework\TestCase;

class TorrentHashServiceTest extends TestCase
{
    private TorrentHashService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TorrentHashService;
    }

    public function test_extracts_hex_info_hash_from_magnet(): void
    {
        $magnet = 'magnet:?xt=urn:btih:6f46bf0445a33ec41c5651e5716d0e6c0e03ba44&dn=ubuntu';

        $this->assertSame(
            '6f46bf0445a33ec41c5651e5716d0e6c0e03ba44',
            $this->service->fromMagnet($magnet)
        );
    }

    public function test_extracts_base32_info_hash_from_magnet(): void
    {
        // base32 of 6f46bf0445a33ec41c5651e5716d0e6c0e03ba44
        $base32 = base32_encode(hex2bin('6f46bf0445a33ec41c5651e5716d0e6c0e03ba44'));

        $this->assertSame(
            '6f46bf0445a33ec41c5651e5716d0e6c0e03ba44',
            $this->service->fromMagnet("magnet:?xt=urn:btih:{$base32}")
        );
    }

    public function test_returns_null_for_invalid_magnet(): void
    {
        $this->assertNull($this->service->fromMagnet('magnet:?xt=urn:btih:zzzz'));
        $this->assertNull($this->service->fromMagnet('not-a-magnet'));
    }

    public function test_parses_torrent_file_info_hash(): void
    {
        // Build a minimal valid .torrent with bencode (via arokettu/bencode)
        $info = ['name' => 'test.txt', 'length' => 123, 'piece length' => 16384, 'pieces' => str_repeat("\0", 20)];
        $torrent = [
            'announce' => 'http://tracker.example/announce',
            'info' => $info,
        ];

        $encoded = Bencode::encode($torrent);
        $path = tempnam(sys_get_temp_dir(), 'fhv_t_');
        file_put_contents($path, $encoded);

        try {
            $expected = strtolower(sha1(Bencode::encode($info)));

            $this->assertSame($expected, $this->service->fromTorrentFile($path));
            $this->assertSame('test.txt', $this->service->nameFromTorrentFile($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_returns_null_for_invalid_torrent_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fhv_t_');
        file_put_contents($path, 'not bencode at all !!!');

        try {
            $this->assertNull($this->service->fromTorrentFile($path));
        } finally {
            @unlink($path);
        }
    }
}
