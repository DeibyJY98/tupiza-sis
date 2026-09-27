<?php

namespace App\Services;

use App\Models\Habitacion;
use App\Models\HabitacionReserva;
use App\Models\ServicioExtra;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservaService
{
    /**
     * Valida que la habitación esté libre en el rango [fechaInicio, fechaFin].
     *
     * El solapamiento se verifica con la condición clásica de rangos
     * (existente.inicio <= nueva.fin AND existente.fin >= nueva.inicio), y la
     * comprobación corre dentro de una transacción con bloqueo pesimista
     * (lockForUpdate) sobre la fila de la habitación: dos peticiones que validan
     * la misma habitación al mismo tiempo no pueden pasar la validación a la vez.
     *
     * IMPORTANTE: para que el bloqueo sea efectivo, quien llama a este método debe
     * hacerlo DENTRO de su propia DB::transaction y crear la reserva en esa misma
     * transacción. La transacción de aquí se anida como savepoint y el bloqueo se
     * mantiene hasta el commit externo.
     */
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

        DB::transaction(function () use ($idHabitacion, $fechaInicio, $fechaFin, $idReservaExcluir) {
            // Bloquea la fila de la habitación hasta el commit externo: si otra
            // petición valida la misma habitación en paralelo, espera aquí.
            Habitacion::whereKey($idHabitacion)->lockForUpdate()->firstOrFail();

            $solapa = HabitacionReserva::where('id_habitacion', $idHabitacion)
                ->whereHas('reserva', function ($query) use ($fechaInicio, $fechaFin, $idReservaExcluir) {
                    $query->where('estado', 1) // Solo reservas activas
                        // Solapamiento de rangos (límites inclusive: el día de
                        // salida sigue bloqueado, igual que en el calendario del formulario)
                        ->whereDate('fecha_inicio', '<=', $fechaFin->toDateString())
                        ->whereDate('fecha_fin', '>=', $fechaInicio->toDateString());

                    if ($idReservaExcluir) {
                        $query->where('id', '!=', $idReservaExcluir);
                    }
                })
                ->exists();

            if ($solapa) {
                throw new \Exception('La habitación no está disponible para las fechas seleccionadas.');
            }
        });

        return true;
    }

    /**
     * Recalcula el costo total en el SERVIDOR a partir de los datos reales de la
     * base de datos: precio del tipo de habitación x días + suma de los servicios
     * extras activos seleccionados. Usa la misma fórmula que el formulario (días
     * contando inicio y fin) para que el valor que ve el recepcionista coincida
     * con el que se guarda. El costo que envíe el navegador se ignora: es un dato
     * manipulable (ver P0.2 de md/GUIA-MEJORAS.md).
     */
    public function calcularCostoTotal($idHabitacion, $fechaInicio, $fechaFin, array $idsServiciosExtra = []): float
    {
        $habitacion = Habitacion::with('tipoHabitacion')->findOrFail($idHabitacion);
        $precioBase = (float) ($habitacion->tipoHabitacion->precio ?? 0);

        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->startOfDay();

        // Días contando tanto la llegada como la salida (misma fórmula del JS)
        $dias = (int) $inicio->diffInDays($fin) + 1;

        $totalServicios = 0.0;
        if ($idsServiciosExtra !== []) {
            $totalServicios = (float) ServicioExtra::whereIn('id', $idsServiciosExtra)
                ->where('estado', 1) // solo servicios activos
                ->sum('precio');
        }

        return round($dias * $precioBase + $totalServicios, 2);
    }

    public function liberarHabitacion($idReserva)
    {
        $habitacionReserva = HabitacionReserva::where('id_reserva', $idReserva)->first();

        // Se excluye $idReserva del recálculo porque este método se llama justo antes
        // de eliminar/cancelar esa reserva, mientras su registro todavía existe en BD.
        $habitacionReserva?->habitacion?->actualizarDisponibilidad($idReserva);
    }
}