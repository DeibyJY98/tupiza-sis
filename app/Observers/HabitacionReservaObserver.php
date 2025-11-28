<?php

namespace App\Observers;

use App\Models\HabitacionReserva;

class HabitacionReservaObserver
{
    public function created(HabitacionReserva $habitacionReserva)
    {
        $this->actualizarEstadoHabitacion($habitacionReserva);
    }

    public function updated(HabitacionReserva $habitacionReserva)
    {
        $this->actualizarEstadoHabitacion($habitacionReserva);
    }

    public function deleted(HabitacionReserva $habitacionReserva)
    {
        // Cuando se elimina una reserva, verificamos si hay otras reservas activas
        $habitacion = $habitacionReserva->habitacion;
        
        // Si no hay otras reservas activas, marcamos la habitación como disponible
        if (!$habitacion->habitacionReservas()->where('estado', 1)->exists()) {
            $habitacion->update(['estado' => 1]); // 1 = disponible
        }
    }

    private function actualizarEstadoHabitacion(HabitacionReserva $habitacionReserva)
    {
        $habitacion = $habitacionReserva->habitacion;
        
        if ($habitacion) {
            // Si la reserva está activa, la habitación pasa a ocupada
            if ($habitacionReserva->reserva && $habitacionReserva->reserva->estado == 1) {
                $habitacion->update(['estado' => 0]); // 0 = ocupada
            } 
            // Si no hay reservas activas, la habitación está disponible
            elseif (!$habitacion->habitacionReservas()
                ->whereHas('reserva', function($query) {
                    $query->where('estado', 1);
                })->exists()) {
                $habitacion->update(['estado' => 1]); // 1 = disponible
            }
        }
    }
}