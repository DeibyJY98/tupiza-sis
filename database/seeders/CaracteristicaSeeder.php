<?php

namespace Database\Seeders;

use App\Models\Caracteristica;
use Illuminate\Database\Seeder;

class CaracteristicaSeeder extends Seeder
{
    public function run(): void
    {
        // Orden usado por TipoHabitacionCaracteristicaSeeder (índices 0-7).
        $caracteristicas = [
            ['nombre' => 'Televisión', 'estado' => 1],
            ['nombre' => 'Aire acondicionado', 'estado' => 1],
            ['nombre' => 'Baño privado', 'estado' => 1],
            ['nombre' => 'Baño compartido', 'estado' => 1],
            ['nombre' => 'Wifi', 'estado' => 1],
            ['nombre' => 'Balcón', 'estado' => 1],
            ['nombre' => 'Minibar', 'estado' => 0],
            ['nombre' => 'Vista a la montaña', 'estado' => 1],
        ];

        foreach ($caracteristicas as $caracteristica) {
            Caracteristica::create($caracteristica);
        }
    }
}
