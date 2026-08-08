<?php

declare(strict_types=1);

namespace App\Models\Integrations;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'key',
        'permissions',
        'rate_limit',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $hidden = [
        'key',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'rate_limit' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}
