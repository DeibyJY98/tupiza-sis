<?php

namespace Database\Seeders;

use App\Models\Persona;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PersonaSeeder extends Seeder
{
    public function run(): void
    {
        Persona::create([
            'nombre' => 'Deiby',
            'apellido' => 'Justiniano',
            'cedula' => '8179066',
            'celular' => '70862228',
            'correo' => 'deibyjy@mail.com',
            'estado' => 1
        ]);
        Persona::create([
            'nombre' => 'Sharon',
            'apellido' => 'Paniagua',
            'cedula' => '8179067',
            'celular' => '70862229',
            'correo' => 'sharonp@mail.com',
            'estado' => 1
        ]);
        Persona::create([
            'nombre' => 'Francisco',
            'apellido' => 'Terraza',
            'cedula' => '8179061',
            'celular' => '70862225',
            'correo' => 'franciscot@mail.com',
            'estado' => 1
        ]);
        Persona::create([
            'nombre' => 'Carlos',
            'apellido' => 'Lopez',
            'cedula' => '8179068',
            'celular' => '70862230',
            'correo' => 'carlosl@mail.com',
            'estado' => 1
        ]);
        Persona::create([
            'nombre' => 'Daniel',
            'apellido' => 'Perez',
            'cedula' => '8179069',
            'celular' => '70862231',
            'correo' => 'danielp@mail.com',
            'estado' => 1
        ]);
        Persona::create([
            'nombre' => 'Shirley',
            'apellido' => 'Zarate',
            'cedula' => '8179070',
            'celular' => '70862232',
            'correo' => 'shirleyz@mail.com',
            'estado' => 1
        ]);
    }
}
