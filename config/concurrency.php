<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Concurrency Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default concurrency driver that will be used by
    | the Concurrency facade. "process" is the safest cross-platform default;
    | "fork" is faster on CLI but requires spatie/fork. "sync" runs closures
    | inline (used by the test suite so in-memory databases stay consistent).
    |
    */

    'default' => env('CONCURRENCY_DRIVER', 'process'),

];
