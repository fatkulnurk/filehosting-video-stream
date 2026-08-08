<?php

declare(strict_types=1);

namespace App\Enums;

enum PackageFeature: string
{
    case CustomThumbnail = 'custom_thumbnail';
    case PasswordProtect = 'password_protect';
    case ApiAccess = 'api_access';
    case DownloadLimit = 'download_limit';
    case Webhook = 'webhook';
    case PrioritySupport = 'priority_support';
    case FileVersioning = 'file_versioning';
    case BatchUpload = 'batch_upload';
    case CustomEmbed = 'custom_embed';
    case Analytics = 'analytics';

    public function label(): string
    {
        return match ($this) {
            self::CustomThumbnail => 'Custom Thumbnail',
            self::PasswordProtect => 'Password Protected Links',
            self::ApiAccess => 'API Access',
            self::DownloadLimit => 'Download Limit',
            self::Webhook => 'Webhook Notifications',
            self::PrioritySupport => 'Priority Support',
            self::FileVersioning => 'File Versioning',
            self::BatchUpload => 'Batch Upload',
            self::CustomEmbed => 'Custom Embed Player',
            self::Analytics => 'Advanced Analytics',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CustomThumbnail => 'Upload custom thumbnail untuk video',
            self::PasswordProtect => 'Proteksi file dengan password',
            self::ApiAccess => 'Akses API untuk integrasi',
            self::DownloadLimit => 'Batas jumlah download per file',
            self::Webhook => 'Notifikasi via webhook',
            self::PrioritySupport => 'Prioritas support',
            self::FileVersioning => 'Simpan versi file sebelumnya',
            self::BatchUpload => 'Upload banyak file sekaligus',
            self::CustomEmbed => 'Customize embed player',
            self::Analytics => 'Analitik detail',
        };
    }

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($feature) => [$feature->value => $feature->label()])
            ->toArray();
    }
}
