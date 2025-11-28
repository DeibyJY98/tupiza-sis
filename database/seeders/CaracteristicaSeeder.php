<?php

namespace Database\Seeders;

use App\Models\Caracteristica;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CaracteristicaSeeder extends Seeder
{
    public function run(): void
    {
        Caracteristica::create(['nombre' => 'television', 'estado' => 1]);
        Caracteristica::create(['nombre' => 'aire', 'estado' => 1]);
        Caracteristica::create(['nombre' => 'baño privado', 'estado' => 1]);
        Caracteristica::create(['nombre' => 'baño compartido', 'estado' => 1]);
    }
}
