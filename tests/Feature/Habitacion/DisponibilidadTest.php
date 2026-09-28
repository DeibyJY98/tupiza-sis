<?php

use App\Models\Habitacion;
use App\Models\Reserva;
use Carbon\Carbon;

// Reutiliza crearHabitacionDePrueba() y crearReservaActiva() definidas en
// tests/Feature/Services/ReservaServiceTest.php (funciones globales de Pest).

afterEach(function () {
    Carbon::setTestNow();
});

it('mantiene la habitación ocupada antes de la hora de check-out el día que termina la reserva', function () {
    Carbon::setTestNow(Carbon::today()->setTime(9, 0));

    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->subDays(2), Carbon::today());

    expect($habitacion->fresh()->estado)->toBe(0); // 0 = ocupada, aún no es hora de check-out (11:00)
});

it('libera la habitación automáticamente después de la hora de check-out el día que termina la reserva', function () {
    Carbon::setTestNow(Carbon::today()->setTime(9, 0));
    $habitacion = crearHabitacionDePrueba();
    crearReservaActiva($habitacion, Carbon::today()->subDays(2), Carbon::today());
    expect($habitacion->fresh()->estado)->toBe(0);

    Carbon::setTestNow(Carbon::today()->setTime(11, 30)); // pasó la hora de check-out
    $habitacion->actualizarDisponibilidad();

    expect($habitacion->fresh()->estado)->toBe(1); // 1 = disponible
});

it('respeta la constante Habitacion::HORA_CHECK_OUT usada por el schedule', function () {
    expect(Habitacion::HORA_CHECK_OUT)->toBe('11:00');
});

it('mantiene ocupada la habitación aunque pase la hora de check-out si el huésped ya hizo check-in y no se registró el check-out', function () {
    Carbon::setTestNow(Carbon::today()->setTime(9, 0));
    $habitacion = crearHabitacionDePrueba();
    $reserva = crearReservaActiva($habitacion, Carbon::today()->subDays(2), Carbon::today());
    $reserva->update(['estado_estadia' => Reserva::ESTADIA_CHECK_IN]);
    expect($habitacion->fresh()->estado)->toBe(0);

    Carbon::setTestNow(Carbon::today()->setTime(11, 30)); // pasó la hora de check-out
    $habitacion->actualizarDisponibilidad();

    // Sigue ocupada: nadie registró el check-out, así que el auto-liberado por hora
    // no debe pisar una estadía que sabemos que sigue en curso.
    expect($habitacion->fresh()->estado)->toBe(0);
});
