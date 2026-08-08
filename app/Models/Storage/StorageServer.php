<?php

declare(strict_types=1);

namespace App\Models\Storage;

use App\Enums\StorageDriver;
use App\Models\Files\FileChecksum;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageServer extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'driver',
        'config',
        'is_active',
        'is_primary',
        'backup_storage_server_id',
        'auto_backup',
        'storage_limit',
        'storage_used',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'auto_backup' => 'boolean',
            'storage_limit' => 'integer',
            'storage_used' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function backupStorageServer(): BelongsTo
    {
        return $this->belongsTo(StorageServer::class, 'backup_storage_server_id');
    }

    public function checksums(): HasMany
    {
        return $this->hasMany(FileChecksum::class, 'storage_server_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function driverLabel(): string
    {
        return StorageDriver::tryFrom($this->driver)?->label() ?? $this->driver;
    }

    public function getFormattedUsedAttribute(): string
    {
        return format_bytes($this->storage_used);
    }

    public function getFormattedLimitAttribute(): string
    {
        return is_null($this->storage_limit) ? 'Unlimited' : format_bytes($this->storage_limit);
    }

    public function usagePercentage(): ?float
    {
        if (is_null($this->storage_limit) || $this->storage_limit === 0) {
            return null;
        }

        return round(($this->storage_used / $this->storage_limit) * 100, 2);
    }
}
