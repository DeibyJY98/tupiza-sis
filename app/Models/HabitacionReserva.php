<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HabitacionReserva extends Model
{
    use SoftDeletes;
    
    protected $table = 'habitacion_reservas';

    protected $fillable = [
        'monto',
        'id_reserva',
        'id_habitacion',
    ];

    public function habitacion()
    {
        return $this->belongsTo(Habitacion::class, 'id_habitacion');
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }

    public function serviciosExtras()
    {
        return $this->belongsToMany(ServicioExtra::class, 'habitacion_servicio_extras', 'id_habitacion_reserva', 'id_servicio_extra')
                    ->withTimestamps()
                    ->withPivot(['created_at', 'updated_at', 'deleted_at']);
    }

    protected static function boot()
    {
        parent::boot();

        // Verificar el estado de la habitación antes de eliminar
        static::deleting(function($habitacionReserva) {
            $habitacion = $habitacionReserva->habitacion;
            
            // Si no quedarán más reservas activas, marcar como disponible
            if ($habitacion && !$habitacion->habitacionReservas()
                ->where('id', '!=', $habitacionReserva->id)
                ->whereHas('reserva', function($query) {
                    $query->where('estado', 1);
                })->exists()) {
                $habitacion->update(['estado' => 1]);
            }
        });
    }
}
