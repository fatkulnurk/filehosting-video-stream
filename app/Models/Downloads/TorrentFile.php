<?php

declare(strict_types=1);

namespace App\Models\Downloads;

use App\Models\Files\FileChecksum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TorrentFile extends Model
{
    use HasUuids;

    protected $fillable = [
        'torrent_id',
        'file_checksum_id',
        'relative_path',
        'file_name',
        'size',
    ];

    public function torrent(): BelongsTo
    {
        return $this->belongsTo(Torrent::class);
    }

    public function checksum(): BelongsTo
    {
        return $this->belongsTo(FileChecksum::class, 'file_checksum_id');
    }
}
