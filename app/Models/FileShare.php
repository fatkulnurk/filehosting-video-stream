<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileShare extends Model
{
    use HasUuids;

    protected $fillable = [
        'file_id',
        'shared_by',
        'shared_with',
        'permission',
        'password',
        'expires_at',
        'download_limit',
        'download_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'download_limit' => 'integer',
            'download_count' => 'integer',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function sharedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with');
    }

    public function isExpired(): bool
    {
        return ! is_null($this->expires_at) && $this->expires_at->isPast();
    }

    public function canDownload(): bool
    {
        if ($this->permission !== 'download') {
            return false;
        }

        if (! is_null($this->download_limit) && $this->download_count >= $this->download_limit) {
            return false;
        }

        return true;
    }
}
