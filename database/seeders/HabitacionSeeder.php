<?php

namespace Database\Seeders;

use App\Models\Habitacion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HabitacionSeeder extends Seeder
{
    public function run(): void
    {
        Habitacion::create([
            'numero_habitacion' => '101',
            'planta' => 1,
            'estado' => 1,
            'id_tipo_habitacion' => 1
        ]);
        Habitacion::create([
            'numero_habitacion' => '102',
            'planta' => 1,
            'estado' => 1,
            'id_tipo_habitacion' => 1
        ]);
        habitacion::create([
            'numero_habitacion' => '201',
            'planta' => 2,
            'estado' => 1,
            'id_tipo_habitacion' => 2
        ]);
        Habitacion::create([
            'numero_habitacion' => '202',
            'planta' => 2,
            'estado' => 1,
            'id_tipo_habitacion' => 2
        ]);
        Habitacion::create([
            'numero_habitacion' => '301',
            'planta' => 3,
            'estado' => 1,
            'id_tipo_habitacion' => 3
        ]);
        Habitacion::create([
            'numero_habitacion' => '302',
            'planta' => 3,
            'estado' => 1,
            'id_tipo_habitacion' => 3
        ]);
    }
}
