<?php

namespace App\Services;

use App\Models\HabitacionReserva;
use Carbon\Carbon;

class ReservaService
{
    public function validarDisponibilidadHabitacion($idHabitacion, $fechaInicio, $fechaFin, $idReservaExcluir = null)
    {
        $fechaInicio = Carbon::parse($fechaInicio);
        $fechaFin = Carbon::parse($fechaFin);
        $hoy = Carbon::today();

        // Validar que la fecha de inicio no sea anterior a hoy
        if ($fechaInicio->lt($hoy)) {
            throw new \Exception('No se pueden realizar reservas en fechas pasadas.');
        }

        // Validar que la fecha de fin no sea anterior a la fecha de inicio
        if ($fechaFin->lt($fechaInicio)) {
            throw new \Exception('La fecha de fin no puede ser anterior a la fecha de inicio.');
        }

        // Buscar reservas que se solapan con las fechas proporcionadas
        $reservasExistentes = HabitacionReserva::whereHas('reserva', function ($query) use ($fechaInicio, $fechaFin, $idReservaExcluir) {
            $query->where('estado', 1) // Solo reservas activas
                ->where(function ($q) use ($fechaInicio, $fechaFin) {
                    // Verifica si hay solapamiento de fechas
                    $q->where(function ($q) use ($fechaInicio, $fechaFin) {
                        $q->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                          ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
                          ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                              $q->where('fecha_inicio', '<=', $fechaInicio)
                                ->where('fecha_fin', '>=', $fechaFin);
                          });
                    });
                });
            
            if ($idReservaExcluir) {
                $query->where('id', '!=', $idReservaExcluir);
            }
        })
        ->where('id_habitacion', $idHabitacion)
        ->exists();

        if ($reservasExistentes) {
            throw new \Exception('La habitación no está disponible para las fechas seleccionadas.');
        }

        return true;
    }

    public function liberarHabitacion($idReserva)
    {
        $habitacionReserva = HabitacionReserva::where('id_reserva', $idReserva)->first();

        // Se excluye $idReserva del recálculo porque este método se llama justo antes
        // de eliminar/cancelar esa reserva, mientras su registro todavía existe en BD.
        $habitacionReserva?->habitacion?->actualizarDisponibilidad($idReserva);
    }
}