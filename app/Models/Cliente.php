<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'id',
        'nit',
        'razon_social',
        'estado',
        'id_persona',
    ];

    public function persona(){
        return $this->belongsTo(Persona::class,'id_persona');
    }

    public function toShow(){
        return[
            'id'            => $this->id,
            'nit'           => $this->nit,
            'razon_social'  => $this->razon_social,
            'estado'        => $this->estado,
            //'nombre'    => $this->persona->nombre ?? null,
            //'apellido'  => $this->persona->apellido ?? null,

            //Datos de persona asociada
            'persona' => $this->persona ? [
                'cedula'   => $this-> persona->cedula,
                'nombre'   => $this-> persona->nombre,
                'apellido' => $this-> persona->apellido,
            ] : null,
        ];
    }
}
