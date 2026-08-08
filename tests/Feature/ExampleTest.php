<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\StoragePackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(StoragePackageSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_registration_page_loads(): void
    {
        $this->seed(StoragePackageSeeder::class);

        $this->get('/register')->assertStatus(200);
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertStatus(200);
    }
}
