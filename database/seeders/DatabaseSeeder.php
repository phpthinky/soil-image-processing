<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Remove all saved webcam captures so stale images from a previous
        // database do not bleed through after migrate:fresh --seed.
        File::deleteDirectory(public_path('captures'));

        $this->call([
            UserSeeder::class,
            PhColorChartSeeder::class,
            NpkColorChartSeeder::class,
        ]);
    }
}
