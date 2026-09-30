<?php

namespace Database\Seeders;

use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Database\Seeder;

class PagoSeeder extends Seeder
{
    /**
     * Comprobantes de ejemplo (storage/app/public/general, expuestos por el enlace
     * simbólico public/storage). Se reparten en orden entre todos los pagos; la ruta
     * tiene el mismo formato que guarda PagoController ("storage/...").
     */
    private const COMPROBANTES = [
        'storage/general/Comprobante.jpg',
        'storage/general/Comprobante2.jpg',
        'storage/general/Comprobante3.jpg',
    ];

    public function run(): void
    {
        $reservas = Reserva::orderBy('id')->get();
        $costos = ReservaSeeder::costos();
        $contador = 0;

        // Los pagos de cada reserva salen de ReservaSeeder::datos() (clave 'pagos'):
        // [offset de días respecto a hoy, % del costo_total, estado (1 completado / 0 cancelado)].
        // El costo_total ya incluye los servicios extras, así que los pagos los cubren.
        // Hay reservas sin pago (pendientes), con pago parcial (saldo pendiente), con pago
        // dividido en cuotas y con intentos cancelados, para poder probar filtros y validaciones.
        foreach (ReservaSeeder::datos() as $i => $data) {
            $reserva = $reservas[$i];
            $costo = $costos[$i];

            // Si los pagos completados suman 100%, el último toma el resto para que la suma
            // sea exactamente el costo_total (sin desfase por redondeo).
            $completados = array_keys(array_filter($data['pagos'], fn ($pago) => $pago[2] === 1));
            $sumaPorcentaje = array_sum(array_map(fn ($k) => $data['pagos'][$k][1], $completados));
            $ultimoCompletado = $sumaPorcentaje === 100 ? end($completados) : null;
            $acumulado = 0;

            foreach ($data['pagos'] as $k => [$offset, $porcentaje, $estado]) {
                $monto = (int) round($costo * $porcentaje / 100);

                if ($estado === 1) {
                    if ($k === $ultimoCompletado) {
                        $monto = $costo - $acumulado;
                    }
                    $acumulado += $monto;
                }

                // Nunca en el futuro: el pago se registra como máximo "ahora"
                $fecha = now()->addDays($offset)->setTime(9 + ($contador % 8), ($contador * 7) % 60);
                if ($fecha->gt(now())) {
                    $fecha = now();
                }

                Pago::create([
                    'fecha' => $fecha,
                    'monto' => $monto,
                    'comprobante' => self::COMPROBANTES[$contador % count(self::COMPROBANTES)],
                    'estado' => $estado,
                    'id_reserva' => $reserva->id,
                    'id_cliente' => $reserva->id_cliente,
                ]);

                $contador++;
            }
        }
    }
}
