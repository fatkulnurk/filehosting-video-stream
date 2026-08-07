<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DbConnectionProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_probe_db_connection(): void
    {
        fwrite(STDERR, "\n[DB-DRIVER] ".\DB::connection()->getDriverName()."\n");
        fwrite(STDERR, "[DB-NAME] ".\DB::connection()->getDatabaseName()."\n");
        $this->assertTrue(true);
    }
}
