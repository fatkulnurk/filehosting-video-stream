<?php

namespace Database\Seeders;

use App\Models\Directory;
use Illuminate\Database\Seeder;

class DirectorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Directory::factory(10)->create();
    }
}
