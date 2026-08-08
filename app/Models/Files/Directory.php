<?php

declare(strict_types=1);

namespace App\Models\Files;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Directory extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'slug',
        'path',
    ];

    protected static function booted(): void
    {
        static::saving(function (Directory $directory) {
            if (empty($directory->slug)) {
                $directory->slug = Str::slug($directory->name);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Directory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Directory::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DirectoryShare::class);
    }

    public function scopeOwnedBy(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function getFormattedPathAttribute(): string
    {
        return $this->path ?: '/';
    }
}
