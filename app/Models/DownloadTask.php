<?php

namespace App\Models;

use App\Enums\DownloadTaskStatus;
use App\Enums\DownloadTaskType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadTask extends Model
{
    use HasUuids;

    protected $appends = [
        'status_label',
        'status_badge_variant',
        'source_display',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'source',
        'directory_id',
        'disk',
        'status',
        'progress',
        'info_hash',
        'torrent_id',
        'file_id',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'float',
            'status' => DownloadTaskStatus::class,
            'type' => DownloadTaskType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class);
    }

    public function torrent(): BelongsTo
    {
        return $this->belongsTo(Torrent::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusBadgeVariantAttribute(): string
    {
        return $this->status->badgeVariant();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function getSourceDisplayAttribute(): string
    {
        if ($this->type === DownloadTaskType::Torrent) {
            return $this->info_hash ? substr($this->info_hash, 0, 16).'…' : $this->source;
        }

        return $this->source;
    }
}
