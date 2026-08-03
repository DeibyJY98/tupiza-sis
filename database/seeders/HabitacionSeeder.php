<?php

namespace Database\Seeders;

use App\Models\Habitacion;
use App\Models\TipoHabitacion;
use Illuminate\Database\Seeder;

class HabitacionSeeder extends Seeder
{
    public function run(): void
    {
        // 'tipo' es el índice (0-based) dentro del orden de creación de TipoHabitacionSeeder.
        // 'estado' inicia en 1 (disponible) para todas: HabitacionReservaObserver recalcula el
        // estado real (disponible/ocupada) en cuanto HabitacionReservaSeeder crea las reservas.
        $tipos = TipoHabitacion::orderBy('id')->get();

        $habitaciones = [
            ['numero_habitacion' => '101', 'planta' => 1, 'tipo' => 0],
            ['numero_habitacion' => '102', 'planta' => 1, 'tipo' => 0],
            ['numero_habitacion' => '103', 'planta' => 1, 'tipo' => 0],
            ['numero_habitacion' => '104', 'planta' => 1, 'tipo' => 0],
            ['numero_habitacion' => '201', 'planta' => 2, 'tipo' => 1],
            ['numero_habitacion' => '202', 'planta' => 2, 'tipo' => 1],
            ['numero_habitacion' => '203', 'planta' => 2, 'tipo' => 1],
            ['numero_habitacion' => '204', 'planta' => 2, 'tipo' => 1],
            ['numero_habitacion' => '301', 'planta' => 3, 'tipo' => 2],
            ['numero_habitacion' => '302', 'planta' => 3, 'tipo' => 2],
            ['numero_habitacion' => '303', 'planta' => 3, 'tipo' => 2],
            ['numero_habitacion' => '401', 'planta' => 4, 'tipo' => 3],
            ['numero_habitacion' => '402', 'planta' => 4, 'tipo' => 3],
            ['numero_habitacion' => '501', 'planta' => 5, 'tipo' => 4],
            ['numero_habitacion' => '502', 'planta' => 5, 'tipo' => 4],
        ];

        foreach ($habitaciones as $data) {
            Habitacion::create([
                'numero_habitacion' => $data['numero_habitacion'],
                'planta' => $data['planta'],
                'estado' => 1,
                'id_tipo_habitacion' => $tipos[$data['tipo']]->id,
            ]);
        }
    }
}
