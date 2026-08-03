<?php

namespace Database\Seeders;

use App\Models\TipoHabitacion;
use Illuminate\Database\Seeder;

class TipoHabitacionSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['nombre' => 'Simple', 'descripcion' => 'Habitación para una persona', 'cant_cama' => 1, 'precio' => 120, 'estado' => 1],
            ['nombre' => 'Doble', 'descripcion' => 'Habitación para dos personas', 'cant_cama' => 2, 'precio' => 180, 'estado' => 1],
            ['nombre' => 'Matrimonial', 'descripcion' => 'Habitación para pareja, cama matrimonial', 'cant_cama' => 1, 'precio' => 200, 'estado' => 1],
            ['nombre' => 'Suite', 'descripcion' => 'Habitación amplia con sala de estar', 'cant_cama' => 2, 'precio' => 320, 'estado' => 1],
            ['nombre' => 'Familiar', 'descripcion' => 'Habitación para hasta 4 personas', 'cant_cama' => 4, 'precio' => 280, 'estado' => 0],
        ];

        foreach ($tipos as $tipo) {
            TipoHabitacion::create($tipo);
        }
    }
}
