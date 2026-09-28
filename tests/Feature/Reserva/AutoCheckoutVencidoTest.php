<?php

use App\Models\Habitacion;
use App\Models\Notificacion;
use App\Models\Reserva;
use Carbon\Carbon;

// Reutiliza crearHabitacionDePrueba() y crearReservaActiva() (Pest/Services/ReservaServiceTest.php).

afterEach(function () {
    Carbon::setTestNow();
});

it('cierra automáticamente una reserva pendiente cuya fecha de salida ya pasó y notifica', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->subDays(5), Carbon::today()->subDays(2));
    // crearReservaActiva no fija estado_estadia: queda "pendiente" por defecto.

    $cerradas = Reserva::autoCheckoutVencidos();

    expect($cerradas)->toBe(1);
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_CHECK_OUT);
    expect(Notificacion::where('id_reserva', $reserva->id)->where('tipo', 'auto_checkout')->exists())->toBeTrue();
});

it('no cierra una reserva confirmada cuya fecha de salida es hoy si todavía no pasó la hora de check-out', function () {
    Carbon::setTestNow(Carbon::today()->setTime(9, 0));

    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->subDays(1), Carbon::today());
    $reserva->update(['estado_estadia' => Reserva::ESTADIA_CONFIRMADA]);

    $cerradas = Reserva::autoCheckoutVencidos();

    expect($cerradas)->toBe(0);
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_CONFIRMADA);
});

it('cierra una reserva confirmada cuya fecha de salida es hoy una vez pasada la hora de check-out', function () {
    Carbon::setTestNow(Carbon::today()->setTime(9, 0));
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->subDays(1), Carbon::today());
    $reserva->update(['estado_estadia' => Reserva::ESTADIA_CONFIRMADA]);

    Carbon::setTestNow(Carbon::today()->setTime(11, 30));
    $cerradas = Reserva::autoCheckoutVencidos();

    expect($cerradas)->toBe(1);
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_CHECK_OUT);
});

it('no toca una reserva con check-in en curso aunque su fecha de salida ya haya pasado', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->subDays(3), Carbon::today()->subDays(1));
    $reserva->update(['estado_estadia' => Reserva::ESTADIA_CHECK_IN]);

    $cerradas = Reserva::autoCheckoutVencidos();

    expect($cerradas)->toBe(0);
    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_CHECK_IN);
});

it('no vuelve a notificar ni tocar una reserva que ya tiene check-out', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->subDays(5), Carbon::today()->subDays(2));
    $reserva->update(['estado_estadia' => Reserva::ESTADIA_CHECK_OUT]);

    $cerradas = Reserva::autoCheckoutVencidos();

    expect($cerradas)->toBe(0);
    expect(Notificacion::where('id_reserva', $reserva->id)->exists())->toBeFalse();
});

it('no toca una reserva cancelada aunque su fecha de salida ya haya pasado', function () {
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->subDays(5), Carbon::today()->subDays(2), estado: 0);

    $cerradas = Reserva::autoCheckoutVencidos();

    expect($cerradas)->toBe(0);
    expect($reserva->fresh()->estado_estadia)->not->toBe(Reserva::ESTADIA_CHECK_OUT);
});

it('el dashboard cierra automáticamente las reservas vencidas al cargar, sin depender del cron', function () {
    loguearComoTrabajador('recep_' . uniqid());

    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->subDays(5), Carbon::today()->subDays(2));

    $this->get(route('dashboard'))->assertOk();

    expect($reserva->fresh()->estado_estadia)->toBe(Reserva::ESTADIA_CHECK_OUT);
    expect(Notificacion::where('id_reserva', $reserva->id)->where('tipo', 'auto_checkout')->exists())->toBeTrue();
});
