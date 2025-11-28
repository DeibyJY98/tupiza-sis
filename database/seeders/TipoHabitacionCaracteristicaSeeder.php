<?php

namespace Database\Seeders;

use App\Models\TipoHabitacionCaracteristica;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoHabitacionCaracteristicaSeeder extends Seeder
{
    public function run(): void
    {   
        //Habitaciones Simples
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 1,]);
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 2,]);
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 4,]);
        //Habitaciones Dobles
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 1,]);
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 2,]);
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 3,]);
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 4,]);
        //Habitaciones Matrimoniales
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 1,]);
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 2,]);
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 3,]);
        TipoHabitacionCaracteristica::create(['id_tipo_habitacion' => 1, 'id_caracteristica' => 4,]);
    }
}
