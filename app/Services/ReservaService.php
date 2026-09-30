<?php

namespace App\Services;

use App\Exceptions\ReservaException;
use App\Models\Habitacion;
use App\Models\HabitacionReserva;
use App\Models\ServicioExtra;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservaService
{
    /**
     * Valida que la habitación esté libre en las noches de [fechaInicio, fechaFin).
     *
     * El solapamiento se verifica por noches
     * (existente.inicio < nueva.fin AND existente.fin > nueva.inicio), y la
     * comprobación corre dentro de una transacción con bloqueo pesimista
     * (lockForUpdate) sobre la fila de la habitación: dos peticiones que validan
     * la misma habitación al mismo tiempo no pueden pasar la validación a la vez.
     *
     * IMPORTANTE: para que el bloqueo sea efectivo, quien llama a este método debe
     * hacerlo DENTRO de su propia DB::transaction y crear la reserva en esa misma
     * transacción. La transacción de aquí se anida como savepoint y el bloqueo se
     * mantiene hasta el commit externo.
     *
     * $validarFechaInicioPasada controla si se rechaza un fecha_inicio anterior a
     * hoy. Debe ir en true al crear (una reserva nueva nunca empieza en el
     * pasado) pero en false al editar una reserva en curso cuando fecha_inicio no
     * cambió: de lo contrario, extender el fecha_fin de una estadía cuya llegada
     * ya pasó se rechazaría por error.
     */
    public function validarDisponibilidadHabitacion($idHabitacion, $fechaInicio, $fechaFin, $idReservaExcluir = null, bool $validarFechaInicioPasada = true)
    {
        $fechaInicio = Carbon::parse($fechaInicio);
        $fechaFin = Carbon::parse($fechaFin);
        $hoy = Carbon::today();

        // Validar que la fecha de inicio no sea anterior a hoy (solo quien llama
        // decide si aplica: ver doc del parámetro más arriba)
        if ($validarFechaInicioPasada && $fechaInicio->lt($hoy)) {
            throw new ReservaException('No se pueden realizar reservas en fechas pasadas.');
        }

        // Validar que la fecha de fin no sea anterior a la fecha de inicio
        if ($fechaFin->lt($fechaInicio)) {
            throw new ReservaException('La fecha de fin no puede ser anterior a la fecha de inicio.');
        }

        DB::transaction(function () use ($idHabitacion, $fechaInicio, $fechaFin, $idReservaExcluir) {
            // Bloquea la fila de la habitación hasta el commit externo: si otra
            // petición valida la misma habitación en paralelo, espera aquí.
            Habitacion::whereKey($idHabitacion)->lockForUpdate()->firstOrFail();

            $solapa = HabitacionReserva::where('id_habitacion', $idHabitacion)
                ->whereHas('reserva', fn ($query) => $this->aplicarFiltroSolapamiento($query, $fechaInicio, $fechaFin, $idReservaExcluir))
                ->exists();

            if ($solapa) {
                throw new ReservaException('La habitación no está disponible para las fechas seleccionadas.');
            }
        });

        return true;
    }

    /**
     * Condición de solapamiento por NOCHES (el día de salida no cuenta: se sale
     * a las 11:00-12:00 y el siguiente huésped ingresa desde las 14:00, así que
     * una reserva puede empezar el mismo día en que otra termina; coincide con
     * el calendario del formulario y con el cobro por noches), reutilizada
     * por validarDisponibilidadHabitacion() y habitacionesDisponiblesEntre() para
     * no mantener la misma lógica duplicada en dos lugares (P2.1).
     */
    private function aplicarFiltroSolapamiento($query, Carbon $fechaInicio, Carbon $fechaFin, $idReservaExcluir = null): void
    {
        $query->where('estado', 1) // Solo reservas activas
            ->whereDate('fecha_inicio', '<', $fechaFin->toDateString())
            ->whereDate('fecha_fin', '>', $fechaInicio->toDateString());

        if ($idReservaExcluir) {
            $query->where('id', '!=', $idReservaExcluir);
        }
    }

    /**
     * P2.1: habitaciones sin ninguna reserva activa que se solape con el rango
     * [fechaInicio, fechaFin]. A diferencia de Habitacion.estado (que refleja si
     * está ocupada HOY), esto responde "libre para ESTE rango", que puede ser en
     * el futuro.
     */
    public function habitacionesDisponiblesEntre($fechaInicio, $fechaFin, $idReservaExcluir = null)
    {
        $fechaInicio = Carbon::parse($fechaInicio);
        $fechaFin = Carbon::parse($fechaFin);

        return Habitacion::with('tipoHabitacion')
            ->whereDoesntHave('habitacionReservas', function ($query) use ($fechaInicio, $fechaFin, $idReservaExcluir) {
                $query->whereHas('reserva', fn ($q) => $this->aplicarFiltroSolapamiento($q, $fechaInicio, $fechaFin, $idReservaExcluir));
            })
            ->get();
    }

    /**
     * Recalcula el costo total en el SERVIDOR a partir de los datos reales de la
     * base de datos: precio del tipo de habitación x noches + suma de los servicios
     * extras activos seleccionados. Usa la misma fórmula que el formulario (noches
     * entre ingreso y salida) para que el valor que ve el recepcionista coincida
     * con el que se guarda. El costo que envíe el navegador se ignora: es un dato
     * manipulable (ver P0.2 de md/GUIA-MEJORAS.md).
     */
    public function calcularCostoTotal($idHabitacion, $fechaInicio, $fechaFin, array $idsServiciosExtra = []): float
    {
        $habitacion = Habitacion::with('tipoHabitacion')->findOrFail($idHabitacion);
        $precioBase = (float) ($habitacion->tipoHabitacion->precio ?? 0);

        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->startOfDay();

        // Noches de hospedaje (ingreso 14:00-15:00, salida 11:00-12:00): el día de
        // salida no se cobra. Mínimo 1 noche (misma fórmula del JS).
        $dias = max(1, (int) $inicio->diffInDays($fin));

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