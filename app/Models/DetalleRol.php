<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleRol extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'id_rol',
        'id_permiso',
    ];
}
