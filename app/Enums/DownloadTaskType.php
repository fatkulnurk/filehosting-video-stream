<?php

namespace App\Enums;

enum DownloadTaskType: string
{
    case Url = 'url';
    case Torrent = 'torrent';

    public function label(): string
    {
        return match ($this) {
            self::Url => 'URL',
            self::Torrent => 'Torrent',
        };
    }
}
