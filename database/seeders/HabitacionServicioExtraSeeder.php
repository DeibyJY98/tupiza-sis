<?php

namespace Database\Seeders;

use App\Models\HabitacionReserva;
use App\Models\HabitacionServicioExtra;
use App\Models\ServicioExtra;
use Illuminate\Database\Seeder;

class HabitacionServicioExtraSeeder extends Seeder
{
    public function run(): void
    {
        // Los índices de HabitacionReserva coinciden con el orden de ReservaSeeder::datos().
        // Los de ServicioExtra: 0 Desayuno, 1 Almuerzo, 2 Cena, 3 Lavandería, 4 Transporte, 5 City tour.
        $habitacionReservas = HabitacionReserva::orderBy('id')->get();
        $servicios = ServicioExtra::orderBy('id')->get();

        $mapa = [
            0 => [0],
            1 => [0, 4],
            4 => [0],
            5 => [0, 1],
            7 => [2],
            9 => [0, 2],
            10 => [3],
            11 => [0],
            12 => [0, 1, 2],
            14 => [0, 4],
            16 => [0],
        ];

        foreach ($mapa as $habitacionReservaIdx => $servicioIdxs) {
            foreach ($servicioIdxs as $servicioIdx) {
                HabitacionServicioExtra::create([
                    'id_habitacion_reserva' => $habitacionReservas[$habitacionReservaIdx]->id,
                    'id_servicio_extra' => $servicios[$servicioIdx]->id,
                ]);
            }
        }
    }
}
