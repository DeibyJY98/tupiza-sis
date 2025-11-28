<?php

namespace Database\Seeders;

use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Database\Seeder;

class PagoSeeder extends Seeder
{
    public function run(): void
    {
        $reservas = Reserva::all();
        
        foreach($reservas as $reserva) {
            Pago::create([
                'monto' => $reserva->costo_total,
                'fecha' => now(),
                'estado' => $reserva->estado,
                'id_reserva' => $reserva->id,
                'id_cliente' => $reserva->id_cliente,
                'comprobante' => 'COMP-' . str_pad($reserva->id, 3, '0', STR_PAD_LEFT)
            ]);
        }
    }
}