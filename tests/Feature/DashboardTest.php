<?php

use App\Models\Pago;
use App\Models\Reserva;
use Carbon\Carbon;

// P2.2: el dashboard no pertenece a ningún módulo puntual, así que solo exige estar
// logueado (cualquiera de los 3 guards), no un permiso específico.
it('redirige a login si un invitado intenta ver el dashboard', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

it('un usuario logueado, sin importar su rol, puede ver el dashboard', function () {
    loguearComoTrabajador('cualquier_rol_' . uniqid());

    $response = $this->get(route('dashboard'));

    $response->assertOk();
});

it('muestra la ocupación, llegadas/salidas de hoy e ingresos del mes con datos reales', function () {
    loguearComoTrabajador('recep_' . uniqid());

    // Antes de la hora de check-out: si la prueba corriera después de las 11:00 con
    // el reloj real, autoCheckoutVencidos() cerraría reservaSaleHoy (fecha_fin = hoy,
    // pendiente) antes de que se pueda comprobar que aparece en "salidas de hoy".
    Carbon::setTestNow(Carbon::today()->setTime(9, 0));

    $habitacionOcupada = crearHabitacionDePrueba();
    $reservaHoy = crearReservaActiva($habitacionOcupada, Carbon::today(), Carbon::today()->addDays(3));
    $reservaHoy->update(['costo_total' => 300, 'estado_estadia' => Reserva::ESTADIA_CONFIRMADA]);

    $habitacionQueSaleHoy = crearHabitacionDePrueba();
    $reservaSaleHoy = crearReservaActiva($habitacionQueSaleHoy, Carbon::today()->subDays(2), Carbon::today());

    [$cliente] = crearClienteYTrabajadorDePrueba();
    Pago::create([
        'fecha' => Carbon::today()->toDateString(),
        'monto' => 150,
        'comprobante' => 'x.jpg',
        'estado' => 1,
        'id_cliente' => $cliente->id,
        'id_reserva' => $reservaHoy->id,
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('llegadasHoy', fn ($llegadas) => $llegadas->contains('id', $reservaHoy->id));
    $response->assertViewHas('salidasHoy', fn ($salidas) => $salidas->contains('id', $reservaSaleHoy->id));
    $response->assertViewHas('ingresosMes', fn ($ingresos) => $ingresos >= 150);
    $response->assertViewHas('habitacionesOcupadas', fn ($ocupadas) => $ocupadas >= 1);

    Carbon::setTestNow();
});

it('no cuenta como ocupada una habitación cuyo huésped ya hizo check-in y sigue en curso, sin importar la hora', function () {
    loguearComoTrabajador('recep_' . uniqid());

    Carbon::setTestNow(Carbon::today()->setTime(23, 0)); // bien pasada la hora de check-out
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today());
    $reserva->update(['estado_estadia' => Reserva::ESTADIA_CHECK_IN]);

    $response = $this->get(route('dashboard'));

    $response->assertViewHas('habitacionesOcupadas', fn ($ocupadas) => $ocupadas >= 1);

    Carbon::setTestNow();
});

it('no muestra en llegadas ni salidas de hoy una reserva que ya completó su check-out', function () {
    loguearComoTrabajador('recep_' . uniqid());

    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(2));
    $reserva->update(['estado_estadia' => Reserva::ESTADIA_CHECK_OUT]);

    $response = $this->get(route('dashboard'));

    $response->assertViewHas('llegadasHoy', fn ($llegadas) => ! $llegadas->contains('id', $reserva->id));
    $response->assertViewHas('salidasHoy', fn ($salidas) => ! $salidas->contains('id', $reserva->id));
});

it('no muestra en llegadas de hoy una reserva pendiente cuyo pago todavía no se confirmó', function () {
    loguearComoTrabajador('recep_' . uniqid());

    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(2));
    // crearReservaActiva no fija estado_estadia: queda en "pendiente" (default de la migración).

    $response = $this->get(route('dashboard'));

    $response->assertViewHas('llegadasHoy', fn ($llegadas) => ! $llegadas->contains('id', $reserva->id));
});

it('no muestra en llegadas de hoy una reserva que ya hizo check-in (el huésped ya ingresó, no está "por llegar")', function () {
    loguearComoTrabajador('recep_' . uniqid());

    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(2));
    $reserva->update(['estado_estadia' => Reserva::ESTADIA_CHECK_IN]);

    $response = $this->get(route('dashboard'));

    $response->assertViewHas('llegadasHoy', fn ($llegadas) => ! $llegadas->contains('id', $reserva->id));
});

it('arma el calendario del mes con una fila por habitación y día ocupado, sin repetir reservas ya con check-out', function () {
    loguearComoTrabajador('recep_' . uniqid());

    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today(), Carbon::today()->addDays(2));

    $habitacionCheckOut = crearHabitacionDePrueba();
    $reservaCheckOut = crearReservaActiva($habitacionCheckOut, Carbon::today(), Carbon::today()->addDays(2));
    $reservaCheckOut->update(['estado_estadia' => Reserva::ESTADIA_CHECK_OUT]);

    $response = $this->get(route('dashboard'));

    $response->assertViewHas('reservasDelMes', function ($reservasDelMes) use ($reserva, $reservaCheckOut, $habitacion) {
        $entrada = $reservasDelMes->firstWhere('id_reserva', $reserva->id);

        return $entrada
            && $entrada['id_habitacion'] === $habitacion->id
            && $entrada['fecha_inicio'] === Carbon::today()->toDateString()
            && $entrada['fecha_fin'] === Carbon::today()->addDays(2)->toDateString()
            && ! $reservasDelMes->contains('id_reserva', $reservaCheckOut->id);
    });
});
