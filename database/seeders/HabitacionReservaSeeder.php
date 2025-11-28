<?php

namespace Database\Seeders;

use App\Models\HabitacionReserva;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HabitacionReservaSeeder extends Seeder
{
    public function run(): void
    {
        HabitacionReserva::create(['id_reserva' => 1, 'id_habitacion' => 1, 'monto' => 60]);
        HabitacionReserva::create(['id_reserva' => 2, 'id_habitacion' => 3, 'monto' => 90]);
        HabitacionReserva::create(['id_reserva' => 3, 'id_habitacion' => 5, 'monto' => 100]);
    }
}
