<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\StorageServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    StorageServiceProvider::class,
];