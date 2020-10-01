<?php

namespace Database\Seeders;

use App\Models\StorageServer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'fatkul nur k',
            'email' => 'fatkulnurk@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'remember_token' => Str::random(10),
        ]);

        \App\Models\User::factory(10)->create();

        StorageServer::create([
            'name' => 'local_default',
            'config' => json_encode([
                'driver' => 'local',
                'root' => storage_path('app'),
            ]),
            'is_default' => true
        ]);

        $this->call([
            DirectorySeeder::class,
            FileSeeder::class
        ]);
    }
}
