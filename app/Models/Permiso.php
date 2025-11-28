<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permiso extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'nombre',
    ];
    
    public function toShow(){
        return [
            'nombre' => $this->name
        ];
    }
}
