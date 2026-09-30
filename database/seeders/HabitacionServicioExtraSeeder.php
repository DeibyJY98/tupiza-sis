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
        // Los índices de HabitacionReserva coinciden con el orden de ReservaSeeder::datos(),
        // que también define los servicios extras de cada reserva (clave 'servicios'), los
        // mismos que ya están sumados en su costo_total.
        $habitacionReservas = HabitacionReserva::orderBy('id')->get();
        $servicios = ServicioExtra::orderBy('id')->get();

        foreach (ReservaSeeder::datos() as $i => $data) {
            foreach ($data['servicios'] as $servicioIdx) {
                HabitacionServicioExtra::create([
                    'id_habitacion_reserva' => $habitacionReservas[$i]->id,
                    'id_servicio_extra' => $servicios[$servicioIdx]->id,
                ]);
            }
        }
    }
}
