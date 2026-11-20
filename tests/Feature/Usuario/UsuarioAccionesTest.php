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
        'password' => 'Clave1234',
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

it('rechaza contraseñas que no cumplen la política (mínimo 8, mayúsculas, minúsculas y números)', function () {
    $persona = Persona::create([
        'nombre' => 'Otra', 'apellido' => 'Persona', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000098', 'correo' => 'otra.persona@mail.com', 'estado' => 1,
    ]);
    $rol = Rol::where('nombre', 'recepcionista')->firstOrFail();

    foreach (['Ab1', 'clave1234', 'CLAVE1234', 'ClaveClave'] as $i => $debil) {
        $this->post('/usuario/crear', [
            'id_persona' => $persona->id,
            'username' => 'DebilUser' . $i,
            'email' => "debil{$i}@mail.com",
            'password' => $debil,
            'estado' => 1,
            'id_rol' => $rol->id,
        ]);

        expect(User::where('username', 'DebilUser' . $i)->exists())->toBeFalse();
    }
});

it('si la contraseña es débil, reabre el modal de crear con el error dentro y los datos ya escritos', function () {
    $persona = Persona::create([
        'nombre' => 'Mod', 'apellido' => 'Al', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000097', 'correo' => 'modal@mail.com', 'estado' => 1,
    ]);
    $rol = Rol::where('nombre', 'recepcionista')->firstOrFail();

    $this->post('/usuario/crear', [
        'id_persona' => $persona->id,
        'username' => 'UsuarioModal',
        'email' => 'modal@mail.com',
        'password' => 'debil',
        'estado' => 1,
        'id_rol' => $rol->id,
    ])->assertSessionHas('modal', 'crear')->assertSessionHas('error');

    $respuesta = $this->get('/usuario');

    // El script del layout reabre #modalCrear, lo rellena con lo escrito y muestra el error
    $respuesta->assertSee('document.getElementById("modalCrear")', false);
    $respuesta->assertSee('"username":"UsuarioModal"', false);
    $respuesta->assertSee('al menos 8 caracteres');
    // la contraseña no se devuelve al formulario
    $respuesta->assertDontSee('"password":"debil"', false);
});

it('un error al editar reabre el modal de editar, no el de crear', function () {
    $usuario = User::where('username', 'DeibyJY')->firstOrFail();

    $this->post('/usuario/modificarPost', [
        'id' => $usuario->id,
        'password' => 'debil',
    ])->assertSessionHas('modal', 'editar');

    $this->get('/usuario')->assertSee('document.getElementById("modalEditar")', false);
});

it('el resto de módulos también deja el error dentro del modal (ej. persona con nombre inválido)', function () {
    $this->post('/persona', [
        'nombre' => 'Juan2', 'apellido' => 'Perez', 'cedula' => '1234567',
        'celular' => '7000000', 'correo' => 'j@mail.com', 'estado' => 1,
    ])->assertSessionHas('modal', 'crear')->assertSessionHas('error');

    $this->get('/persona')
        ->assertSee('document.getElementById("modalCrear")', false)
        ->assertSee('"nombre":"Juan2"', false);
});
