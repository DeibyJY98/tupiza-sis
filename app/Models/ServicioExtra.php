<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicioExtra extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'nombre',
        'descripcion',
        'precio',
        'estado',
    ];

    public function toShow()
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'estado' => $this->estado,
        ];
    }
}
