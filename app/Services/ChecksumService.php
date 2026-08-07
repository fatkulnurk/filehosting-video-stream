<?php

namespace App\Services;

use App\Services\Interfaces\ChecksumServiceInterface;
use Illuminate\Http\UploadedFile;

class ChecksumService implements ChecksumServiceInterface
{
    public function calculate(UploadedFile $file): array
    {
        $tempPath = $file->getRealPath();

        return [
            'crc32' => crc32((string) file_get_contents($tempPath)),
            'md5' => md5_file($tempPath),
            'sha1' => sha1_file($tempPath),
            'sha256' => hash_file('sha256', $tempPath),
            'sha512' => hash_file('sha512', $tempPath),
        ];
    }

    public function crc32(UploadedFile $file): int
    {
        return crc32((string) file_get_contents($file->getRealPath()));
    }

    public function md5(UploadedFile $file): string
    {
        return md5_file($file->getRealPath());
    }

    public function sha1(UploadedFile $file): string
    {
        return sha1_file($file->getRealPath());
    }

    public function sha256(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    public function sha512(UploadedFile $file): string
    {
        return hash_file('sha512', $file->getRealPath());
    }

    public function verify(string $path, string $expectedSha256): bool
    {
        return hash_equals($expectedSha256, hash_file('sha256', $path));
    }
}
