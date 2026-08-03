<?php

namespace Database\Seeders;

use App\Models\Caracteristica;
use App\Models\TipoHabitacion;
use App\Models\TipoHabitacionCaracteristica;
use Illuminate\Database\Seeder;

class TipoHabitacionCaracteristicaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = TipoHabitacion::orderBy('id')->get();
        $caracteristicas = Caracteristica::orderBy('id')->get();

        // Índices (0-based) de Caracteristica por cada tipo de habitación.
        // 0 Televisión, 1 Aire acondicionado, 2 Baño privado, 3 Baño compartido,
        // 4 Wifi, 5 Balcón, 6 Minibar, 7 Vista a la montaña.
        $mapa = [
            0 => [0, 3, 4],          // Simple
            1 => [0, 1, 2, 4],       // Doble
            2 => [0, 1, 2, 4, 6],    // Matrimonial
            3 => [0, 1, 2, 4, 5, 6, 7], // Suite
            4 => [0, 2, 4, 5],       // Familiar
        ];

        foreach ($mapa as $tipoIdx => $caracteristicaIdxs) {
            foreach ($caracteristicaIdxs as $caracteristicaIdx) {
                TipoHabitacionCaracteristica::create([
                    'id_tipo_habitacion' => $tipos[$tipoIdx]->id,
                    'id_caracteristica' => $caracteristicas[$caracteristicaIdx]->id,
                ]);
            }
        }
    }
}
