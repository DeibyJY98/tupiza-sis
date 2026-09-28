<?php

use App\Models\DetalleRol;
use App\Models\Habitacion;
use App\Models\Pago;
use App\Models\Permiso;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

// P1.1: estado_estadia (pendiente/confirmada/check_in/check_out) es aditivo y no
// reemplaza a 'estado' (0/1), que sigue siendo la fuente de verdad para ocupación,
// solapamiento y cancelación.
beforeEach(function () {
    loguearComoTrabajador('recep_' . uniqid());
});

// loguearComoTrabajador() solo da permiso sobre "reserva"; las pruebas de pago
// necesitan además el permiso "pago" para pasar el middleware de esa ruta.
function otorgarPermisoAlUsuarioActual(string $nombrePermiso): void
{
    $usuario = Auth::guard(session('auth_guard'))->user();
    $permiso = Permiso::firstOrCreate(['nombre' => $nombrePermiso]);
    DetalleRol::firstOrCreate(['id_rol' => $usuario->id_rol, 'id_permiso' => $permiso->id]);
}

it('permite hacer check-in el día de llegada de una reserva pendiente', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(2));

    $response = $this->post(route('reserva.check-in', $reserva->id));

    $response->assertSessionHas('success');
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_CHECK_IN);
});

it('rechaza el check-in si la fecha de llegada todavía no es hoy', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::tomorrow(), Carbon::tomorrow()->addDays(2));

    $response = $this->post(route('reserva.check-in', $reserva->id));

    $response->assertSessionHas('error');
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_PENDIENTE);
});

it('rechaza el check-in de una reserva cancelada', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(2), estado: 0);

    $response = $this->post(route('reserva.check-in', $reserva->id));

    $response->assertSessionHas('error');
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_PENDIENTE);
});

it('rechaza un segundo check-in sobre la misma reserva', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(2));
    $reserva->update(['estado_estadia' => Reserva::ESTADIA_CHECK_IN]);

    $response = $this->post(route('reserva.check-in', $reserva->id));

    $response->assertSessionHas('error');
    expect(session('error'))->toContain('ya tiene un check-in');
});

it('el check-out libera la habitación de inmediato sin esperar la hora de check-out', function () {
    Carbon::setTestNow(Carbon::today()->setTime(9, 0)); // antes de HORA_CHECK_OUT (11:00)

    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today());
    $reserva->update(['estado_estadia' => Reserva::ESTADIA_CHECK_IN]);

    expect($habitacion->fresh()->estado)->toBe(0); // ocupada mientras hay check-in

    $response = $this->post(route('reserva.check-out', $reserva->id));

    $response->assertSessionHas('success');
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_CHECK_OUT)
        ->and($habitacion->fresh()->estado)->toBe(1); // disponible ya, aunque son las 9am

    Carbon::setTestNow();
});

it('rechaza el check-out si no se había registrado el check-in', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(2));

    $response = $this->post(route('reserva.check-out', $reserva->id));

    $response->assertSessionHas('error');
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_PENDIENTE);
});

it('un pago que cubre el saldo total confirma la reserva pendiente', function () {
    otorgarPermisoAlUsuarioActual('pago');
    $habitacion = crearHabitacionDePrueba(); // costo_total de la reserva = 100 (1 noche x 100)
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today());
    $reserva->update(['costo_total' => 100]);
    [$cliente] = crearClienteYTrabajadorDePrueba();

    $response = $this->post('/pago', [
        'fecha' => Carbon::today()->toDateString(),
        'monto' => 100,
        'comprobante' => comprobanteFake('comprobante.jpg'),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_reserva' => $reserva->id,
    ]);

    $response->assertRedirect(route('mostrar.pago'));
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_CONFIRMADA);
});

it('un pago parcial no confirma la reserva', function () {
    otorgarPermisoAlUsuarioActual('pago');
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today());
    $reserva->update(['costo_total' => 100]);
    [$cliente] = crearClienteYTrabajadorDePrueba();

    $this->post('/pago', [
        'fecha' => Carbon::today()->toDateString(),
        'monto' => 40,
        'comprobante' => comprobanteFake('comprobante.jpg'),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_reserva' => $reserva->id,
    ])->assertRedirect(route('mostrar.pago'));

    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_PENDIENTE);
});

it('un pago que cubre el saldo no retrocede una reserva que ya hizo check-in', function () {
    otorgarPermisoAlUsuarioActual('pago');
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today());
    $reserva->update(['costo_total' => 100, 'estado_estadia' => Reserva::ESTADIA_CHECK_IN]);
    [$cliente] = crearClienteYTrabajadorDePrueba();

    $this->post('/pago', [
        'fecha' => Carbon::today()->toDateString(),
        'monto' => 100,
        'comprobante' => comprobanteFake('comprobante.jpg'),
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_reserva' => $reserva->id,
    ])->assertRedirect(route('mostrar.pago'));

    // Sigue en check_in, no "retrocede" a confirmada por el pago.
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_CHECK_IN);
});
