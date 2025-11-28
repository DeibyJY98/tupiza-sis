<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurarse de proporcionar el campo `estado` que la migración requiere
        Rol::create(['nombre' => 'administrador', 'estado' => 1]);
        Rol::create(['nombre' => 'recepcionista', 'estado' => 1]);
        Rol::create(['nombre' => 'cliente', 'estado' => 0]);
        Rol::create(['nombre' => 'mantenimiento', 'estado' => 1]);
    }
}
