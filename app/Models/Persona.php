<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Persona extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'apellido',
        'cedula',
        'celular',
        'correo',
        'estado'
    ];

    public function toShow()
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'cedula' => $this->cedula,
            'celular' => $this->celular,
            'correo' => $this->correo,
            'estado' => $this->estado,
        ];
    }
}
