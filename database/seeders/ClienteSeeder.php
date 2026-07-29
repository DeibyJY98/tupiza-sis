<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        Cliente::create([
            'nit' => '548179068',
            'razon_social' => 'CarlosLopez s.r.l.',
            'estado' => 1,
            'id_persona' => 4
        ]);
        Cliente::create([
            'nit' => '558179069',
            'razon_social' => 'DanielPerez S.A.',
            'estado' => 1,
            'id_persona' => 5
        ]);
        Cliente::create([
            'nit' => '568179070',
            'razon_social' => 'ShirleyZarate s.r.l.',
            'estado' => 1,
            'id_persona' => 6
        ]);
    }


}
