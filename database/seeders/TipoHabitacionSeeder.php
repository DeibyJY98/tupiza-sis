<?php

namespace Database\Seeders;

use App\Models\TipoHabitacion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoHabitacionSeeder extends Seeder
{
    public function run(): void
    {
        TipoHabitacion::create([
            'nombre'=> 'Simple',
            'descripcion'=> 'para una persona',
            'cant_cama' => 1,
            'precio'=> 120,
            'estado'=>1
        ]);
        TipoHabitacion::create([
            'nombre'=> 'Doble',
            'descripcion'=> 'para dos personas',
            'cant_cama' => 2,
            'precio'=> 180,
            'estado'=>1
        ]);
        TipoHabitacion::create([
            'nombre'=> 'Matrimonial',
            'descripcion'=> 'para pareja',
            'cant_cama' => 1,
            'precio'=> 200, 
            'estado'=>1
        ]);
    }
}
