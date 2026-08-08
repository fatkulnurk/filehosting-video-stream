<?php

declare(strict_types=1);

namespace App\Models\Files;

use App\Models\Storage\StorageServer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileChecksum extends Model
{
    use HasUuids;

    protected $fillable = [
        'crc32',
        'md5',
        'sha1',
        'sha256',
        'sha512',
        'storage_server_id',
        'disk',
        'path',
        'size',
        'mime_type',
        'file_name',
        'reference_count',
    ];

    protected function casts(): array
    {
        return [
            'crc32' => 'integer',
            'size' => 'integer',
            'reference_count' => 'integer',
        ];
    }

    public function storageServer(): BelongsTo
    {
        return $this->belongsTo(StorageServer::class, 'storage_server_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'file_checksum_id');
    }
}
