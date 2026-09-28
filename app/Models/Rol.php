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

    public function permisos()
    {
        // wherePivotNull('deleted_at'): detalle_rols tiene soft delete, igual que
        // habitacion_reservas (ver Reserva::habitaciones()) — sin este filtro, un
        // permiso revocado y reasignado quedaría duplicado.
        return $this->belongsToMany(Permiso::class, 'detalle_rols', 'id_rol', 'id_permiso')
                    ->wherePivotNull('deleted_at');
    }
}
