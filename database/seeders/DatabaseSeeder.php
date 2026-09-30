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
            // HabitacionSeeder va antes: ReservaSeeder calcula el costo_total con el precio
            // del tipo de cada habitación.
            HabitacionSeeder::class,
            ReservaSeeder::class,
            DetalleRolSeeder::class,
            HabitacionReservaSeeder::class,
            TipoHabitacionCaracteristicaSeeder::class,
            HabitacionServicioExtraSeeder::class,
            PagoSeeder::class
        ]);
    }
}
