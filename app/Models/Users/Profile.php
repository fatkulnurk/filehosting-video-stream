<?php

declare(strict_types=1);

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'phone',
        'avatar',
        'bio',
        'storage_limit',
        'storage_used',
        'storage_used_at',
    ];

    protected function casts(): array
    {
        return [
            'storage_limit' => 'integer',
            'storage_used' => 'integer',
            'storage_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedStorageLimitAttribute(): string
    {
        if (is_null($this->storage_limit)) {
            return 'Unlimited';
        }

        return format_bytes($this->storage_limit);
    }

    public function getFormattedStorageUsedAttribute(): string
    {
        return format_bytes($this->storage_used);
    }

    public function getUsagePercentageAttribute(): ?float
    {
        if (is_null($this->storage_limit) || $this->storage_limit === 0) {
            return null;
        }

        return round(($this->storage_used / $this->storage_limit) * 100, 2);
    }
}
