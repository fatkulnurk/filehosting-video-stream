<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoragePackage extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'storage_limit',
        'bandwidth_limit',
        'file_limit',
        'max_file_size',
        'features',
        'price_monthly',
        'price_yearly',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'storage_limit' => 'integer',
            'bandwidth_limit' => 'integer',
            'file_limit' => 'integer',
            'max_file_size' => 'integer',
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function getFormattedStorageLimitAttribute(): string
    {
        return format_bytes($this->storage_limit);
    }

    public function getFormattedMaxFileSizeAttribute(): string
    {
        return is_null($this->max_file_size) ? 'Unlimited' : format_bytes($this->max_file_size);
    }
}
