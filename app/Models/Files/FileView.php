<?php

declare(strict_types=1);

namespace App\Models\Files;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileView extends Model
{
    use HasUuids;

    protected $fillable = [
        'file_id',
        'ip',
        'user_agent',
        'referer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
