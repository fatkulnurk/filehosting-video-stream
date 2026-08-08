<?php

declare(strict_types=1);

namespace App\Enums;

enum DownloadTaskStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Downloading = 'downloading';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Queued => __('downloads.queued'),
            self::Processing => __('downloads.processing'),
            self::Downloading => __('downloads.downloading'),
            self::Completed => __('downloads.completed'),
            self::Failed => __('downloads.failed'),
            self::Cancelled => __('downloads.cancelled'),
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Failed, self::Cancelled => 'danger',
            self::Downloading => 'accent',
            default => 'neutral',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled], true);
    }
}
