<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileDownload extends Model
{
    use HasUuids;

    protected $fillable = [
        'file_id',
        'ip',
        'user_agent',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
