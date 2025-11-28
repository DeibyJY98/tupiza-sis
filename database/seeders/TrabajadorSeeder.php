<?php

namespace Database\Seeders;

use App\Models\Trabajador;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrabajadorSeeder extends Seeder
{
    public function run(): void
    {
        Trabajador::create([
            'cargo' => 'Gerente',
            'salario' => 5000,
            'estado' => 1,
            'id_persona' => 1
        ]);
        Trabajador::create([
            'cargo' => 'Recepcionista',
            'salario' => 3000,
            'estado' => 1,
            'id_persona' => 2
        ]);
        Trabajador::create([
            'cargo' => 'Mantenimiento',
            'salario' => 2500,
            'estado' => 1,
            'id_persona' => 3
        ]);
    }
}
