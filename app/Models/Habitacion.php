<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Habitacion extends Model
{
    use SoftDeletes;

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
