<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\Trabajador;
use Illuminate\Database\Seeder;

class TrabajadorSeeder extends Seeder
{
    public function run(): void
    {
        // 'persona' es el índice (0-based) dentro del orden de creación de PersonaSeeder.
        $personas = Persona::orderBy('id')->get();

        $trabajadores = [
            ['persona' => 0, 'cargo' => 'Gerente', 'salario' => 5000, 'estado' => 1],
            ['persona' => 1, 'cargo' => 'Recepcionista', 'salario' => 3000, 'estado' => 1],
            ['persona' => 2, 'cargo' => 'Mantenimiento', 'salario' => 2500, 'estado' => 1],
            ['persona' => 6, 'cargo' => 'Recepcionista', 'salario' => 2800, 'estado' => 1],
            ['persona' => 7, 'cargo' => 'Limpieza', 'salario' => 2200, 'estado' => 1],
            ['persona' => 8, 'cargo' => 'Limpieza', 'salario' => 2200, 'estado' => 0],
        ];

        foreach ($trabajadores as $data) {
            Trabajador::create([
                'cargo' => $data['cargo'],
                'salario' => $data['salario'],
                'estado' => $data['estado'],
                'id_persona' => $personas[$data['persona']]->id,
            ]);
        }
    }
}
