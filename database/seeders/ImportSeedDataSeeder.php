<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ImportSeedDataSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('data:import-seed-csv', [
            '--path' => 'database/seed-data',
            '--truncate' => true,
        ]);

        Artisan::call('data:materialize-seed', [
            '--truncate' => true,
        ]);

        Artisan::call('data:import-formadores', [
            '--truncate' => true,
        ]);
    }
}
