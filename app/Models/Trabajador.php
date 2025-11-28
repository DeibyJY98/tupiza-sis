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
            'estado'    => $this->estado,
            //'nombre'    => $this->persona->nombre ?? null,
            //'apellido'  => $this->persona->apellido ?? null

            //Datos de persona asociada
            'persona' => $this->persona ? [
                'cedula'   => $this-> persona->cedula,
                'nombre'   => $this-> persona->nombre,
                'apellido' => $this-> persona->apellido,
            ] : null,

        ];
    }

}
