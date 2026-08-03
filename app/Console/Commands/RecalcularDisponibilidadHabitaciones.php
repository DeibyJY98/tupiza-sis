<?php

namespace App\Console\Commands;

use App\Models\Habitacion;
use App\Models\Notificacion;
use App\Models\Reserva;
use Illuminate\Console\Command;

class RecalcularDisponibilidadHabitaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recalcular-disponibilidad-habitaciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifica los check-outs del día y recalcula qué habitaciones quedan ocupadas/disponibles según las fechas de las reservas.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $notificacionesNuevas = $this->notificarCheckOutsDeHoy();

        $habitaciones = Habitacion::all();
        $habitaciones->each(fn (Habitacion $habitacion) => $habitacion->actualizarDisponibilidad());

        $this->info("Check-outs notificados: {$notificacionesNuevas}. Disponibilidad recalculada para {$habitaciones->count()} habitación(es).");

        return self::SUCCESS;
    }

    /**
     * Crea una notificación por cada habitación que se desocupa hoy (reserva activa
     * cuyo check-out es hoy), evitando duplicar la notificación si el comando ya
     * la generó antes (por ejemplo, si se ejecuta manualmente más de una vez el mismo día).
     */
    private function notificarCheckOutsDeHoy(): int
    {
        // Si el comando se corre manualmente antes de la hora de check-out, no se notifica
        // todavía: el mensaje diría "quedó disponible" cuando la habitación aún sigue ocupada.
        if (now()->format('H:i') < Habitacion::HORA_CHECK_OUT) {
            return 0;
        }

        $hoy = now()->toDateString();

        $reservasFinalizando = Reserva::with(['cliente.persona', 'habitaciones'])
            ->where('estado', 1)
            ->whereDate('fecha_fin', $hoy)
            ->get();

        $creadas = 0;

        foreach ($reservasFinalizando as $reserva) {
            foreach ($reserva->habitaciones as $habitacion) {
                $yaNotificada = Notificacion::where('id_reserva', $reserva->id)
                    ->where('id_habitacion', $habitacion->id)
                    ->where('tipo', 'checkout')
                    ->exists();

                if ($yaNotificada) {
                    continue;
                }

                $persona = optional($reserva->cliente)->persona;
                $nombreCliente = trim(($persona->nombre ?? '') . ' ' . ($persona->apellido ?? '')) ?: 'Cliente';

                Notificacion::create([
                    'tipo' => 'checkout',
                    'mensaje' => "Check-out: la reserva de {$nombreCliente} finalizó hoy. La habitación {$habitacion->numero_habitacion} quedó disponible.",
                    'id_reserva' => $reserva->id,
                    'id_habitacion' => $habitacion->id,
                    'leida' => false,
                ]);

                $creadas++;
            }
        }

        return $creadas;
    }
}
