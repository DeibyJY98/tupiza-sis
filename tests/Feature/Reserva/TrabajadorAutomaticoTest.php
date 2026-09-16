<?php

use App\Models\Persona;
use App\Models\Permiso;
use App\Models\DetalleRol;
use App\Models\Reserva;
use App\Models\Trabajador;

// El formulario de crear reserva ya no debe obligar a elegir manualmente un trabajador
// cuando el usuario logueado ya es uno: el backend debe reconocerlo por su persona
// (id_persona) y usar siempre ese id, sin confiar en lo que llegue del formulario.

function loguearComoTrabajador(string $username): Trabajador
{
    $persona = Persona::create([
        'nombre' => 'Recepcion', 'apellido' => 'Prueba', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000002', 'correo' => fake()->unique()->safeEmail(), 'estado' => 1,
    ]);
    $trabajador = new Trabajador(['cargo' => 'Recepcionista', 'estado' => 1, 'id_persona' => $persona->id]);
    $trabajador->salario = '2000';
    $trabajador->save();

    $rol = crearRol('recepcionista_' . $username);
    $permisoReserva = Permiso::firstOrCreate(['nombre' => 'reserva']);
    DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permisoReserva->id]);

    // crearUsuarioConRol crea su propia Persona; en su lugar creamos el User a mano
    // para dejarlo vinculado a LA MISMA persona que el trabajador.
    $user = \App\Models\User::create([
        'username' => $username,
        'email' => $username . '@mail.com',
        'password' => bcrypt('12345'),
        'estado' => 1,
        'id_rol' => $rol->id,
        'id_persona' => $persona->id,
    ]);

    test()->post('/login', ['username' => $username, 'password' => '12345']);

    return $trabajador;
}

it('usa el trabajador vinculado al usuario logueado aunque no se envíe id_trabajador', function () {
    $trabajador = loguearComoTrabajador('recep_auto');
    $habitacion = crearHabitacionDePrueba();
    [$cliente] = crearClienteYTrabajadorDePrueba();

    $this->post('/reserva', [
        'costo_total' => 100,
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(2)->toDateString(),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_habitacion' => $habitacion->id,
    ])->assertRedirect(route('mostrar.reserva'));

    $reserva = Reserva::latest('id')->firstOrFail();
    expect($reserva->id_trabajador)->toBe($trabajador->id);
});

it('ignora un id_trabajador manipulado y usa el del usuario logueado', function () {
    $trabajador = loguearComoTrabajador('recep_manip');
    $habitacion = crearHabitacionDePrueba();
    [$cliente, $otroTrabajador] = crearClienteYTrabajadorDePrueba();

    $this->post('/reserva', [
        'costo_total' => 100,
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(2)->toDateString(),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_habitacion' => $habitacion->id,
        'id_trabajador' => $otroTrabajador->id, // intento de suplantar a otro trabajador
    ])->assertRedirect(route('mostrar.reserva'));

    $reserva = Reserva::latest('id')->firstOrFail();
    expect($reserva->id_trabajador)->toBe($trabajador->id)
        ->and($reserva->id_trabajador)->not->toBe($otroTrabajador->id);
});

it('exige seleccionar trabajador manualmente cuando el usuario logueado no es uno', function () {
    $habitacion = crearHabitacionDePrueba();
    [$cliente, $trabajador] = crearClienteYTrabajadorDePrueba();

    $rol = crearRol('cliente_sin_trabajador');
    $permisoReserva = Permiso::firstOrCreate(['nombre' => 'reserva']);
    DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permisoReserva->id]);
    [$user, $password] = crearUsuarioConRol($rol, 'cliente_sin_trabajador1');
    $this->post('/login', ['username' => $user->username, 'password' => $password]);

    $response = $this->post('/reserva', [
        'costo_total' => 100,
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(2)->toDateString(),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_habitacion' => $habitacion->id,
        // sin id_trabajador
    ]);

    $response->assertSessionHas('error');
    expect(Reserva::count())->toBe(0);
});
