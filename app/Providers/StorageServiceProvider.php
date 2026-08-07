<?php

namespace App\Providers;

use App\Services\Interfaces\StorageServiceInterface;
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
            if (\Illuminate\Support\Facades\Schema::hasTable('storage_servers')
                && ! app()->runningUnitTests()) {
                $storageService = app(StorageServiceInterface::class);
                $storageService->registerAllDisks();
            }
        });
    }
}
