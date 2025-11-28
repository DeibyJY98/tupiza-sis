<?php

namespace Database\Seeders;

use App\Models\User;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PersonaSeeder::class,
            PermisoSeeder::class,
            ServicioExtraSeeder::class,
            TipoHabitacionSeeder::class,
            CaracteristicaSeeder::class,
            RolSeeder::class,
            UserSeeder::class,
            ClienteSeeder::class,
            TrabajadorSeeder::class,
            ReservaSeeder::class,
            HabitacionSeeder::class,
            DetalleRolSeeder::class,
            HabitacionReservaSeeder::class,
            TipoHabitacionCaracteristicaSeeder::class,
            HabitacionServicioExtraSeeder::class,
            PagoSeeder::class
        ]);
    }
}
