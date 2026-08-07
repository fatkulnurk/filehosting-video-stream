<?php

use App\Jobs\CleanupDownloadsJob;
use App\Jobs\CleanupExpiredLinksJob;
use App\Jobs\CleanupOldLogsJob;
use App\Jobs\ExpireSubscriptionsJob;
use App\Jobs\MonitorTorrentDownloadsJob;
use App\Jobs\PurgeTrashJob;
use App\Jobs\SyncStorageStatsJob;
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
