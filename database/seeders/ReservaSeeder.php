<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Reserva;
use App\Models\Trabajador;
use Illuminate\Database\Seeder;

class ReservaSeeder extends Seeder
{
    /**
     * Lista de reservas a sembrar. Los offsets de fecha son relativos a "hoy" para que la
     * demo siempre muestre una mezcla de estadías pasadas, en curso y futuras sin importar
     * cuándo se ejecute el seeder. 'habitacion' (índice 0-based) se usa en HabitacionReservaSeeder
     * para no solapar fechas activas sobre la misma habitación.
     */
    public static function datos(): array
    {
        return [
            // habitacion, inicio, fin, estado, cliente, trabajador, costo_total
            ['habitacion' => 0, 'inicio' => -10, 'fin' => -7, 'estado' => 1, 'cliente' => 0, 'trabajador' => 1, 'costo_total' => 360],
            ['habitacion' => 0, 'inicio' => 5, 'fin' => 8, 'estado' => 1, 'cliente' => 3, 'trabajador' => 1, 'costo_total' => 360],
            ['habitacion' => 1, 'inicio' => -5, 'fin' => -2, 'estado' => 1, 'cliente' => 1, 'trabajador' => 3, 'costo_total' => 360],
            ['habitacion' => 2, 'inicio' => -15, 'fin' => -12, 'estado' => 0, 'cliente' => 2, 'trabajador' => 1, 'costo_total' => 480],
            ['habitacion' => 3, 'inicio' => 2, 'fin' => 4, 'estado' => 1, 'cliente' => 4, 'trabajador' => 3, 'costo_total' => 240],
            ['habitacion' => 4, 'inicio' => -8, 'fin' => -4, 'estado' => 1, 'cliente' => 5, 'trabajador' => 1, 'costo_total' => 720],
            ['habitacion' => 5, 'inicio' => 10, 'fin' => 14, 'estado' => 1, 'cliente' => 6, 'trabajador' => 3, 'costo_total' => 720],
            ['habitacion' => 6, 'inicio' => -3, 'fin' => 0, 'estado' => 1, 'cliente' => 7, 'trabajador' => 1, 'costo_total' => 720],
            ['habitacion' => 7, 'inicio' => -20, 'fin' => -18, 'estado' => 0, 'cliente' => 8, 'trabajador' => 3, 'costo_total' => 360],
            ['habitacion' => 8, 'inicio' => 0, 'fin' => 3, 'estado' => 1, 'cliente' => 9, 'trabajador' => 1, 'costo_total' => 600],
            ['habitacion' => 9, 'inicio' => 7, 'fin' => 10, 'estado' => 1, 'cliente' => 10, 'trabajador' => 3, 'costo_total' => 600],
            ['habitacion' => 10, 'inicio' => -6, 'fin' => -3, 'estado' => 1, 'cliente' => 11, 'trabajador' => 1, 'costo_total' => 600],
            ['habitacion' => 11, 'inicio' => 15, 'fin' => 20, 'estado' => 1, 'cliente' => 0, 'trabajador' => 3, 'costo_total' => 1600],
            ['habitacion' => 12, 'inicio' => -12, 'fin' => -9, 'estado' => 0, 'cliente' => 1, 'trabajador' => 1, 'costo_total' => 960],
            ['habitacion' => 13, 'inicio' => 3, 'fin' => 6, 'estado' => 1, 'cliente' => 2, 'trabajador' => 3, 'costo_total' => 840],
            ['habitacion' => 14, 'inicio' => -2, 'fin' => 1, 'estado' => 1, 'cliente' => 3, 'trabajador' => 1, 'costo_total' => 840],
            ['habitacion' => 0, 'inicio' => 12, 'fin' => 15, 'estado' => 1, 'cliente' => 4, 'trabajador' => 3, 'costo_total' => 360],
            ['habitacion' => 5, 'inicio' => -25, 'fin' => -22, 'estado' => 0, 'cliente' => 5, 'trabajador' => 1, 'costo_total' => 720],
        ];
    }

    public function run(): void
    {
        $clientes = Cliente::orderBy('id')->get();
        $trabajadores = Trabajador::orderBy('id')->get();

        foreach (self::datos() as $data) {
            Reserva::create([
                'fecha_inicio' => now()->addDays($data['inicio'])->toDateString(),
                'fecha_fin' => now()->addDays($data['fin'])->toDateString(),
                'costo_total' => $data['costo_total'],
                'estado' => $data['estado'],
                'id_cliente' => $clientes[$data['cliente']]->id,
                'id_trabajador' => $trabajadores[$data['trabajador']]->id,
            ]);
        }
    }
}
