<?php

namespace Database\Seeders;

use App\Models\Habitacion;
use App\Models\HabitacionReserva;
use App\Models\Reserva;
use Illuminate\Database\Seeder;

class HabitacionReservaSeeder extends Seeder
{
    public function run(): void
    {
        $reservas = Reserva::orderBy('id')->get();
        $habitaciones = Habitacion::orderBy('id')->get();
        $datos = ReservaSeeder::datos();

        foreach ($datos as $i => $data) {
            HabitacionReserva::create([
                'id_reserva' => $reservas[$i]->id,
                'id_habitacion' => $habitaciones[$data['habitacion']]->id,
                'monto' => $data['costo_total'],
            ]);
        }
    }
}
