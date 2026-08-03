<?php

use App\Models\DetalleRol;
use App\Models\Notificacion;
use App\Models\Permiso;

function loguearAdminConPermisoPersona(string $username): void
{
    $permiso = Permiso::firstOrCreate(['nombre' => 'persona']);
    $rol = crearRol('administrador_' . $username);
    DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permiso->id]);
    [$user, $password] = crearUsuarioConRol($rol, $username);
    test()->post('/login', ['username' => $user->username, 'password' => $password]);
}

it('muestra en el navbar el mensaje y el badge de las notificaciones no leídas', function () {
    loguearAdminConPermisoPersona('admin_navbar1');

    Notificacion::create([
        'tipo' => 'checkout',
        'mensaje' => 'Check-out: la reserva de Juan Pérez finalizó hoy. La habitación 101 quedó disponible.',
        'leida' => false,
    ]);
    Notificacion::create(['tipo' => 'checkout', 'mensaje' => 'Notificación ya leída', 'leida' => true]);

    $response = $this->get('/persona');

    $response->assertOk();
    $response->assertSee('notificacionesPanel', false);
    $response->assertSee('quedó disponible', false);
    $response->assertSee('class="badge"', false);
});

it('no muestra el badge cuando no hay notificaciones sin leer', function () {
    loguearAdminConPermisoPersona('admin_navbar2');

    Notificacion::create(['tipo' => 'checkout', 'mensaje' => 'Ya leída', 'leida' => true]);

    $response = $this->get('/persona');

    $response->assertOk();
    $response->assertDontSee('class="badge"', false);
});

it('marca todas las notificaciones como leídas cuando el usuario está autenticado', function () {
    loguearAdminConPermisoPersona('admin_marcar');

    Notificacion::create(['tipo' => 'checkout', 'mensaje' => 'Uno', 'leida' => false]);
    Notificacion::create(['tipo' => 'checkout', 'mensaje' => 'Dos', 'leida' => false]);

    $response = $this->post('/notificaciones/marcar-leidas');

    $response->assertRedirect();
    expect(Notificacion::where('leida', false)->count())->toBe(0);
});

it('redirige a login si un invitado intenta marcar notificaciones como leídas', function () {
    Notificacion::create(['tipo' => 'checkout', 'mensaje' => 'Uno', 'leida' => false]);

    $response = $this->post('/notificaciones/marcar-leidas');

    $response->assertRedirect(route('login'));
    expect(Notificacion::where('leida', false)->count())->toBe(1);
});
