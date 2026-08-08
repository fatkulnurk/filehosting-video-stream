<?php

declare(strict_types=1);

namespace App\Models\Downloads;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Torrent extends Model
{
    use HasUuids;

    protected $fillable = [
        'info_hash',
        'name',
        'total_size',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(TorrentFile::class);
    }

    public function downloadTasks(): HasMany
    {
        return $this->hasMany(DownloadTask::class);
    }
}
