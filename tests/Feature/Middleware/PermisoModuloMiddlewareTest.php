<?php

use App\Models\DetalleRol;
use App\Models\Permiso;
use Illuminate\Support\Facades\Route;

// Ruta de prueba aislada de cualquier controlador/vista real, para probar únicamente
// la autorización del middleware `permiso:{modulo}`.
beforeEach(function () {
    // 'web' es necesario explícitamente: las rutas registradas fuera de routes/web.php
    // no heredan automáticamente el grupo de middleware "web" (sesión, cookies, etc.).
    Route::middleware(['web', 'permiso:reserva'])->get('/_test/reserva', fn () => 'ok');
});

it('redirige a login cuando no hay usuario autenticado', function () {
    $response = $this->get('/_test/reserva');

    $response->assertRedirect(route('login'));
});

// Nota: el guard usado en /login depende del id numérico del rol (1/2/3), no de su
// nombre; la verificación de permisos en el middleware usa el id_rol real del usuario,
// así que el nombre del rol aquí es solo etiqueta y no afecta el resultado de la prueba.
it('permite el acceso cuando el rol del usuario tiene el permiso del módulo', function () {
    $permisoReserva = Permiso::create(['nombre' => 'reserva']);

    $rol = crearRol('recepcionista');
    DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permisoReserva->id]);

    [$user, $password] = crearUsuarioConRol($rol, 'recepcionista1');
    $this->post('/login', ['username' => $user->username, 'password' => $password]);

    $response = $this->get('/_test/reserva');

    $response->assertOk();
    $response->assertSee('ok');
});

it('bloquea el acceso cuando el rol del usuario NO tiene el permiso del módulo', function () {
    Permiso::create(['nombre' => 'reserva']);
    $permisoHabitacion = Permiso::create(['nombre' => 'habitacion']);

    $rol = crearRol('cliente');
    // Este rol solo tiene permiso sobre "habitacion", no sobre "reserva".
    DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permisoHabitacion->id]);

    [$user, $password] = crearUsuarioConRol($rol, 'cliente1');
    $this->post('/login', ['username' => $user->username, 'password' => $password]);

    $response = $this->get('/_test/reserva');

    $response->assertSessionHas('autorizacion');
    $response->assertStatus(302);
});
