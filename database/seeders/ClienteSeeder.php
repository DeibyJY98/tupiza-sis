<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Persona;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        // 'persona' es el índice (0-based) dentro del orden de creación de PersonaSeeder.
        $personas = Persona::orderBy('id')->get();

        $clientes = [
            ['persona' => 3, 'nit' => '548179068', 'razon_social' => 'Lopez Turismo S.R.L.', 'estado' => 1],
            ['persona' => 4, 'nit' => '558179069', 'razon_social' => 'Perez Hnos. S.A.', 'estado' => 1],
            ['persona' => 5, 'nit' => '568179070', 'razon_social' => 'Zarate Comercial', 'estado' => 1],
            ['persona' => 9, 'nit' => '578179074', 'razon_social' => 'Fernandez Import S.R.L.', 'estado' => 1],
            ['persona' => 10, 'nit' => '588179075', 'razon_social' => 'Choque Distribuciones', 'estado' => 1],
            ['persona' => 11, 'nit' => '598179076', 'razon_social' => 'Aguilar y Asociados', 'estado' => 1],
            ['persona' => 12, 'nit' => '608179077', 'razon_social' => 'Flores Textiles', 'estado' => 1],
            ['persona' => 13, 'nit' => '618179078', 'razon_social' => 'Condori Construcciones', 'estado' => 0],
            ['persona' => 14, 'nit' => '628179079', 'razon_social' => 'Villca Agropecuaria', 'estado' => 1],
            ['persona' => 15, 'nit' => '638179080', 'razon_social' => 'Salazar Minerales S.A.', 'estado' => 1],
            ['persona' => 16, 'nit' => '648179081', 'razon_social' => 'Cruz Hoteleria', 'estado' => 0],
            ['persona' => 17, 'nit' => '658179082', 'razon_social' => 'Ticona Transportes', 'estado' => 1],
        ];

        foreach ($clientes as $data) {
            Cliente::create([
                'nit' => $data['nit'],
                'razon_social' => $data['razon_social'],
                'estado' => $data['estado'],
                'id_persona' => $personas[$data['persona']]->id,
            ]);
        }
    }
}
