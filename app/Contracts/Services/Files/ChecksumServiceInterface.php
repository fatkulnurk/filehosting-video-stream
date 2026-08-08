<?php

declare(strict_types=1);

namespace App\Contracts\Services\Files;

use Illuminate\Http\UploadedFile;

interface ChecksumServiceInterface
{
    public function calculate(UploadedFile $file): array;

    public function crc32(UploadedFile $file): int;

    public function md5(UploadedFile $file): string;

    public function sha1(UploadedFile $file): string;

    public function sha256(UploadedFile $file): string;

    public function sha512(UploadedFile $file): string;

    public function verify(string $path, string $expectedSha256): bool;
}
