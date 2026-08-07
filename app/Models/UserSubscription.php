<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'storage_package_id',
        'status',
        'start_date',
        'end_date',
        'cancelled_at',
        'payment_method',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function storagePackage(): BelongsTo
    {
        return $this->belongsTo(StoragePackage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function (Builder $q) {
                $q->whereNull('end_date')->orWhere('end_date', '>', now());
            });
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && (is_null($this->end_date) || $this->end_date->isFuture());
    }
}
