<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HabitacionServicioExtra extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'id_habitacion_reserva',
        'id_servicio_extra',
    ];
}
