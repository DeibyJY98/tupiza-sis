<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Trabajador;
use App\Models\Reserva;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReservaSeeder extends Seeder
{
    public function run(): void
    {
        //$cliente = \App\Models\Cliente::first();
        //$trabajador = \App\Models\Trabajador::first();
        
        /*if (!$cliente || !$trabajador) {
            throw new \Exception('Necesitas tener al menos un cliente y un trabajador en la base de datos');
        }*/

        Reserva::create([
            //'identificador' => 'RES-001',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(3)->toDateString(),
            'costo_total' => 60,
            'estado' => 1,
            'id_cliente' => 1,
            'id_trabajador' => 2,
        ]);

        Reserva::create([
            //'identificador' => 'RES-002',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(5)->toDateString(),
            'costo_total' => 90,
            'estado' => 1,
            'id_cliente' => 2,
            'id_trabajador' => 2,
        ]);

        Reserva::create([
            //'identificador' => 'RES-003',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(7)->toDateString(),
            'costo_total' => 100,
            'estado' => 0,
            'id_cliente' => 3,
            'id_trabajador' => 2,
        ]);
    }
}
