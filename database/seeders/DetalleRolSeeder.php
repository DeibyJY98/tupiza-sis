<?php

namespace Database\Seeders;

use App\Models\DetalleRol;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetalleRolSeeder extends Seeder
{
    public function run(): void
    {
        //permisos administrador
        DetalleRol::create(['id_permiso'=> 1,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 2,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 3,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 4,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 5,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 6,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 7,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 8,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 9,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 10,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 11,'id_rol'=> 1]);
        DetalleRol::create(['id_permiso'=> 12,'id_rol'=> 1]);

        //permisos recepcionista
        DetalleRol::create(['id_permiso'=> 4,'id_rol'=> 2]);
        DetalleRol::create(['id_permiso'=> 5,'id_rol'=> 2]);
        DetalleRol::create(['id_permiso'=> 6,'id_rol'=> 2]);
        DetalleRol::create(['id_permiso'=> 7,'id_rol'=> 2]);
        DetalleRol::create(['id_permiso'=> 8,'id_rol'=> 2]);
        DetalleRol::create(['id_permiso'=> 9,'id_rol'=> 2]);
        DetalleRol::create(['id_permiso'=> 10,'id_rol'=> 2]);
        DetalleRol::create(['id_permiso'=> 11,'id_rol'=> 2]);
        DetalleRol::create(['id_permiso'=> 12,'id_rol'=> 2]);
        
        //permisos cliente
        DetalleRol::create(['id_permiso'=> 7,'id_rol'=> 3]);
        DetalleRol::create(['id_permiso'=> 12,'id_rol'=> 3]);

        //permisos cliente
        DetalleRol::create(['id_permiso'=> 10,'id_rol'=> 4]);

    }
}
