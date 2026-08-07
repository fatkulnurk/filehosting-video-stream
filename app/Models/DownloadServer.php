<?php

namespace App\Models;

use App\Enums\DownloaderDriver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DownloadServer extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'driver',
        'config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function driver(): DownloaderDriver
    {
        return DownloaderDriver::tryFrom($this->getAttribute('driver')) ?? DownloaderDriver::Local;
    }

    public function driverLabel(): string
    {
        return DownloaderDriver::tryFrom($this->getAttribute('driver'))?->label() ?? $this->getAttribute('driver');
    }
}
