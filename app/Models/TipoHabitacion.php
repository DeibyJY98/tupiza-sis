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

    public function toShow()
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'cant_cama' => $this->cant_cama,
            'precio' => $this->precio,
            'estado' => $this->estado,
        ];
    }
}
