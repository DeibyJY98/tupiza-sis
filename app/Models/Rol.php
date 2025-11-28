<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'nombre',
        'estado',
    ];
    
    public function toShow(){
        return [
            'nombre' => $this->nombre,
            'estado' => $this->estado
        ];
    }
    
    public function users()
    {
        return $this->hasMany(User::class, 'id_rol');
    }
}
