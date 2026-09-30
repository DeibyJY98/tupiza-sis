<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Habitacion;
use App\Models\Reserva;
use App\Models\ServicioExtra;
use App\Models\Trabajador;
use Illuminate\Database\Seeder;

class ReservaSeeder extends Seeder
{
    /**
     * Fuente única de la demo: HabitacionReservaSeeder, HabitacionServicioExtraSeeder y
     * PagoSeeder leen esta misma lista (mismo orden = mismo índice), así que reservas,
     * habitaciones, servicios extras y pagos siempre son coherentes entre sí.
     *
     * Los offsets de fecha son relativos a "hoy" para que la demo siempre muestre una
     * mezcla de estadías pasadas, en curso, que llegan/salen hoy y futuras sin importar
     * cuándo se ejecute el seeder.
     *
     * Claves:
     *  - habitacion / cliente / trabajador: índice 0-based según el orden de creación de su seeder.
     *  - inicio / fin: días respecto a hoy. Se cobra por NOCHES (fin - inicio): el día de salida
     *    no cuenta, por eso una reserva puede empezar el mismo día en que otra termina.
     *  - estado: 1 activa / 0 cancelada.
     *  - servicios: índices de ServicioExtra (0 Desayuno, 1 Almuerzo, 2 Cena, 3 Lavandería,
     *    4 Transporte; el 5, City tour, está inactivo y no se usa).
     *  - estadia: estado_estadia coherente con las fechas y los pagos (pendiente / confirmada
     *    cuando los pagos completados cubren el costo / check_in en curso / check_out terminada).
     *  - pagos: [offset de días, % del costo_total, estado (1 completado / 0 cancelado)].
     *    Los completados nunca superan el 100%; si suman menos queda saldo pendiente.
     *
     * El costo_total NO se escribe a mano: se calcula con la misma fórmula del sistema
     * (noches x precio del tipo de habitación + servicios extras), ver costos().
     */
    public static function datos(): array
    {
        $p = 'pendiente';
        $c = 'confirmada';
        $in = 'check_in';
        $out = 'check_out';

        return [
            // 0-2: habitación 101
            ['habitacion' => 0, 'inicio' => -10, 'fin' => -7, 'estado' => 1, 'cliente' => 0, 'trabajador' => 1, 'servicios' => [0], 'estadia' => $out, 'pagos' => [[-12, 100, 0], [-11, 50, 1], [-10, 50, 1]]],
            ['habitacion' => 0, 'inicio' => 5, 'fin' => 8, 'estado' => 1, 'cliente' => 3, 'trabajador' => 1, 'servicios' => [0, 4], 'estadia' => $p, 'pagos' => [[-1, 40, 1]]],
            ['habitacion' => 0, 'inicio' => 12, 'fin' => 15, 'estado' => 1, 'cliente' => 4, 'trabajador' => 3, 'servicios' => [], 'estadia' => $p, 'pagos' => []],
            // 3-4: habitación 102 (sale hoy y ese mismo día entra otro huésped)
            ['habitacion' => 1, 'inicio' => -5, 'fin' => 0, 'estado' => 1, 'cliente' => 1, 'trabajador' => 3, 'servicios' => [0, 4], 'estadia' => $in, 'pagos' => [[-5, 100, 1]]],
            ['habitacion' => 1, 'inicio' => 0, 'fin' => 3, 'estado' => 1, 'cliente' => 6, 'trabajador' => 1, 'servicios' => [0, 1], 'estadia' => $c, 'pagos' => [[-2, 100, 1]]],
            // 5-7: habitación 103
            ['habitacion' => 2, 'inicio' => -15, 'fin' => -12, 'estado' => 0, 'cliente' => 2, 'trabajador' => 1, 'servicios' => [], 'estadia' => $p, 'pagos' => [[-15, 100, 0]]],
            ['habitacion' => 2, 'inicio' => -6, 'fin' => -3, 'estado' => 1, 'cliente' => 5, 'trabajador' => 3, 'servicios' => [2], 'estadia' => $out, 'pagos' => [[-7, 60, 1], [-6, 40, 1]]],
            ['habitacion' => 2, 'inicio' => 9, 'fin' => 11, 'estado' => 1, 'cliente' => 7, 'trabajador' => 1, 'servicios' => [3], 'estadia' => $p, 'pagos' => [[-2, 50, 1]]],
            // 8-9: habitación 104
            ['habitacion' => 3, 'inicio' => 2, 'fin' => 4, 'estado' => 1, 'cliente' => 4, 'trabajador' => 3, 'servicios' => [0], 'estadia' => $c, 'pagos' => [[-1, 100, 1]]],
            ['habitacion' => 3, 'inicio' => -9, 'fin' => -6, 'estado' => 1, 'cliente' => 8, 'trabajador' => 1, 'servicios' => [0, 1, 2], 'estadia' => $out, 'pagos' => [[-10, 100, 1]]],
            // 10-11: habitación 201
            ['habitacion' => 4, 'inicio' => -8, 'fin' => -4, 'estado' => 1, 'cliente' => 5, 'trabajador' => 1, 'servicios' => [0, 1], 'estadia' => $out, 'pagos' => [[-9, 100, 0], [-8, 100, 1]]],
            ['habitacion' => 4, 'inicio' => 1, 'fin' => 3, 'estado' => 1, 'cliente' => 9, 'trabajador' => 3, 'servicios' => [4], 'estadia' => $c, 'pagos' => [[-3, 50, 1], [-1, 50, 1]]],
            // 12-14: habitación 202
            ['habitacion' => 5, 'inicio' => -25, 'fin' => -22, 'estado' => 0, 'cliente' => 5, 'trabajador' => 1, 'servicios' => [], 'estadia' => $p, 'pagos' => [[-26, 100, 0]]],
            ['habitacion' => 5, 'inicio' => -2, 'fin' => 1, 'estado' => 1, 'cliente' => 10, 'trabajador' => 1, 'servicios' => [0, 2], 'estadia' => $in, 'pagos' => [[-3, 100, 1]]],
            ['habitacion' => 5, 'inicio' => 10, 'fin' => 14, 'estado' => 1, 'cliente' => 6, 'trabajador' => 3, 'servicios' => [0], 'estadia' => $p, 'pagos' => []],
            // 15: habitación 203
            ['habitacion' => 6, 'inicio' => -3, 'fin' => 0, 'estado' => 1, 'cliente' => 7, 'trabajador' => 1, 'servicios' => [2], 'estadia' => $in, 'pagos' => [[-3, 100, 1]]],
            // 16-18: habitación 204
            ['habitacion' => 7, 'inicio' => -20, 'fin' => -18, 'estado' => 0, 'cliente' => 8, 'trabajador' => 3, 'servicios' => [], 'estadia' => $p, 'pagos' => [[-21, 100, 0]]],
            ['habitacion' => 7, 'inicio' => -4, 'fin' => -1, 'estado' => 1, 'cliente' => 11, 'trabajador' => 1, 'servicios' => [0, 3], 'estadia' => $out, 'pagos' => [[-5, 100, 1]]],
            ['habitacion' => 7, 'inicio' => 3, 'fin' => 6, 'estado' => 1, 'cliente' => 0, 'trabajador' => 3, 'servicios' => [], 'estadia' => $p, 'pagos' => []],
            // 19-20: habitación 301
            ['habitacion' => 8, 'inicio' => 0, 'fin' => 3, 'estado' => 1, 'cliente' => 9, 'trabajador' => 1, 'servicios' => [0, 2], 'estadia' => $c, 'pagos' => [[-4, 100, 1]]],
            ['habitacion' => 8, 'inicio' => -8, 'fin' => -5, 'estado' => 1, 'cliente' => 1, 'trabajador' => 3, 'servicios' => [1], 'estadia' => $in, 'pagos' => [[-9, 50, 1], [-8, 50, 1]]],
            // 21-22: habitación 302
            ['habitacion' => 9, 'inicio' => 7, 'fin' => 10, 'estado' => 1, 'cliente' => 10, 'trabajador' => 3, 'servicios' => [], 'estadia' => $p, 'pagos' => []],
            ['habitacion' => 9, 'inicio' => -4, 'fin' => -2, 'estado' => 1, 'cliente' => 2, 'trabajador' => 1, 'servicios' => [0], 'estadia' => $out, 'pagos' => [[-5, 100, 1]]],
            // 23-25: habitación 303
            ['habitacion' => 10, 'inicio' => -6, 'fin' => -3, 'estado' => 1, 'cliente' => 11, 'trabajador' => 1, 'servicios' => [3], 'estadia' => $out, 'pagos' => [[-6, 100, 1]]],
            ['habitacion' => 10, 'inicio' => 0, 'fin' => 2, 'estado' => 1, 'cliente' => 3, 'trabajador' => 3, 'servicios' => [], 'estadia' => $p, 'pagos' => []],
            ['habitacion' => 10, 'inicio' => 4, 'fin' => 7, 'estado' => 1, 'cliente' => 4, 'trabajador' => 1, 'servicios' => [0, 4], 'estadia' => $c, 'pagos' => [[-2, 100, 1]]],
            // 26-27: habitación 401
            ['habitacion' => 11, 'inicio' => 15, 'fin' => 20, 'estado' => 1, 'cliente' => 0, 'trabajador' => 3, 'servicios' => [0, 1], 'estadia' => $p, 'pagos' => [[-1, 30, 1]]],
            ['habitacion' => 11, 'inicio' => -5, 'fin' => -2, 'estado' => 1, 'cliente' => 6, 'trabajador' => 1, 'servicios' => [4], 'estadia' => $out, 'pagos' => [[-6, 100, 1]]],
            // 28-30: habitación 402
            ['habitacion' => 12, 'inicio' => -12, 'fin' => -9, 'estado' => 0, 'cliente' => 1, 'trabajador' => 1, 'servicios' => [], 'estadia' => $p, 'pagos' => [[-12, 100, 0]]],
            ['habitacion' => 12, 'inicio' => -3, 'fin' => 2, 'estado' => 1, 'cliente' => 8, 'trabajador' => 3, 'servicios' => [0, 1, 2], 'estadia' => $in, 'pagos' => [[-4, 60, 1], [-3, 40, 1]]],
            ['habitacion' => 12, 'inicio' => 6, 'fin' => 9, 'estado' => 1, 'cliente' => 2, 'trabajador' => 1, 'servicios' => [0], 'estadia' => $p, 'pagos' => [[-1, 50, 1]]],
            // 31-32: habitación 501
            ['habitacion' => 13, 'inicio' => -9, 'fin' => -6, 'estado' => 1, 'cliente' => 3, 'trabajador' => 3, 'servicios' => [0], 'estadia' => $out, 'pagos' => [[-10, 100, 1]]],
            ['habitacion' => 13, 'inicio' => 4, 'fin' => 7, 'estado' => 1, 'cliente' => 5, 'trabajador' => 1, 'servicios' => [1], 'estadia' => $c, 'pagos' => [[0, 100, 1]]],
            // 33-34: habitación 502
            ['habitacion' => 14, 'inicio' => -2, 'fin' => 1, 'estado' => 1, 'cliente' => 7, 'trabajador' => 3, 'servicios' => [0, 4], 'estadia' => $in, 'pagos' => [[-2, 100, 1]]],
            ['habitacion' => 14, 'inicio' => 5, 'fin' => 8, 'estado' => 1, 'cliente' => 9, 'trabajador' => 1, 'servicios' => [], 'estadia' => $p, 'pagos' => []],
        ];
    }

