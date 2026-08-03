<?php

namespace Database\Seeders;

use App\Models\ServicioExtra;
use Illuminate\Database\Seeder;

class ServicioExtraSeeder extends Seeder
{
    public function run(): void
    {
        // Orden usado por HabitacionServicioExtraSeeder (índices 0-5).
        $servicios = [
            ['nombre' => 'Desayuno', 'descripcion' => 'Servicio de desayuno incluido', 'precio' => 15, 'estado' => 1],
            ['nombre' => 'Almuerzo', 'descripcion' => 'Servicio de almuerzo incluido', 'precio' => 25, 'estado' => 1],
            ['nombre' => 'Cena', 'descripcion' => 'Servicio de cena incluido', 'precio' => 30, 'estado' => 1],
            ['nombre' => 'Lavandería', 'descripcion' => 'Lavado y planchado de ropa', 'precio' => 20, 'estado' => 1],
            ['nombre' => 'Transporte al terminal', 'descripcion' => 'Traslado desde/hacia la terminal de Tupiza', 'precio' => 40, 'estado' => 1],
            ['nombre' => 'City tour', 'descripcion' => 'Tour guiado por el centro de Tupiza', 'precio' => 50, 'estado' => 0],
        ];

        foreach ($servicios as $servicio) {
            ServicioExtra::create($servicio);
        }
    }
}
