<?php

namespace App\Observers;

use App\Models\HabitacionReserva;

class HabitacionReservaObserver
{
    public function created(HabitacionReserva $habitacionReserva)
    {
        $habitacionReserva->habitacion?->actualizarDisponibilidad();
    }

    public function updated(HabitacionReserva $habitacionReserva)
    {
        $habitacionReserva->habitacion?->actualizarDisponibilidad();
    }

    public function deleted(HabitacionReserva $habitacionReserva)
    {
        $habitacionReserva->habitacion?->actualizarDisponibilidad();
    }
}
