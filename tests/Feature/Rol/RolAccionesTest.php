<?php

use App\Models\DetalleRol;
use App\Models\Permiso;
use App\Models\Rol;

// Mismo bug que en Usuario: "Crear Rol" era un botón sin listener ni modal. Se
// alineó el CRUD completo al mismo patrón de modales que usan los demás módulos.

beforeEach(function () {
    $this->seed();
    $this->post('/login', ['username' => 'DeibyJY', 'password' => '12345'])
        ->assertRedirect(route('dashboard'));
});

it('el listado de roles trae el modal y el listener de "Crear Rol"', function () {
    $response = $this->get('/rol');

    $response->assertOk();
    $response->assertSee('id="abrirModalCrear"', false);
    $response->assertSee('id="modalCrear"', false);
});

it('crea un rol a través del modal (POST directo, sin página intermedia)', function () {
    // 'nombre' tiene max:15 en RolController::store().
    $response = $this->post('/rol/crear', ['nombre' => 'rol_' . rand(1, 999), 'estado' => 1]);

    $response->assertRedirect(route('mostrar.rol'));
});

it('el listado de roles trae el modal de confirmación para eliminar', function () {
    $response = $this->get('/rol');

    $response->assertOk();
    $response->assertSee('btn-abrir-eliminar', false);
    $response->assertSee('id="modalEliminar"', false);
});

it('el botón "Ver" de un rol incluye sus permisos y usuarios asignados', function () {
    $response = $this->get('/rol');

    $response->assertOk();
    // "administrador" (sembrado con los 12 permisos) y su único usuario, DeibyJY.
    $response->assertSee('trabajador', false); // nombre de un permiso sembrado
    $response->assertSee('Deiby Justiniano', false);
    $response->assertSee('btn-abrir-ver-rol', false);
    $response->assertSee('id="modalVerRol"', false);
});

it('Rol::permisos() no duplica un permiso revocado y vuelto a asignar (soft delete de detalle_rols)', function () {
    $rol = Rol::create(['nombre' => 'temporal_' . uniqid(), 'estado' => 1]);
    $permiso = Permiso::create(['nombre' => 'permiso_test_' . uniqid()]);

    $detalleRevocado = DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permiso->id]);
    $detalleRevocado->delete(); // soft delete: el permiso fue revocado
    DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permiso->id]); // vuelto a asignar

    expect($rol->permisos()->count())->toBe(1);
});
