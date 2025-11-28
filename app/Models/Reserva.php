<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reserva extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        //'identificador',
        'fecha_inicio',
        'fecha_fin',
        'costo_total',
        'estado',
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
        return $this->belongsToMany(Habitacion::class, 'habitacion_reservas', 'id_reserva', 'id_habitacion')
                    ->withPivot('monto')
                    ->withTimestamps();
    }
    
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_reserva');
    }

    public function toShow(){
        return[
            'id'    => $this->id,
            //'identificador' => $this->identificador,
            'fecha_inicio'=> date('Y-m-d', strtotime($this->fecha_inicio)),
            'fecha_fin'=> date('Y-m-d', strtotime($this->fecha_fin)),
            'costo_total' => $this->costo_total,
            'estado' => $this->estado,

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
