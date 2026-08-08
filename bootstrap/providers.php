<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\PolicyServiceProvider;
use App\Providers\StorageServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    PolicyServiceProvider::class,
    StorageServiceProvider::class,
];
