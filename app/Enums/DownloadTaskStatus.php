<?php

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
            self::Queued => 'Queued',
            self::Processing => 'Processing',
            self::Downloading => 'Downloading',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
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
