<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDirectoryController;
use App\Http\Controllers\Admin\AdminDownloaderController;
use App\Http\Controllers\Admin\AdminFileController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminStorageController;
use App\Http\Controllers\Admin\AdminStoragePackageController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\DirectoryShareController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\FileAccessController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FileFavoriteController;
use App\Http\Controllers\FileShareController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubtitleController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Public access
Route::get('/f/{id}', [PublicController::class, 'show'])->name('public.show');
Route::post('/f/{id}/unlock', [PublicController::class, 'unlock'])->name('public.unlock');
Route::get('/f/{id}/download', [PublicController::class, 'download'])->name('public.download');
Route::get('/embed/{id}', [EmbedController::class, 'show'])->name('embed.show');
Route::get('/stream/{id}', [StreamController::class, 'show'])->name('stream.show');
Route::get('/subtitles/{id}/{media}', [SubtitleController::class, 'show'])->name('subtitles.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');

    // Files
    Route::get('/files', [FileController::class, 'index'])->name('files.index');
    Route::post('/files', [FileController::class, 'store'])->name('files.store')->middleware('user.throttle:upload');
    Route::post('/files/from-url', [FileController::class, 'storeFromUrl'])->name('files.store-url')->middleware('user.throttle:upload');
    Route::get('/files/{file}', [FileController::class, 'show'])->name('files.show');
    Route::patch('/files/{file}', [FileController::class, 'update'])->name('files.update');
    Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
    Route::post('/files/{file}/rename', [FileController::class, 'rename'])->name('files.rename');
    Route::post('/files/{file}/move', [FileController::class, 'move'])->name('files.move');
    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');

    // Directories
    Route::post('/directories', [DirectoryController::class, 'store'])->name('directories.store');
    Route::get('/directories/{directory}', [DirectoryController::class, 'show'])->name('directories.show');
    Route::patch('/directories/{directory}', [DirectoryController::class, 'update'])->name('directories.update');
    Route::delete('/directories/{directory}', [DirectoryController::class, 'destroy'])->name('directories.destroy');
    Route::post('/directories/{directory}/move', [DirectoryController::class, 'move'])->name('directories.move');

    // Trash
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('/trash/{file}/restore', [TrashController::class, 'restore'])->name('trash.restore');
    Route::delete('/trash/{file}', [TrashController::class, 'destroy'])->name('trash.destroy');
    Route::post('/trash/purge', [TrashController::class, 'purge'])->name('trash.purge');

    // Versions
    Route::post('/files/{file}/versions', [VersionController::class, 'store'])->name('files.versions.store');
    Route::post('/files/{file}/versions/{version}/restore', [VersionController::class, 'restore'])->name('files.versions.restore');
    Route::delete('/files/{file}/versions/{version}', [VersionController::class, 'destroy'])->name('files.versions.destroy');

    // Subtitles
    Route::post('/files/{file}/subtitles', [SubtitleController::class, 'store'])->name('files.subtitles.store');
    Route::delete('/files/{file}/subtitles/{media}', [SubtitleController::class, 'destroy'])->name('files.subtitles.destroy');

    // Favorites
    Route::get('/favorites', [FileFavoriteController::class, 'index'])->name('files.favorites');
    Route::post('/files/{file}/favorite', [FileFavoriteController::class, 'toggle'])->name('files.favorite');

    // Shares
    Route::post('/files/{file}/share', [FileShareController::class, 'store'])->name('files.share');
    Route::delete('/shares/{share}', [FileShareController::class, 'destroy'])->name('shares.destroy');
    Route::get('/shared-with-me', [FileShareController::class, 'sharedWithMe'])->name('files.shared-with-me');
    Route::get('/shared-by-me', [FileShareController::class, 'sharedByMe'])->name('files.shared-by-me');
    Route::post('/directories/{directory}/share', [DirectoryShareController::class, 'store'])->name('directories.share');
    Route::delete('/directory-shares/{share}', [DirectoryShareController::class, 'destroy'])->name('directory-shares.destroy');
    Route::get('/directories/shared-with-me', [DirectoryShareController::class, 'sharedWithMe'])->name('directories.shared-with-me');

    // Recently accessed
    Route::get('/recently-accessed', [FileAccessController::class, 'recentlyAccessed'])->name('files.recently-accessed');

    // Activity logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Subscriptions
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    // API keys
    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    // Webhooks
    Route::get('/webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
    Route::post('/webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
    Route::patch('/webhooks/{webhook}', [WebhookController::class, 'update'])->name('webhooks.update');
    Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
    Route::post('/webhooks/{webhook}/regenerate', [WebhookController::class, 'regenerate'])->name('webhooks.regenerate');
    Route::get('/webhooks/{webhook}/logs', [WebhookController::class, 'logs'])->name('webhooks.logs');

    // Batch
    Route::post('/batch/download', [BatchController::class, 'download'])->name('batch.download');

    // Cloud downloads (URL + torrent)
    Route::get('/cloud-downloads', [DownloadController::class, 'index'])->name('cloud-downloads.index');
    Route::post('/cloud-downloads/url', [DownloadController::class, 'storeUrl'])->name('cloud-downloads.url');
    Route::post('/cloud-downloads/torrent', [DownloadController::class, 'storeTorrent'])->name('cloud-downloads.torrent');
    Route::post('/cloud-downloads/{task}/retry', [DownloadController::class, 'retry'])->name('cloud-downloads.retry');
    Route::post('/cloud-downloads/{task}/cancel', [DownloadController::class, 'cancel'])->name('cloud-downloads.cancel');

    // Admin
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/role', [AdminUserController::class, 'changeRole'])->name('users.role');
        Route::post('/users/{user}/quota', [AdminUserController::class, 'setQuota'])->name('users.quota');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::get('/files', [AdminFileController::class, 'index'])->name('files.index');
        Route::post('/files', [AdminFileController::class, 'store'])->name('files.store');
        Route::get('/files/directories', [AdminFileController::class, 'directories'])->name('files.directories');
        Route::get('/files/{file}', [AdminFileController::class, 'show'])->name('files.show');
        Route::patch('/files/{file}', [AdminFileController::class, 'update'])->name('files.update');
        Route::delete('/files/{file}', [AdminFileController::class, 'destroy'])->name('files.destroy');
        Route::delete('/files/{file}/force', [AdminFileController::class, 'forceDestroy'])->name('files.forceDestroy');
        Route::get('/directories', [AdminDirectoryController::class, 'index'])->name('directories.index');
        Route::post('/directories', [AdminDirectoryController::class, 'store'])->name('directories.store');
        Route::patch('/directories/{directory}', [AdminDirectoryController::class, 'update'])->name('directories.update');
        Route::delete('/directories/{directory}', [AdminDirectoryController::class, 'destroy'])->name('directories.destroy');
        Route::delete('/directories/{directory}/force', [AdminDirectoryController::class, 'forceDestroy'])->name('directories.forceDestroy');
        Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/subscriptions', [AdminSubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::patch('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::post('/subscriptions/{user}/cancel', [AdminSubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::get('/packages', [AdminStoragePackageController::class, 'index'])->name('packages.index');
        Route::post('/packages', [AdminStoragePackageController::class, 'store'])->name('packages.store');
        Route::patch('/packages/{package}', [AdminStoragePackageController::class, 'update'])->name('packages.update');
        Route::delete('/packages/{package}', [AdminStoragePackageController::class, 'destroy'])->name('packages.destroy');
        Route::get('/storage', [AdminStorageController::class, 'index'])->name('storage.index');
        Route::post('/storage', [AdminStorageController::class, 'store'])->name('storage.store');
        Route::get('/storage/{server}', [AdminStorageController::class, 'show'])->name('storage.show');
        Route::patch('/storage/{server}', [AdminStorageController::class, 'update'])->name('storage.update');
        Route::post('/storage/{server}/deactivate', [AdminStorageController::class, 'deactivate'])->name('storage.deactivate');
        Route::delete('/storage/{server}', [AdminStorageController::class, 'destroy'])->name('storage.destroy');
        Route::get('/downloaders', [AdminDownloaderController::class, 'index'])->name('downloaders.index');
        Route::post('/downloaders', [AdminDownloaderController::class, 'store'])->name('downloaders.store');
        Route::post('/downloaders/{server}/test', [AdminDownloaderController::class, 'test'])->name('downloaders.test');
        Route::patch('/downloaders/{server}', [AdminDownloaderController::class, 'update'])->name('downloaders.update');
        Route::delete('/downloaders/{server}', [AdminDownloaderController::class, 'destroy'])->name('downloaders.destroy');
        Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');
    });
});
