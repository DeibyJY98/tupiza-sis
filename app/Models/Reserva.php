<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reserva extends Model
{
    use SoftDeletes;
    
    // Ciclo de vida de la estadía de una reserva activa (estado = 1). No reemplaza
    // a 'estado': una reserva cancelada (estado = 0) conserva el último valor que
    // tenía aquí, mas deja de ser relevante.
    public const ESTADIA_PENDIENTE = 'pendiente';
    public const ESTADIA_CONFIRMADA = 'confirmada';
    public const ESTADIA_CHECK_IN = 'check_in';
    public const ESTADIA_CHECK_OUT = 'check_out';

    protected $fillable = [
        //'identificador',
        'fecha_inicio',
        'fecha_fin',
        'costo_total',
        'estado',
        'estado_estadia',
        'id_cliente',
        'id_trabajador',
    ];

    public function trabajador(){
        return $this->belongsTo(Trabajador::class,'id_trabajador'); 
    }

    public function cliente(){
        return $this->belongsTo(Cliente::class,'id_cliente'); 
    }

    public function habitaciones()
    {
        // wherePivotNull('deleted_at'): habitacion_reservas tiene soft delete (al editar
        // una reserva se borra lógicamente la fila vieja y se crea una nueva). belongsToMany
        // hace un JOIN directo contra la tabla pivote sin respetar ese soft delete por su
        // cuenta, así que sin este filtro las filas "fantasma" duplican la habitación.
        return $this->belongsToMany(Habitacion::class, 'habitacion_reservas', 'id_reserva', 'id_habitacion')
                    ->withPivot('monto')
                    ->wherePivotNull('deleted_at')
                    ->withTimestamps();
    }

    public function habitacionReservas()
    {
        return $this->hasMany(HabitacionReserva::class, 'id_reserva');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_reserva');
    }

    /**
     * Cierra automáticamente las reservas cuya fecha de salida ya pasó (o es hoy y ya
     * pasó la hora de check-out) pero se quedaron en "pendiente"/"confirmada" porque
     * nadie registró el check-in ni el check-out manualmente. Sin esto, la habitación
     * se liberaba igual (Habitacion::actualizarDisponibilidad ya lo hacía por su cuenta)
     * pero la reserva quedaba "pendiente" para siempre, sin rastro de qué pasó. Se
     * marca como check_out y se genera una notificación visible para que el personal
     * verifique si fue un olvido de check-out o un no-show.
     *
     * No toca reservas con estado_estadia = check_in: esas siguen en curso hasta que
     * alguien registre el check-out explícito (ver Habitacion::actualizarDisponibilidad).
     */
    public static function autoCheckoutVencidos(): int
    {
        $hoy = now()->toDateString();
        $yaPasoElCheckOut = now()->format('H:i') >= Habitacion::HORA_CHECK_OUT;

        $reservasVencidas = static::where('estado', 1)
            ->whereIn('estado_estadia', [self::ESTADIA_PENDIENTE, self::ESTADIA_CONFIRMADA])
            ->where(function ($query) use ($hoy, $yaPasoElCheckOut) {
                $query->whereDate('fecha_fin', '<', $hoy);
                if ($yaPasoElCheckOut) {
                    $query->orWhereDate('fecha_fin', $hoy);
                }
            })
            ->with(['cliente.persona', 'habitaciones'])
            ->get();

        foreach ($reservasVencidas as $reserva) {
            $reserva->update(['estado_estadia' => self::ESTADIA_CHECK_OUT]);

            $persona = optional($reserva->cliente)->persona;
            $nombreCliente = trim(($persona->nombre ?? '') . ' ' . ($persona->apellido ?? '')) ?: 'Cliente';

            foreach ($reserva->habitaciones as $habitacion) {
                Notificacion::create([
                    'tipo' => 'auto_checkout',
                    'mensaje' => "Check-out automático: la reserva #{$reserva->id} de {$nombreCliente} (Habitación {$habitacion->numero_habitacion}) se cerró sola porque la fecha de salida ya pasó sin que se registrara el check-out. Verifica si el huésped realmente se retiró.",
                    'id_reserva' => $reserva->id,
                    'id_habitacion' => $habitacion->id,
                    'leida' => false,
                ]);
            }
        }

        return $reservasVencidas->count();
    }

    public function toShow(){
        return[
            'id'    => $this->id,
            //'identificador' => $this->identificador,
            'fecha_inicio'=> date('Y-m-d', strtotime($this->fecha_inicio)),
            'fecha_fin'=> date('Y-m-d', strtotime($this->fecha_fin)),
            'costo_total' => $this->costo_total,
            'estado' => $this->estado,
            'estado_estadia' => $this->estado_estadia,

            // Cliente con datos de persona asociada
            'cliente' => $this->cliente ? [
                'id'       => $this->cliente->id,
                'nombre'   => optional($this->cliente->persona)->nombre,
                'apellido' => optional($this->cliente->persona)->apellido,
            ] : null,

            // Trabajador con datos de persona asociada
            'trabajador' => $this->trabajador ? [
                'id'       => $this->trabajador->id,
                'nombre'   => optional($this->trabajador->persona)->nombre,
                'apellido' => optional($this->trabajador->persona)->apellido,
            ] : null,

            // Habitaciones asociadas
            'habitaciones' => $this->habitaciones->map(function ($habitacion) {
                return [
                    'id'                => $habitacion->id,
                    'numero_habitacion' => $habitacion->numero_habitacion,
                    'planta'            => $habitacion->planta,
                    'monto'             => $habitacion->pivot->monto,
                ];
            }),

            // Servicios extras asociados a la(s) habitación(es) de esta reserva
            'servicios_extra' => $this->habitacionReservas->flatMap(function (HabitacionReserva $habitacionReserva) {
                return $habitacionReserva->serviciosExtras->map(function (ServicioExtra $servicioExtra) {
                    return [
                        'id' => $servicioExtra->id,
                        'nombre' => $servicioExtra->nombre,
                        'precio' => $servicioExtra->precio,
                    ];
                });
            })->values(),

            // Pagos relacionados
            'pagos' => $this->pagos->map(function ($pago) {
                return [
                    'id'         => $pago->id,
                    'fecha'      => $pago->fecha,
                    'monto'      => $pago->monto,
                    'comprobante'=> $pago->comprobante,
                    'estado'     => $pago->estado,
                ];
            }),
        ];
    }

}
