<?php

declare(strict_types=1);
use App\Jobs\ActivityLogs\CleanupOldLogsJob;
use App\Jobs\Downloads\CleanupDownloadsJob;
use App\Jobs\Downloads\MonitorTorrentDownloadsJob;
use App\Jobs\Files\CleanupExpiredLinksJob;
use App\Jobs\Files\PurgeTrashJob;
use App\Jobs\Storage\SyncStorageStatsJob;
use App\Jobs\Subscriptions\ExpireSubscriptionsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CleanupExpiredLinksJob)->hourly();
Schedule::job(new SyncStorageStatsJob)->everySixHours();
Schedule::job(new CleanupOldLogsJob)->dailyAt('03:00');
Schedule::job(new PurgeTrashJob)->dailyAt('03:30');
Schedule::job(new ExpireSubscriptionsJob)->hourly();
Schedule::job(new MonitorTorrentDownloadsJob)->everyMinute();
Schedule::job(new CleanupDownloadsJob)->dailyAt('04:00');
