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

// Regresión: /rol y /permiso usaban RolMiddleware/PermisoMiddleware con ids de permiso
// hardcodeados (1 y 2 respectivamente). En el seeder real esos ids correspondían al
// permiso contrario ("permiso" y "rol" cruzados), así que un usuario con el permiso
// "rol" no podía entrar a /rol, y viceversa. Ahora ambas rutas usan permiso:{modulo}
// como el resto de módulos, así que el nombre manda y no el id numérico.
it('el permiso "rol" (aunque no tenga el id 1) da acceso a /rol y no a /permiso', function () {
    // Se crean en este orden para que "rol" termine con id=1, justo el escenario que
    // rompía con el middleware viejo (que exigía id_permiso == 1 para entrar a /rol).
    $permisoRol = Permiso::create(['nombre' => 'rol']);
    Permiso::create(['nombre' => 'permiso']);

    $rol = crearRol('solo_rol');
    DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permisoRol->id]);

    [$user, $password] = crearUsuarioConRol($rol, 'solo_rol1');
    $this->post('/login', ['username' => $user->username, 'password' => $password]);

    $this->get('/rol')->assertOk();

    $response = $this->get('/permiso');
    $response->assertSessionHas('autorizacion');
    $response->assertStatus(302);
});

it('el permiso "permiso" (aunque no tenga el id 2) da acceso a /permiso y no a /rol', function () {
    Permiso::create(['nombre' => 'rol']);
    $permisoPermiso = Permiso::create(['nombre' => 'permiso']);

    $rol = crearRol('solo_permiso');
    DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permisoPermiso->id]);

    [$user, $password] = crearUsuarioConRol($rol, 'solo_permiso1');
    $this->post('/login', ['username' => $user->username, 'password' => $password]);

    $this->get('/permiso')->assertOk();

    $response = $this->get('/rol');
    $response->assertSessionHas('autorizacion');
    $response->assertStatus(302);
});
