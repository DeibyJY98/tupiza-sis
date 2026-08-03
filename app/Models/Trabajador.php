<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trabajador extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'cargo',
        'salario',
        'estado',
        'id_persona',
    ];

    public function persona(){
        return $this->belongsTo(Persona::class,'id_persona'); //uno a muchos, solo tiene 1
    }

    public function toShow(){
        return[
            'id'        => $this->id,
            'cargo'     => $this->cargo,
            'salario'   => $this->salario,
            'estado'    => $this->estado,

            //Datos de persona asociada
            'persona' => $this->persona ? [
                'id'       => $this->persona->id,
                'cedula'   => $this->persona->cedula,
                'nombre'   => $this->persona->nombre,
                'apellido' => $this->persona->apellido,
            ] : null,

        ];
    }

}
