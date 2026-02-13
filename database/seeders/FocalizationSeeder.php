<?php

namespace Database\Seeders;

use App\Models\Focalization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FocalizationSeeder extends Seeder
{
    public function run(): void
    {
        $focalizations = [
            'AUDIOVISUALES',
            'LITERATURA',
            'MÚSICA',
            'DANZA',
            'TEATRO',
        ];

        foreach ($focalizations as $name) {
            Focalization::create(['name' => $name]);
        }
    }
}
