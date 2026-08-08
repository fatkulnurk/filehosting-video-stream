<?php

declare(strict_types=1);

namespace App\Models\Files;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryShare extends Model
{
    use HasUuids;

    protected $fillable = [
        'directory_id',
        'shared_by',
        'shared_with',
        'permission',
        'recursive',
    ];

    protected function casts(): array
    {
        return [
            'recursive' => 'boolean',
        ];
    }

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class);
    }

    public function sharedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with');
    }
}
