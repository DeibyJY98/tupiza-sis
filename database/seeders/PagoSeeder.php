<?php

namespace Database\Seeders;

use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Database\Seeder;

class PagoSeeder extends Seeder
{
    public function run(): void
    {
        $reservas = Reserva::orderBy('id')->get();

        // Índice (0-based, mismo orden que ReservaSeeder::datos()) => lista de pagos
        // [offset de días respecto a hoy, monto, estado (1 completado / 0 cancelado)].
        // Algunas reservas quedan sin pago (pendientes) y otras con pago dividido en dos
        // cuotas, a propósito, para poder demostrar el filtro de estado/fecha y la
        // validación de saldo pendiente de PagoController.
        $pagosPorReserva = [
            0 => [[-11, 360, 0], [-10, 360, 1]], // intento fallido + pago exitoso
            1 => [],
            2 => [[-5, 200, 1], [-3, 160, 1]],
            3 => [[-15, 480, 0]],
            4 => [[0, 240, 1]],
            5 => [[-9, 720, 0], [-8, 720, 1]],
            6 => [],
            7 => [[-3, 400, 1]],
            8 => [],
            9 => [[0, 600, 1]],
            10 => [],
            11 => [[-6, 600, 1]],
            12 => [[0, 800, 1]],
            13 => [[-12, 960, 0]],
            14 => [[1, 840, 1]],
            15 => [],
            16 => [[10, 360, 1]],
            17 => [],
        ];

        foreach ($pagosPorReserva as $reservaIdx => $pagos) {
            $reserva = $reservas[$reservaIdx];

            foreach ($pagos as [$offset, $monto, $estado]) {
                Pago::create([
                    'fecha' => now()->addDays($offset),
                    'monto' => $monto,
                    'comprobante' => 'COMP-' . str_pad($reserva->id, 3, '0', STR_PAD_LEFT) . '-' . ($offset + 100),
                    'estado' => $estado,
                    'id_reserva' => $reserva->id,
                    'id_cliente' => $reserva->id_cliente,
                ]);
            }
        }
    }
}
