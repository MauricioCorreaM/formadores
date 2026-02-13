<?php

namespace Database\Seeders;

use App\Models\Secretaria;
use Illuminate\Database\Seeder;

class SecretariaSeeder extends Seeder
{
    public function run(): void
    {
        $secretarias = [
            'ANTIOQUIA',
            'APARTADO',
            'BELLO',
            'CHOCO',
            'CORDOBA',
            'ENVIGADO',
            'ITAGUI',
            'LA ESTRELLA',
            'LORICA',
            'MEDELLIN',
            'MONTERIA',
            'QUIBDÓ',
            'RIONEGRO',
            'SABANETA',
            'SAHAGUN',
            'SAN ANDRES',
            'SINCELEJO',
            'SUCRE',
            'TURBO',
        ];

        foreach ($secretarias as $secretaria) {
            Secretaria::create([
                'name' => $secretaria,
            ]);
        }
    }
}
