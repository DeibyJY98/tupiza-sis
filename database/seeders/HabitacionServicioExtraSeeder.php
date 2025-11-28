<?php

namespace Database\Seeders;

use App\Models\HabitacionServicioExtra;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HabitacionServicioExtraSeeder extends Seeder
{
    public function run(): void
    {
        HabitacionServicioExtra::create(['id_habitacion_reserva' => 1, 'id_servicio_extra' => 1]);
        HabitacionServicioExtra::create(['id_habitacion_reserva' => 1, 'id_servicio_extra' => 2]);
        HabitacionServicioExtra::create(['id_habitacion_reserva' => 2, 'id_servicio_extra' => 1]);
        HabitacionServicioExtra::create(['id_habitacion_reserva' => 2, 'id_servicio_extra' => 3]);
    }
}
