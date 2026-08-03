<?php

use App\Models\DetalleRol;
use App\Models\Permiso;
use App\Models\Persona;

function loguearAdminConPermiso(string $username, string $nombrePermiso): void
{
    $permiso = Permiso::firstOrCreate(['nombre' => $nombrePermiso]);
    $rol = crearRol('administrador_' . $username);
    DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permiso->id]);
    [$user, $password] = crearUsuarioConRol($rol, $username);
    test()->post('/login', ['username' => $user->username, 'password' => $password]);
}

it('descarga un PDF con todos los registros cuando no se envían ids', function () {
    loguearAdminConPermiso('admin_pdf1', 'persona');

    Persona::create(['nombre' => 'Ana', 'apellido' => 'Gomez', 'cedula' => '111', 'celular' => '700', 'correo' => 'a@a.com', 'estado' => 1]);
    Persona::create(['nombre' => 'Beto', 'apellido' => 'Rios', 'cedula' => '222', 'celular' => '701', 'correo' => 'b@b.com', 'estado' => 0]);

    $response = $this->post('/persona/pdf');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('descarga un PDF solo con los ids enviados (registros filtrados o específicos)', function () {
    loguearAdminConPermiso('admin_pdf2', 'persona');

    $ana = Persona::create(['nombre' => 'Ana', 'apellido' => 'Gomez', 'cedula' => '111', 'celular' => '700', 'correo' => 'a@a.com', 'estado' => 1]);
    Persona::create(['nombre' => 'Beto', 'apellido' => 'Rios', 'cedula' => '222', 'celular' => '701', 'correo' => 'b@b.com', 'estado' => 0]);

    $response = $this->post('/persona/pdf', ['ids' => [$ana->id]]);

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('exige permiso del módulo para exportar el PDF', function () {
    $response = $this->post('/persona/pdf');

    $response->assertRedirect(route('login'));
});

it('genera el PDF de cada uno de los 12 módulos con datos reales de los seeders', function (string $ruta) {
    $this->seed();

    $this->post('/login', ['username' => 'DeibyJY', 'password' => '12345'])
        ->assertRedirect(route('mostrar.reserva'));

    $response = $this->post($ruta);

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
})->with([
    '/persona/pdf',
    '/cliente/pdf',
    '/trabajador/pdf',
    '/habitacion/pdf',
    '/tipo_habitacion/pdf',
    '/caracteristica/pdf',
    '/servicio_extra/pdf',
    '/reserva/pdf',
    '/pago/pdf',
    '/permiso/pdf',
    '/rol/pdf',
    '/usuario/pdf',
]);
