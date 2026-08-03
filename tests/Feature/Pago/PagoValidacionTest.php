<?php

use App\Models\Cliente;
use App\Models\DetalleRol;
use App\Models\Pago;
use App\Models\Permiso;
use App\Models\Persona;
use App\Models\Reserva;
use App\Models\Trabajador;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// El entorno de pruebas no tiene la extensión GD con soporte JPEG, así que
// UploadedFile::fake()->image() falla; se crea el fake con MIME explícito en su lugar.
function comprobanteFake(string $nombre): UploadedFile
{
    return UploadedFile::fake()->create($nombre, 10, 'image/jpeg');
}

function crearReservaConCosto(int $costoTotal): Reserva
{
    $personaCliente = Persona::create([
        'nombre' => 'Cliente', 'apellido' => 'Prueba', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000000', 'correo' => fake()->unique()->safeEmail(), 'estado' => 1,
    ]);
    $cliente = Cliente::create(['nit' => 123, 'razon_social' => 'N/A', 'estado' => 1, 'id_persona' => $personaCliente->id]);

    $personaTrabajador = Persona::create([
        'nombre' => 'Trabajador', 'apellido' => 'Prueba', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000001', 'correo' => fake()->unique()->safeEmail(), 'estado' => 1,
    ]);
    $trabajador = new Trabajador(['cargo' => 'Recepcionista', 'estado' => 1, 'id_persona' => $personaTrabajador->id]);
    $trabajador->salario = '1500';
    $trabajador->save();

    return Reserva::create([
        'fecha_inicio' => now()->addDay(),
        'fecha_fin' => now()->addDays(3),
        'costo_total' => $costoTotal,
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_trabajador' => $trabajador->id,
    ]);
}

beforeEach(function () {
    Storage::fake('public');

    // La ruta /pago está protegida por el middleware permiso:pago (Punto 2),
    // así que hace falta un usuario autenticado con ese permiso para probar el controlador.
    $permisoPago = Permiso::create(['nombre' => 'pago']);
    $rol = crearRol('recepcionista');
    DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permisoPago->id]);
    [$user, $password] = crearUsuarioConRol($rol, 'recepcionista_pagos');
    $this->post('/login', ['username' => $user->username, 'password' => $password]);
});

it('rechaza un pago cuyo monto excede el saldo pendiente de la reserva', function () {
    $reserva = crearReservaConCosto(100);

    $response = $this->post('/pago', [
        'fecha' => now()->toDateString(),
        'monto' => 150,
        'comprobante' => comprobanteFake('comprobante.jpg'),
        'estado' => 1,
        'id_cliente' => $reserva->id_cliente,
        'id_reserva' => $reserva->id,
    ]);

    $response->assertSessionHas('error');
    expect(Pago::count())->toBe(0);
});

it('acepta un pago dentro del saldo pendiente y descuenta pagos previos completados', function () {
    $reserva = crearReservaConCosto(100);

    Pago::create([
        'fecha' => now(), 'monto' => 60, 'comprobante' => 'x.jpg', 'estado' => 1,
        'id_cliente' => $reserva->id_cliente, 'id_reserva' => $reserva->id,
    ]);

    // Quedan 40 de saldo; pedir 50 debe rechazarse...
    $rechazado = $this->post('/pago', [
        'fecha' => now()->toDateString(), 'monto' => 50,
        'comprobante' => comprobanteFake('c1.jpg'), 'estado' => 1,
        'id_cliente' => $reserva->id_cliente, 'id_reserva' => $reserva->id,
    ]);
    $rechazado->assertSessionHas('error');

    // ...pero 40 exactos sí debe aceptarse.
    $aceptado = $this->post('/pago', [
        'fecha' => now()->toDateString(), 'monto' => 40,
        'comprobante' => comprobanteFake('c2.jpg'), 'estado' => 1,
        'id_cliente' => $reserva->id_cliente, 'id_reserva' => $reserva->id,
    ]);
    $aceptado->assertSessionMissing('error');
    expect(Pago::where('id_reserva', $reserva->id)->where('estado', 1)->sum('monto'))->toBe(100);
});

it('ignora los pagos cancelados al calcular el saldo pendiente', function () {
    $reserva = crearReservaConCosto(100);

    Pago::create([
        'fecha' => now(), 'monto' => 90, 'comprobante' => 'x.jpg', 'estado' => 0, // cancelado
        'id_cliente' => $reserva->id_cliente, 'id_reserva' => $reserva->id,
    ]);

    $response = $this->post('/pago', [
        'fecha' => now()->toDateString(), 'monto' => 100,
        'comprobante' => comprobanteFake('c.jpg'), 'estado' => 1,
        'id_cliente' => $reserva->id_cliente, 'id_reserva' => $reserva->id,
    ]);

    $response->assertSessionMissing('error');
});

it('exige comprobante al registrar un pago', function () {
    $reserva = crearReservaConCosto(100);

    $response = $this->post('/pago', [
        'fecha' => now()->toDateString(),
        'monto' => 50,
        'estado' => 1,
        'id_cliente' => $reserva->id_cliente,
        'id_reserva' => $reserva->id,
    ]);

    $response->assertSessionHas('error');
    expect(Pago::count())->toBe(0);
});
