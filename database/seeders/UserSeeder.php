<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Cuentas originales del sistema (no cambiar username/password: se usan para login en tests).
        User::create([
            'username' => 'DeibyJY',
            'email'     => 'deibyjy@mail.com',
            'password' => Hash::make('12345'),
            'estado' => 1,
            'id_rol' => 1,
            'id_persona' => 1
        ]);
        User::create([
            'username' => 'SharonP',
            'email'     => 'sharonp@mail.com',
            'password' => Hash::make('12345'),
            'estado' => 1,
            'id_rol' => 2,
            'id_persona' => 2
        ]);
        User::create([
            'username' => 'FranciscoT',
            'email'     => 'franciscot@mail.com',
            'password' => Hash::make('12345'),
            'estado' => 1,
            'id_rol' => 4,
            'id_persona' => 3
        ]);

        // Cuentas adicionales para tener variedad de estado/rol en el filtro de Usuarios.
        $personas = Persona::orderBy('id')->get();

        User::create([
            'username' => 'MarianaR',
            'email' => 'marianar@mail.com',
            'password' => Hash::make('12345'),
            'estado' => 1,
            'id_rol' => 2, // recepcionista
            'id_persona' => $personas[6]->id, // Mariana Rojas
        ]);
        User::create([
            'username' => 'CarlosL',
            'email' => 'carlosl@mail.com',
            'password' => Hash::make('12345'),
            'estado' => 1,
            'id_rol' => 3, // cliente
            'id_persona' => $personas[3]->id, // Carlos Lopez
        ]);
        User::create([
            'username' => 'DanielP',
            'email' => 'danielp@mail.com',
            'password' => Hash::make('12345'),
            'estado' => 0, // cuenta inactiva, útil para probar el filtro de estado
            'id_rol' => 3, // cliente
            'id_persona' => $personas[4]->id, // Daniel Perez
        ]);
    }
}
