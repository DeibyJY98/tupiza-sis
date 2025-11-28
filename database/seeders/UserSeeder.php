<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
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
    }
}
