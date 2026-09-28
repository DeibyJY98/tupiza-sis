<?php

use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;

// El botón "Crear usuario" no tenía ni modal ni listener asociado: era un
// <button id="abrirModalCrear"> sin nada que lo abriera, así que no hacía nada al
// hacer clic. Se alineó el CRUD completo (crear/editar/eliminar) al mismo patrón de
// modales que usan los demás módulos (reserva, pago, característica, etc.), en vez
// de las páginas separadas que tenía antes.
beforeEach(function () {
    $this->seed();
    $this->post('/login', ['username' => 'DeibyJY', 'password' => '12345'])
        ->assertRedirect(route('dashboard'));
});

it('el listado de usuarios trae el modal y el listener de "Crear usuario"', function () {
    $response = $this->get('/usuario');

    $response->assertOk();
    $response->assertSee('id="abrirModalCrear"', false);
    $response->assertSee('id="modalCrear"', false);
});

it('el botón "Editar" de un usuario trae sus datos para precargar el modal, sin volver a pedir al servidor', function () {
    $response = $this->get('/usuario');

    $response->assertOk();
    $response->assertSee('btn-abrir-editar', false);
    $response->assertSee('id="modalEditar"', false);
    $response->assertSee('data-username="DeibyJY"', false);
});

it('crea un usuario a través del modal (POST directo, sin página intermedia)', function () {
    $persona = Persona::create([
        'nombre' => 'Nueva', 'apellido' => 'Persona', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000099', 'correo' => 'nueva.persona@mail.com', 'estado' => 1,
    ]);
    $rol = Rol::where('nombre', 'recepcionista')->firstOrFail();

    $response = $this->post('/usuario/crear', [
        'id_persona' => $persona->id,
        'username' => 'NuevaPersonaUser',
        'email' => 'nueva.persona@mail.com',
        'password' => 'clave123',
        'estado' => 1,
        'id_rol' => $rol->id,
    ]);

    $response->assertRedirect(route('mostrar.usuario'));
    expect(User::where('username', 'NuevaPersonaUser')->exists())->toBeTrue();
});

it('edita un usuario sin cambiar su email sin que la validación de único falle contra sí mismo', function () {
    $usuario = User::where('username', 'DeibyJY')->firstOrFail();

    $response = $this->post('/usuario/modificarPost', [
        'id' => $usuario->id,
        'username' => $usuario->username,
        'email' => $usuario->email,
        'estado' => $usuario->estado,
        'id_rol' => $usuario->id_rol,
    ]);

    $response->assertRedirect(route('mostrar.usuario'));
    $response->assertSessionMissing('error');
});

it('el botón "Ver" de un usuario incluye persona (cédula/celular), rol y estado', function () {
    $response = $this->get('/usuario');

    $response->assertOk();
    // DeibyJY / persona "Deiby Justiniano", sembrada por UserSeeder/PersonaSeeder.
    $response->assertSee('8179066', false); // cédula
    $response->assertSee('70862228', false); // celular
    $response->assertSee('btn-abrir-ver-usuario', false);
    $response->assertSee('id="modalVerUsuario"', false);
});

it('el listado de usuarios trae el modal de confirmación para inactivar', function () {
    $response = $this->get('/usuario');

    $response->assertOk();
    $response->assertSee('btn-abrir-eliminar', false);
    $response->assertSee('id="modalEliminar"', false);
});
