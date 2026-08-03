<?php

use App\Models\Caracteristica;
use App\Models\Cliente;
use App\Models\DetalleRol;
use App\Models\Permiso;
use App\Models\Persona;
use App\Models\ServicioExtra;
use App\Models\TipoHabitacion;
use App\Models\Trabajador;

// Da acceso total a un usuario logueado sobre los módulos tocados en este cambio,
// para poder probar el CRUD real de cada uno vía HTTP (incluye render de las vistas).
function loguearUsuarioConAccesoTotal(): void
{
    $rol = crearRol('administrador');

    foreach (['caracteristica', 'tipo_habitacion', 'servicio_extra', 'permiso', 'cliente', 'trabajador'] as $nombrePermiso) {
        $permiso = Permiso::firstOrCreate(['nombre' => $nombrePermiso]);
        DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permiso->id]);
    }

    [$user, $password] = crearUsuarioConRol($rol, 'admin_smoke');
    test()->post('/login', ['username' => $user->username, 'password' => $password]);
}

function crearPersonaDePrueba(): Persona
{
    return Persona::create([
        'nombre' => 'Test', 'apellido' => 'Persona', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000000', 'correo' => fake()->unique()->safeEmail(), 'estado' => 1,
    ]);
}

beforeEach(function () {
    loguearUsuarioConAccesoTotal();
});

it('Caracteristica: index, store, update y destroy funcionan de punta a punta', function () {
    $this->get('/caracteristica')->assertOk();

    $this->post('/caracteristica', ['nombre' => 'Vista al mar', 'estado' => 1])->assertRedirect(route('mostrar.caracteristica'));
    $caracteristica = Caracteristica::where('nombre', 'Vista al mar')->firstOrFail();

    $this->post('/caracteristica/editar', ['id' => $caracteristica->id, 'nombre' => 'Vista al jardín'])
        ->assertRedirect(route('mostrar.caracteristica'));
    expect($caracteristica->fresh()->nombre)->toBe('Vista al jardín');

    $this->post('/caracteristica/eliminar', ['inputIdEliminar' => $caracteristica->id])
        ->assertRedirect(route('mostrar.caracteristica'));
    expect($caracteristica->fresh()->estado)->toBe(0);
});

it('TipoHabitacion: index, store, update y destroy funcionan de punta a punta', function () {
    $this->get('/tipo_habitacion')->assertOk();

    $this->post('/tipo_habitacion', [
        'nombre' => 'Suite', 'descripcion' => 'Amplia', 'cant_cama' => 2, 'precio' => 300, 'estado' => 1,
    ])->assertRedirect(route('mostrar.tipo_habitacion'));
    $tipo = TipoHabitacion::where('nombre', 'Suite')->firstOrFail();

    $this->post('/tipo_habitacion/editar', ['id' => $tipo->id, 'precio' => 350])
        ->assertRedirect(route('mostrar.tipo_habitacion'));
    expect($tipo->fresh()->precio)->toBe(350);

    $this->post('/tipo_habitacion/eliminar', ['inputIdEliminar' => $tipo->id])
        ->assertRedirect(route('mostrar.tipo_habitacion'));
    expect($tipo->fresh()->estado)->toBe(0);
});

it('ServicioExtra: index, store, update y destroy funcionan de punta a punta', function () {
    $this->get('/servicio_extra')->assertOk();

    $this->post('/servicio_extra', [
        'nombre' => 'Desayuno', 'descripcion' => 'Buffet', 'precio' => 25, 'estado' => 1,
    ])->assertRedirect(route('mostrar.servicio_extra'));
    $servicio = ServicioExtra::where('nombre', 'Desayuno')->firstOrFail();

    $this->post('/servicio_extra/editar', ['id' => $servicio->id, 'precio' => 30])
        ->assertRedirect(route('mostrar.servicio_extra'));
    expect($servicio->fresh()->precio)->toBe(30);

    $this->post('/servicio_extra/eliminar', ['inputIdEliminar' => $servicio->id])
        ->assertRedirect(route('mostrar.servicio_extra'));
    expect($servicio->fresh()->estado)->toBe(0);
});

it('Permiso: index, store, update y destroy funcionan de punta a punta', function () {
    $this->get('/permiso')->assertOk();

    $this->post('/permiso', ['nombre' => 'modulo_prueba'])->assertRedirect(route('mostrar.permiso'));
    $permiso = Permiso::where('nombre', 'modulo_prueba')->firstOrFail();

    $this->post('/permiso/editar', ['id' => $permiso->id, 'nombre' => 'modulo_prueba_2'])
        ->assertRedirect(route('mostrar.permiso'));
    expect($permiso->fresh()->nombre)->toBe('modulo_prueba_2');

    $this->post('/permiso/eliminar', ['inputIdEliminar' => $permiso->id])
        ->assertRedirect(route('mostrar.permiso'));
    expect(Permiso::find($permiso->id))->toBeNull();
});

it('Cliente: index, store, update y destroy funcionan de punta a punta', function () {
    $persona = crearPersonaDePrueba();

    $this->get('/cliente')->assertOk();

    $this->post('/cliente', [
        'nit' => 12345, 'razon_social' => 'Comercial ABC', 'estado' => 1, 'id_persona' => $persona->id,
    ])->assertRedirect(route('mostrar.cliente'));
    $cliente = Cliente::where('id_persona', $persona->id)->firstOrFail();

    $this->post('/cliente/editar', ['id' => $cliente->id, 'razon_social' => 'Comercial XYZ'])
        ->assertRedirect(route('mostrar.cliente'));
    expect($cliente->fresh()->razon_social)->toBe('Comercial XYZ');

    $this->post('/cliente/eliminar', ['inputIdEliminar' => $cliente->id])
        ->assertRedirect(route('mostrar.cliente'));
    expect($cliente->fresh()->estado)->toBe(0);
});

it('Trabajador: index, store, update y destroy funcionan de punta a punta', function () {
    $persona = crearPersonaDePrueba();

    $this->get('/trabajador')->assertOk();

    $this->post('/trabajador', [
        'cargo' => 'Recepcionista', 'salario' => 2500, 'estado' => 1, 'id_persona' => $persona->id,
    ])->assertRedirect(route('mostrar.trabajador'));
    $trabajador = Trabajador::where('id_persona', $persona->id)->firstOrFail();

    $this->post('/trabajador/editar', ['id' => $trabajador->id, 'salario' => 2800])
        ->assertRedirect(route('mostrar.trabajador'));
    expect((float) $trabajador->fresh()->salario)->toBe(2800.0);

    $this->post('/trabajador/eliminar', ['inputIdEliminar' => $trabajador->id])
        ->assertRedirect(route('mostrar.trabajador'));
    expect($trabajador->fresh()->estado)->toBe(0);
});
