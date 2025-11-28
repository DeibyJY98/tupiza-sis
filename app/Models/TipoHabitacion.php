<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoHabitacion extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'cant_cama',
        'precio',
        'estado',
    ];
}
