<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoHabitacionCaracteristica extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'id_tipo_habitacion',
        'id_caracteristica',
    ];
}
