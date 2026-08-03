<?php

namespace Database\Seeders;

use App\Models\Persona;
use Illuminate\Database\Seeder;

class PersonaSeeder extends Seeder
{
    public function run(): void
    {
        // Índices 0-19, en este orden, para que el resto de seeders puedan referenciar
        // cada persona de forma predecible (ver comentarios en ClienteSeeder/TrabajadorSeeder/UserSeeder).
        $personas = [
            ['nombre' => 'Deiby', 'apellido' => 'Justiniano', 'cedula' => '8179066', 'celular' => '70862228', 'correo' => 'deibyjy@mail.com', 'estado' => 1],
            ['nombre' => 'Sharon', 'apellido' => 'Paniagua', 'cedula' => '8179067', 'celular' => '70862229', 'correo' => 'sharonp@mail.com', 'estado' => 1],
            ['nombre' => 'Francisco', 'apellido' => 'Terraza', 'cedula' => '8179061', 'celular' => '70862225', 'correo' => 'franciscot@mail.com', 'estado' => 1],
            ['nombre' => 'Carlos', 'apellido' => 'Lopez', 'cedula' => '8179068', 'celular' => '70862230', 'correo' => 'carlosl@mail.com', 'estado' => 1],
            ['nombre' => 'Daniel', 'apellido' => 'Perez', 'cedula' => '8179069', 'celular' => '70862231', 'correo' => 'danielp@mail.com', 'estado' => 1],
            ['nombre' => 'Shirley', 'apellido' => 'Zarate', 'cedula' => '8179070', 'celular' => '70862232', 'correo' => 'shirleyz@mail.com', 'estado' => 1],
            ['nombre' => 'Mariana', 'apellido' => 'Rojas', 'cedula' => '8179071', 'celular' => '70862233', 'correo' => 'marianar@mail.com', 'estado' => 1],
            ['nombre' => 'Jhonny', 'apellido' => 'Quispe', 'cedula' => '8179072', 'celular' => '70862234', 'correo' => 'jhonnyq@mail.com', 'estado' => 1],
            ['nombre' => 'Adela', 'apellido' => 'Mamani', 'cedula' => '8179073', 'celular' => '70862235', 'correo' => 'adelam@mail.com', 'estado' => 0],
            ['nombre' => 'Rodrigo', 'apellido' => 'Fernandez', 'cedula' => '8179074', 'celular' => '70862236', 'correo' => 'rodrigof@mail.com', 'estado' => 1],
            ['nombre' => 'Patricia', 'apellido' => 'Choque', 'cedula' => '8179075', 'celular' => '70862237', 'correo' => 'patriciac@mail.com', 'estado' => 1],
            ['nombre' => 'Alvaro', 'apellido' => 'Aguilar', 'cedula' => '8179076', 'celular' => '70862238', 'correo' => 'alvaroa@mail.com', 'estado' => 1],
            ['nombre' => 'Veronica', 'apellido' => 'Flores', 'cedula' => '8179077', 'celular' => '70862239', 'correo' => 'veronicaf@mail.com', 'estado' => 1],
            ['nombre' => 'Miguel', 'apellido' => 'Condori', 'cedula' => '8179078', 'celular' => '70862240', 'correo' => 'miguelc@mail.com', 'estado' => 0],
            ['nombre' => 'Gabriela', 'apellido' => 'Villca', 'cedula' => '8179079', 'celular' => '70862241', 'correo' => 'gabrielav@mail.com', 'estado' => 1],
            ['nombre' => 'Oscar', 'apellido' => 'Salazar', 'cedula' => '8179080', 'celular' => '70862242', 'correo' => 'oscars@mail.com', 'estado' => 1],
            ['nombre' => 'Fatima', 'apellido' => 'Cruz', 'cedula' => '8179081', 'celular' => '70862243', 'correo' => 'fatimac@mail.com', 'estado' => 0],
            ['nombre' => 'Ruben', 'apellido' => 'Ticona', 'cedula' => '8179082', 'celular' => '70862244', 'correo' => 'rubent@mail.com', 'estado' => 1],
            ['nombre' => 'Lourdes', 'apellido' => 'Apaza', 'cedula' => '8179083', 'celular' => '70862245', 'correo' => 'lourdesa@mail.com', 'estado' => 1],
            ['nombre' => 'Ivan', 'apellido' => 'Mendoza', 'cedula' => '8179084', 'celular' => '70862246', 'correo' => 'ivanm@mail.com', 'estado' => 1],
        ];

        foreach ($personas as $persona) {
            Persona::create($persona);
        }
    }
}
