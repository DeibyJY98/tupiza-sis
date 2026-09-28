<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'id',
        'fecha',
        'monto',
        'comprobante',
        'estado',
        'id_cliente',
        'id_reserva',
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class,'id_cliente');
    }

        public function reserva(){
        return $this->belongsTo(Reserva::class,'id_reserva');
    }

    public function toShow(){
        return[
            'id'            => $this->id,
            'monto'         => $this->monto,
            'fecha'         => $this->fecha ? \Illuminate\Support\Carbon::parse($this->fecha)->format('Y-m-d') : null,
            'estado'        => $this->estado,
            'comprobante'   => $this->comprobante,
            // Cliente con datos de persona asociada
            'cliente'       => $this->cliente ? [
                'id'        => $this->cliente->id,
                'nombre'    => optional($this->cliente->persona)->nombre,
                'apellido'  => optional($this->cliente->persona)->apellido,
                'cedula'    => optional($this->cliente->persona)->cedula,
            ] : null,
            // Reserva
            'reserva'           => $this->reserva ? [
                'id'             => $this->reserva->id,
                'costo_total'    => $this->reserva->costo_total,
                'fecha_inicio'   => $this->reserva->fecha_inicio ? \Illuminate\Support\Carbon::parse($this->reserva->fecha_inicio)->format('Y-m-d') : null,
                'fecha_fin'      => $this->reserva->fecha_fin ? \Illuminate\Support\Carbon::parse($this->reserva->fecha_fin)->format('Y-m-d') : null,
                'estado_estadia' => $this->reserva->estado_estadia,
                'habitaciones'   => $this->reserva->habitaciones->pluck('numero_habitacion')->implode(', '),
            ] : null,
        ];
    }

}
