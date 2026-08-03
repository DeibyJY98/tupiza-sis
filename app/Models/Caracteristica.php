<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caracteristica extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'nombre',
        'estado',
    ];

    public function toShow()
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'estado' => $this->estado,
        ];
    }
}
