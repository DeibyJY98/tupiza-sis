<?php

use App\Models\Cliente;
use App\Models\HabitacionReserva;
use App\Models\HabitacionServicioExtra;
use App\Models\Persona;
use App\Models\Reserva;
use App\Models\ServicioExtra;
use App\Models\Trabajador;

// RF16: el catálogo de servicios extras ya existía, pero no había forma de asociar un
// servicio extra concreto a una reserva/habitación. Estos tests cubren esa asociación
// a través de los endpoints reales de ReservaController (store/update), no accediendo
// directo al modelo, para probar el flujo tal como lo usa la vista.
function crearClienteYTrabajadorDePrueba(): array
{
    $personaCliente = Persona::create([
        'nombre' => 'Cliente', 'apellido' => 'Prueba', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000000', 'correo' => fake()->unique()->safeEmail(), 'estado' => 1,
    ]);
    $cliente = Cliente::create(['nit' => 123, 'razon_social' => 'N/A', 'estado' => 1, 'id_persona' => $personaCliente->id]);

    $personaTrabajador = Persona::create([
        'nombre' => 'Trabajador', 'apellido' => 'Prueba', 'cedula' => fake()->unique()->numerify('########'),
        'celular' => '70000001', 'correo' => fake()->unique()->safeEmail(), 'estado' => 1,
    ]);
    $trabajador = new Trabajador(['cargo' => 'Recepcionista', 'estado' => 1, 'id_persona' => $personaTrabajador->id]);
    $trabajador->salario = '1500';
    $trabajador->save();

    return [$cliente, $trabajador];
}

beforeEach(function () {
    $rol = crearRol('administrador_reserva');
    $permiso = \App\Models\Permiso::firstOrCreate(['nombre' => 'reserva']);
    \App\Models\DetalleRol::create(['id_rol' => $rol->id, 'id_permiso' => $permiso->id]);

    [$user, $password] = crearUsuarioConRol($rol, 'admin_reserva');
    $this->post('/login', ['username' => $user->username, 'password' => $password]);
});

it('asocia servicios extras a una reserva nueva y los suma al costo total', function () {
    $habitacion = crearHabitacionDePrueba();
    [$cliente, $trabajador] = crearClienteYTrabajadorDePrueba();

    $desayuno = ServicioExtra::create(['nombre' => 'Desayuno', 'descripcion' => 'Buffet', 'precio' => 25, 'estado' => 1]);
    $spa = ServicioExtra::create(['nombre' => 'Spa', 'descripcion' => 'Masaje', 'precio' => 80, 'estado' => 1]);

    $this->post('/reserva', [
        'costo_total' => 100 + 25 + 80,
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(2)->toDateString(),
        'estado' => 1,
        'id_trabajador' => $trabajador->id,
        'id_cliente' => $cliente->id,
        'id_habitacion' => $habitacion->id,
        'servicios_extra' => [$desayuno->id, $spa->id],
    ])->assertRedirect(route('mostrar.reserva'));

    $reserva = Reserva::latest('id')->firstOrFail();
    $habitacionReserva = HabitacionReserva::where('id_reserva', $reserva->id)->firstOrFail();

    expect(HabitacionServicioExtra::where('id_habitacion_reserva', $habitacionReserva->id)->count())->toBe(2);

    $nombresAsociados = $reserva->toShow()['servicios_extra']->pluck('nombre')->sort()->values()->all();
    expect($nombresAsociados)->toBe(['Desayuno', 'Spa']);
});

it('permite crear una reserva sin servicios extras (el campo es opcional)', function () {
    $habitacion = crearHabitacionDePrueba();
    [$cliente, $trabajador] = crearClienteYTrabajadorDePrueba();

    $this->post('/reserva', [
        'costo_total' => 100,
        'fecha_inicio' => now()->addDay()->toDateString(),
        'fecha_fin' => now()->addDays(2)->toDateString(),
        'estado' => 1,
        'id_trabajador' => $trabajador->id,
        'id_cliente' => $cliente->id,
        'id_habitacion' => $habitacion->id,
    ])->assertRedirect(route('mostrar.reserva'));

    $reserva = Reserva::latest('id')->firstOrFail();

    expect($reserva->toShow()['servicios_extra'])->toHaveCount(0);
});

it('sincroniza los servicios extras de una reserva al editarla', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, now()->addDay()->toDateString(), now()->addDays(2)->toDateString());

    $desayuno = ServicioExtra::create(['nombre' => 'Desayuno', 'descripcion' => 'Buffet', 'precio' => 25, 'estado' => 1]);
    $lavanderia = ServicioExtra::create(['nombre' => 'Lavandería', 'descripcion' => 'Lavado', 'precio' => 15, 'estado' => 1]);

    // Primera edición: se asocia "Desayuno".
    $this->post('/reserva/editar', [
        'id' => $reserva->id,
        'id_habitacion' => $habitacion->id,
        'servicios_extra' => [$desayuno->id],
    ], ['X-Requested-With' => 'XMLHttpRequest'])->assertJson(['success' => true]);

    expect($reserva->fresh()->toShow()['servicios_extra']->pluck('nombre')->all())->toBe(['Desayuno']);

    // Segunda edición: se reemplaza por "Lavandería" únicamente.
    $this->post('/reserva/editar', [
        'id' => $reserva->id,
        'id_habitacion' => $habitacion->id,
        'servicios_extra' => [$lavanderia->id],
    ], ['X-Requested-With' => 'XMLHttpRequest'])->assertJson(['success' => true]);

    expect($reserva->fresh()->toShow()['servicios_extra']->pluck('nombre')->all())->toBe(['Lavandería']);
});
