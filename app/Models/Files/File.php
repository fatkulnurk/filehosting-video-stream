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
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class File extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia, SoftDeletes;

    protected $appends = [
        'formatted_size',
        'public_url',
        'embed_url',
        'stream_url',
        'embed_code',
    ];

    protected $fillable = [
        'user_id',
        'directory_id',
        'file_checksum_id',
        'name',
        'slug',
        'status',
        'views',
        'downloads',
        'expires_at',
        'password',
        'download_limit',
        'download_count',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'views' => 'integer',
            'downloads' => 'integer',
            'download_limit' => 'integer',
            'download_count' => 'integer',
            'expires_at' => 'datetime',
            'is_public' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (File $file) {
            if (empty($file->slug)) {
                $file->slug = Str::slug($file->name) ?: Str::lower(Str::random(8));
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default');
        $this->addMediaCollection('versions');
        $this->addMediaCollection('subtitles');
    }

    public function subtitles()
    {
        return $this->media()->where('collection_name', 'subtitles');
    }

    public function getSubtitlesAttribute(): array
    {
        return $this->getMedia('subtitles')
            ->sortBy('order_column')
            ->map(fn ($media) => [
                'id' => $media->getKey(),
                'url' => route('subtitles.show', [$this->id, $media->getKey()]),
                'srclang' => $media->getCustomProperty('language', 'en'),
                'label' => $media->getCustomProperty('label', 'Subtitles'),
                'default' => (bool) $media->getCustomProperty('default', false),
            ])
            ->values()
            ->all();
    }

    public function getSignedStreamUrlAttribute(): string
    {
        return URL::temporarySignedRoute(
            'stream.show',
            now()->addMinutes(config('filehosting.player.signed_url_ttl_minutes', 30)),
            ['id' => $this->id]
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class);
    }

    public function checksum(): BelongsTo
    {
        return $this->belongsTo(FileChecksum::class, 'file_checksum_id');
    }

    public function viewsLog(): HasMany
    {
        return $this->hasMany(FileView::class);
    }

    public function downloadsLog(): HasMany
    {
        return $this->hasMany(FileDownload::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FileVersion::class)->orderByDesc('version');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(FileShare::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(FileFavorite::class);
    }

    public function scopeOwnedBy(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isExpired(): bool
    {
        return ! is_null($this->expires_at) && $this->expires_at->isPast();
    }

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->getFirstMedia()?->mime_type, 'video/');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->getFirstMedia()?->mime_type, 'image/');
    }

    public function extension(): string
    {
        $name = $this->getFirstMedia()?->file_name ?? $this->name;

        return pathinfo($name, PATHINFO_EXTENSION);
    }

    public function size(): int
    {
        return (int) ($this->getFirstMedia()?->size ?? 0);
    }

    public function getFormattedSizeAttribute(): string
    {
        return format_bytes($this->size());
    }

    public function getFormattedViewsAttribute(): string
    {
        return number_format($this->views);
    }

    public function getFormattedDownloadsAttribute(): string
    {
        return number_format($this->downloads);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('public.show', $this->id);
    }

    public function getEmbedUrlAttribute(): string
    {
        return route('embed.show', $this->id);
    }

    public function getStreamUrlAttribute(): string
    {
        return route('stream.show', $this->id);
    }

    public function getEmbedCodeAttribute(): string
    {
        $width = config('filehosting.embed.width', 640);
        $height = config('filehosting.embed.height', 360);

        return sprintf(
            '<iframe src="%s" width="%d" height="%d" frameborder="0" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture"></iframe>',
            $this->embed_url,
            $width,
            $height
        );
    }

    public function isPasswordProtected(): bool
    {
        return ! empty($this->password);
    }

    public function downloadLimitReached(): bool
    {
        return ! is_null($this->download_limit) && $this->download_count >= $this->download_limit;
    }
}
