<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Habitacion extends Model
{
    use SoftDeletes;

    /** Hora oficial de check-out (formato H:i). A partir de esta hora, una reserva
     *  cuyo fecha_fin sea hoy deja de considerarse ocupante de la habitación. */
    public const HORA_CHECK_OUT = '11:00';

    protected $fillable = [
        'numero_habitacion',
        'planta',
        'estado',
        'id_tipo_habitacion',
    ];

    public function tipoHabitacion()
    {
        return $this->belongsTo(TipoHabitacion::class, 'id_tipo_habitacion');
    }

    public function reservas()
    {
        return $this->belongsToMany(Reserva::class, 'habitacion_reservas', 'id_habitacion', 'id_reserva')
                    ->withPivot('monto')
                    ->withTimestamps();
    }

    public function habitacionReservas()
    {
        return $this->hasMany(HabitacionReserva::class, 'id_habitacion');
    }

    // Los servicios extras se acceden a través de habitacion_reservas
    public function serviciosExtras()
    {
        return $this->hasManyThrough(
            ServicioExtra::class,
            HabitacionReserva::class,
            'id_habitacion', // Foreign key en habitacion_reservas
            'id_habitacion_reserva', // Foreign key en habitacion_servicio_extras
            'id', // Local key en habitacions
            'id' // Local key en habitacion_reservas
        )->withPivot(['created_at', 'updated_at', 'deleted_at']);
    }

    /**
     * Recalcula si la habitación está ocupada HOY: solo cuenta como ocupante una
     * reserva activa (estado 1) cuyo rango [fecha_inicio, fecha_fin] incluya la
     * fecha actual. Reservas pasadas, futuras o canceladas no la bloquean.
     *
     * Si el check-out (fecha_fin) de una reserva es justamente hoy, esa reserva
     * deja de contar como ocupante a partir de HORA_CHECK_OUT (antes de esa hora
     * sigue ocupando, ya que el huésped todavía no se retiró).
     *
     * $idReservaExcluir permite recalcular "como si" una reserva puntual ya no
     * existiera, útil cuando se llama justo antes de eliminarla/cancelarla.
     */
    public function actualizarDisponibilidad(?int $idReservaExcluir = null): void
    {
        $hoy = now()->toDateString();
        $yaPasoElCheckOut = now()->format('H:i') >= self::HORA_CHECK_OUT;

        $reservasQueCubrenHoy = $this->habitacionReservas()
            ->when($idReservaExcluir, fn ($query) => $query->where('id_reserva', '!=', $idReservaExcluir))
            ->whereHas('reserva', function ($query) use ($hoy) {
                $query->where('estado', 1)
                    ->whereDate('fecha_inicio', '<=', $hoy)
                    ->whereDate('fecha_fin', '>=', $hoy);
            })
            ->with('reserva')
            ->get();

        $ocupadaHoy = $reservasQueCubrenHoy->contains(function (HabitacionReserva $habitacionReserva) use ($hoy, $yaPasoElCheckOut) {
            $esCheckOutHoy = \Illuminate\Support\Carbon::parse($habitacionReserva->reserva->fecha_fin)->toDateString() === $hoy;

            return !($esCheckOutHoy && $yaPasoElCheckOut);
        });

        $nuevoEstado = $ocupadaHoy ? 0 : 1;

        // Se actualiza vía query builder (no $this->update()) porque $this puede estar
        // desactualizado en memoria: por ejemplo, el observer de HabitacionReserva llama a
        // este método sobre OTRA instancia de este mismo registro. Si $this->estado ya
        // coincide (en memoria) con $nuevoEstado, Eloquent detectaría "sin cambios" y
        // omitiría el UPDATE, dejando la fila real desincronizada.
        static::whereKey($this->getKey())->update(['estado' => $nuevoEstado]);
        $this->setAttribute('estado', $nuevoEstado)->syncOriginalAttribute('estado');
    }

    public function toShow()
    {
        return [
            'id' => $this->id,
            'numero_habitacion' => $this->numero_habitacion,
            'planta' => $this->planta,
            'estado' => $this->estado,
            'tipo_habitacion' => $this->tipoHabitacion ? [
                'id' => $this->tipoHabitacion->id,
                'nombre' => $this->tipoHabitacion->nombre,
                'descripcion' => $this->tipoHabitacion->descripcion,
                'precio' => $this->tipoHabitacion->precio
            ] : null,
            'reservas' => $this->habitacionReservas->map(function($reserva) {
                return [
                    'id' => $reserva->id,
                    'monto' => $reserva->monto,
                    'servicios_extras' => collect($reserva->serviciosExtras)->map(function($servicio) {
                        return [
                            'id' => $servicio->id,
                            'nombre' => $servicio->nombre,
                            'descripcion' => $servicio->descripcion,
                            'precio' => $servicio->precio
                        ];
                    })->all()
                ];
            })
        ];
    }
}
