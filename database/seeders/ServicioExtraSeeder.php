<?php

namespace Database\Seeders;

use App\Models\ServicioExtra;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServicioExtraSeeder extends Seeder
{
    public function run(): void
    {
        ServicioExtra::create([
            'nombre' => 'desayuno', 
            'descripcion' => 'Servicio de desayuno incluido',
            'precio' => 15.00,
            'estado' => 1
        ]);
        ServicioExtra::create([
            'nombre' => 'almuerzo',
            'descripcion' => 'Servicio de almuerzo incluido',
            'precio' => 25.00, 'estado' => 1,
            'estado' => 1
        ]);
        ServicioExtra::create([
            'nombre' => 'cena', 
            'descripcion' => 'Servicio de cena incluido',
            'precio' => 30.00, 
            'estado' => 1
        ]);
    }
}
