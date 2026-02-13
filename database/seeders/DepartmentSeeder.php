<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'ANTIOQUIA',
            'CÓRDOBA',
            'CHOCÓ',
            'ARCHIPIÉLAGO DE SAN ANDRÉS, PROVIDENCIA Y SANTA CATALINA',
            'SUCRE',
        ];

        foreach ($departments as $department) {
            Department::create([
                'name' => $department,
            ]);
        }
    }
}
