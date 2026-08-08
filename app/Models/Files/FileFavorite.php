<?php

declare(strict_types=1);

namespace App\Models\Files;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileFavorite extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'file_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
