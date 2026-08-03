<?php

use App\Models\Habitacion;
use App\Models\Permiso;
use App\Models\Persona;
use App\Models\Reserva;
use App\Models\Rol;
use App\Models\User;
use App\Models\Pago;

// "Eliminar" debe ser una baja lógica (estado = 0) en los módulos que exponen
// Activo/Inactivo o Completado/Cancelado en su listado, no un soft-delete que
// oculte el registro vía deleted_at. Habitacion y Permiso son la excepción
// documentada: no tienen un estado "activo/inactivo" manual, así que ahí sí
// se conserva el soft-delete real.
beforeEach(function () {
    $this->seed();
    $this->post('/login', ['username' => 'DeibyJY', 'password' => '12345'])
        ->assertRedirect(route('mostrar.reserva'));
});

it('Persona: eliminar pone estado en 0 pero el registro sigue existiendo', function () {
    $persona = Persona::where('estado', 1)->firstOrFail();

    $this->post('/persona/eliminar', ['inputIdEliminar' => $persona->id])
        ->assertRedirect(route('mostrar.persona'));

    expect(Persona::find($persona->id))->not->toBeNull();
    expect($persona->fresh()->estado)->toBe(0);
});

it('Rol: eliminar pone estado en 0 pero el registro sigue existiendo', function () {
    $rol = Rol::where('estado', 1)->firstOrFail();

    $this->post('/rol/eliminar', ['id' => $rol->id])
        ->assertRedirect(route('mostrar.rol'));

    expect(Rol::find($rol->id))->not->toBeNull();
    expect($rol->fresh()->estado)->toBe(0);
});

it('Pago: eliminar cancela el pago (estado 0) sin borrar el registro', function () {
    $pago = Pago::where('estado', 1)->firstOrFail();

    $this->post('/pago/eliminar', ['inputIdEliminar' => $pago->id])
        ->assertRedirect(route('mostrar.pago'));

    expect(Pago::find($pago->id))->not->toBeNull();
    expect($pago->fresh()->estado)->toBe(0);
});

it('Usuario: eliminar inactiva la cuenta en vez de borrarla de la base de datos', function () {
    $user = User::where('username', 'SharonP')->firstOrFail();

    $this->post('/usuario/eliminar', ['id' => $user->id])
        ->assertRedirect(route('mostrar.usuario'));

    expect(User::find($user->id))->not->toBeNull();
    expect($user->fresh()->estado)->toBe(0);
});

it('Reserva: eliminar cancela la reserva (estado 0) y libera la habitación', function () {
    $reserva = Reserva::where('estado', 1)->firstOrFail();
    $habitacion = $reserva->habitaciones()->first();

    $this->post('/reserva/eliminar', ['inputIdEliminar' => $reserva->id])
        ->assertRedirect(route('mostrar.reserva'));

    expect(Reserva::find($reserva->id))->not->toBeNull();
    expect($reserva->fresh()->estado)->toBe(0);

    if ($habitacion) {
        expect($habitacion->fresh()->estado)->toBe(1); // disponible
    }
});

it('Habitacion: eliminar sigue siendo un soft-delete real (excepción intencional)', function () {
    $habitacion = Habitacion::first();

    $this->post('/habitacion/eliminar', ['inputIdEliminar' => $habitacion->id])
        ->assertRedirect(route('mostrar.habitacion'));

    expect(Habitacion::find($habitacion->id))->toBeNull();
    $this->assertSoftDeleted('habitacions', ['id' => $habitacion->id]);
});

it('Permiso: eliminar sigue siendo un delete real (no tiene campo estado)', function () {
    $permiso = Permiso::first();

    $this->post('/permiso/eliminar', ['inputIdEliminar' => $permiso->id])
        ->assertRedirect(route('mostrar.permiso'));

    expect(Permiso::find($permiso->id))->toBeNull();
});
