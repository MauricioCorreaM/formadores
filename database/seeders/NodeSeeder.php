<?php

namespace Database\Seeders;

use App\Models\Node;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NodeSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 28; $i++) {
            Node::create([
                'name' => "Nodo $i",
            ]);
        }
    }
}
