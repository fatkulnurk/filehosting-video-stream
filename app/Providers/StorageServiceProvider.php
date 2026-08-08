<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Services\Storage\StorageServiceInterface;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            if (Schema::hasTable('storage_servers')
                && ! app()->runningUnitTests()) {
                $storageService = app(StorageServiceInterface::class);
                $storageService->registerAllDisks();
            }
        });
    }
}