    /**
     * costo_total de cada reserva de datos() (mismo índice), con la fórmula del sistema
     * (ReservaService::calcularCostoTotal): noches x precio del tipo de habitación +
     * servicios extras. Requiere que TipoHabitacion, ServicioExtra y Habitacion ya estén sembrados.
     */
    public static function costos(): array
    {
        $habitaciones = Habitacion::with('tipoHabitacion')->orderBy('id')->get();
        $servicios = ServicioExtra::orderBy('id')->get();

        return array_map(function (array $data) use ($habitaciones, $servicios) {
            $noches = max(1, $data['fin'] - $data['inicio']);
            $precioNoche = (float) $habitaciones[$data['habitacion']]->tipoHabitacion->precio;
            $extras = collect($data['servicios'])->sum(fn ($i) => (float) $servicios[$i]->precio);

            return (int) round($noches * $precioNoche + $extras);
        }, self::datos());
    }

    public function run(): void
    {
        $clientes = Cliente::orderBy('id')->get();
        $trabajadores = Trabajador::orderBy('id')->get();
        $costos = self::costos();

        foreach (self::datos() as $i => $data) {
            Reserva::create([
                'fecha_inicio' => now()->addDays($data['inicio'])->toDateString(),
                'fecha_fin' => now()->addDays($data['fin'])->toDateString(),
                'costo_total' => $costos[$i],
                'estado' => $data['estado'],
                'estado_estadia' => $data['estadia'],
                'id_cliente' => $clientes[$data['cliente']]->id,
                'id_trabajador' => $trabajadores[$data['trabajador']]->id,
            ]);
        }
    }
}
