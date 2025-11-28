<?php

namespace Database\Seeders;

use App\Models\Permiso;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisoSeeder extends Seeder
{
    public function run(): void
    {
        Permiso::create(['nombre' =>'permiso']);
        Permiso::create(['nombre' =>'rol']);
        Permiso::create(['nombre' =>'user']);
        Permiso::create(['nombre' =>'persona']);
        Permiso::create(['nombre' =>'trabajador']);
        Permiso::create(['nombre' =>'cliente']);
        Permiso::create(['nombre' =>'reserva']);
        Permiso::create(['nombre' =>'habitacion']);
        Permiso::create(['nombre' =>'tipo_habitacion']);
        Permiso::create(['nombre' =>'servicio_extra']);
        Permiso::create(['nombre' =>'caracteristica']);
        Permiso::create(['nombre' =>'pago']);
    }
}
